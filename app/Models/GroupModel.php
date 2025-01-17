<?php

namespace App\Models;

use App\Models\Rating\GroupRatingCalculator;
use GrahamCampbell\Markdown\Facades\Markdown;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Cache;

class GroupModel extends Model
{
    protected $tableName='group';
    protected $table='group';
    protected $primaryKey='gid';
    const DELETED_AT=null;
    const UPDATED_AT=null;
    const CREATED_AT=null;

    /*
        join_policy:
            1:a user can join this group only by invitation
            2:a user can join this group only by application
            3:a user can join this group by both invitation and application
    */
    public $role=[
        "-3"=>"None",
        "-1"=>"Invited",
        "0"=>"Pending",
        "1"=>"Member",
        "2"=>"Manager",
        "3"=>"Leader"
    ];
    public $role_color=[
        "-3"=>"wemd-black",
        "-1"=>"wemd-deep-purple",
        "0"=>"wemd-red",
        "1"=>"wemd-grey",
        "2"=>"wemd-light-blue",
        "3"=>"wemd-amber"
    ];

    public function trendingGroups()
    {
        return Cache::tags(['group'])->get('trending');
    }

    public function gid($gcode)
    {
        return DB::table($this->tableName)->where(["gcode"=>$gcode])->first()["gid"];
    }

    public function cacheTrendingGroups()
    {
        $trending_groups=DB::table($this->tableName)->where(["public"=>1])->orderBy('create_time', 'desc')->select("gid", "gcode", "img", "name", "verified")->get()->all();
        foreach ($trending_groups as &$t) {
            $t["members"]=$this->countGroupMembers($t["gid"]);
        }
        usort($trending_groups, function ($a, $b) {
            return $b["members"]<=>$a["members"];
        });
        Cache::tags(['group'])->put('trending', array_slice($trending_groups,0,12), 3600*24);
    }

    public function userGroups($uid)
    {
        $user_groups=DB::table("group_member")->join("group", "group_member.gid", "=", "group.gid")->where(["uid"=>$uid])->select("group.gid as gid", "gcode", "img", "name", "verified")->limit(12)->get()->all();
        foreach ($user_groups as &$m) {
            $m["members"]=$this->countGroupMembers($m["gid"]);
        }
        return $user_groups;
    }

    public function countGroupMembers($gid)
    {
        return DB::table("group_member")->where(["gid"=>$gid])->count();
    }

    public function getGroupTags($gid)
    {
        return DB::table("group_tag")->where(["gid"=>$gid])->select("tag")->get()->all();
    }

    public function countGroupContest($gid)
    {
        return [
            "contest_ahead" => DB::table("contest")->where(["gid"=>$gid])->where("begin_time", ">", DB::raw("now()"))->count(),
            "contest_going" => DB::table("contest")->where(["gid"=>$gid])->where("begin_time", "<=", DB::raw("now()"))->where("end_time", ">=", DB::raw("now()"))->count(),
            "contest_end" => DB::table("contest")->where(["gid"=>$gid])->where("end_time", "<", DB::raw("now()"))->count()
        ];
    }

    public function changeNickName($gid, $uid, $nickName)
    {
        return DB::table("group_member")->where(["gid"=>$gid, "uid"=>$uid])->update([
            "nick_name"=>$nickName
        ]);
    }

    public function changeGroupName($gid, $GroupName)
    {
        return DB::table("group")->where('gid',$gid)->update([
            "name"=>$GroupName
        ]);
    }

    public function changeJoinPolicy($gid, $JoinPolicy){
        return DB::table("group")->where('gid',$gid)->update([
            "join_policy"=>$JoinPolicy
        ]);
    }

    public function details($gcode)
    {
        $basic_info=DB::table($this->tableName)->where(["gcode"=>$gcode])->first();
        if(empty($basic_info)) return [];
        $basic_info["members"]=$this->countGroupMembers($basic_info["gid"]);
        $basic_info["tags"]=$this->getGroupTags($basic_info["gid"]);
        $basic_info["create_time_foramt"]=date_format(date_create($basic_info["create_time"]), 'M jS, Y');
        $basic_info["contest_stat"]=$this->countGroupContest($basic_info["gid"]);
        return $basic_info;
    }

    public function joinPolicy($gid)
    {
        $ret=DB::table($this->tableName)->where(["gid"=>$gid])->first();
        return empty($ret) ? null : $ret["join_policy"];
    }

    public function userProfile($uid, $gid)
    {
        $info=DB::table("group_member")
        ->join('users','users.id','=','group_member.uid')
        ->where(["gid"=>$gid, "uid"=>$uid])
        ->where("role", ">", 0)
        ->select('avatar','describes','email','gid','uid','name','nick_name','professional_rate','role','sub_group')
        ->first();
        if (!empty($info)) {
            $info["role_parsed"]=$this->role[$info["role"]];
            $info["role_color"]=$this->role_color[$info["role"]];
        }
        return $info;
    }

    public function userList($gid)
    {
        $user_list=DB::table("group_member")->join(
            "users",
            "users.id",
            "=",
            "group_member.uid"
        )->where(["gid"=>$gid])->orderBy('role', 'desc')->select(
            "role",
            "uid",
            "name",
            "nick_name",
            "avatar",
            "sub_group",
            "ranking"
        )->get()->all();
        foreach ($user_list as &$u) {
            $u["role_parsed"]=$this->role[$u["role"]];
            $u["role_color"]=$this->role_color[$u["role"]];
            if(is_null($u["sub_group"])) $u["sub_group"]="None";
        }
        return $user_list;
    }

    public function groupNotice($gid)
    {
        $notice_item=DB::table("group_notice")->where(["gid"=>$gid])->first();
        if (empty($notice_item)) {
            return [];
        }
        $notice_author=DB::table("users")->where(["id"=>$notice_item["uid"]])->first();
        $notice_item["name"]=$notice_author["name"];
        $notice_item["avatar"]=$notice_author["avatar"];
        $notice_item["post_date_parsed"]=$this->formatPostTime($notice_item["post_date"]);
        $notice_item["content_parsed"]=clean(Markdown::convertToHtml($notice_item["content"]));
        return $notice_item;
    }

    public function judgeClearance($gid, $uid)
    {
        $ret=DB::table("group_member")->where(["gid"=>$gid, "uid"=>$uid])->first();
        return empty($ret) ? -3 : $ret["role"];
    }

    public function changeClearance($uid, $gid, $clearance)
    {
        return DB::table("group_member")->where([
            "uid"=>$uid,
            "gid"=>$gid
        ])->update([
            "role"=>$clearance
        ]);
    }

    public function removeClearance($uid, $gid)
    {
        return DB::table("group_member")->where([
            "uid"=>$uid,
            "gid"=>$gid
        ])->delete();
    }

    public function addClearance($uid, $gid, $clearance)
    {
        return DB::table("group_member")->insert([
            "uid"=>$uid,
            "gid"=>$gid,
            "role"=>$clearance,
            "join_time"=>date("Y-m-d H:i:s")
        ]);
    }

    public function isMember($gid, $uid)
    {
        return DB::table("group_member")->where([
            "gid"=> $gid,
            "uid"=> $uid
        ])->where("role", ">", 0)->count();
    }

    public function problemTags($gid,$pid = -1)
    {
        if($pid == -1){
            $tags =  DB::table('group_problem_tag')
            ->select('tag')
            ->where('gid',$gid)
            ->distinct()
            ->get()->all();
        }else{
            $tags =  DB::table('group_problem_tag')
            ->select('tag')
            ->where('gid', $gid)
            ->where('pid', $pid)
            ->distinct()
            ->get()->all();
        }

        $tags_arr = [];
        if(!empty($tags)){
            foreach ($tags as $value) {
                array_push($tags_arr,$value['tag']);
            }
        }
        return $tags_arr;
    }

    public function problems($gid)
    {
        $problems = DB::table('contest_problem')
        ->join('contest','contest_problem.cid', '=', 'contest.cid')
        ->join('problem','contest_problem.pid', '=', 'problem.pid' )
        ->select('problem.pid as pid', 'pcode', 'title')
        ->where('contest.gid',$gid)
        ->where('contest.practice',1)
        ->distinct()
        ->get()->all();
        foreach($problems as &$value){
            $value['tags'] = $this->problemTags($gid,$value['pid']);
        }
        return $problems;
    }

    public function problemAddTag($gid,$pid,$tag)
    {
        return DB::table("group_problem_tag")->insert([
            "gid"=>$gid,
            "pid"=>$pid,
            "tag"=>$tag,
        ]);
    }

    public function problemRemoveTag($gid,$pid,$tag)
    {
        return DB::table("group_problem_tag")->where([
            "gid"=>$gid,
            "pid"=>$pid,
            "tag"=>$tag
        ])->delete();
    }

    public function formatPostTime($date)
    {
        $periods=["second", "minute", "hour", "day", "week", "month", "year", "decade"];
        $lengths=["60", "60", "24", "7", "4.35", "12", "10"];

        $now=time();
        $unix_date=strtotime($date);

        if (empty($unix_date)) {
            return "Bad date";
        }

        if ($now>$unix_date) {
            $difference=$now-$unix_date;
            $tense="ago";
        } else {
            $difference=$unix_date-$now;
            $tense="from now";
        }

        for ($j=0; $difference>=$lengths[$j] && $j<count($lengths)-1; $j++) {
            $difference/=$lengths[$j];
        }

        $difference=round($difference);

        if ($difference!=1) {
            $periods[$j].="s";
        }

        return "$difference $periods[$j] {$tense}";
    }

    public function judgeEmailClearance($gid, $email)
    {
        $user=DB::table("users")->where(["email"=>$email])->first();
        if(empty($user)) return -4;
        $ret=DB::table("group_member")->where([
            "gid"=>$gid,
            "uid"=>$user["id"],
        ])->first();
        return empty($ret) ? -3 : $ret["role"];
    }

    public function inviteMember($gid, $email)
    {
        $uid=DB::table("users")->where(["email"=>$email])->first();
        return DB::table("group_member")->insert([
            "uid"=>$uid["id"],
            "gid"=>$gid,
            "role"=>-1,
            "join_time"=>date("Y-m-d H:i:s")
        ]);
    }

    public function isUser($email)
    {
        return DB::table("users")->where([
            "email"=>$email
        ])->count();
    }

    public function isGroup($gcode)
    {
        return DB::table("group")->where([
            "gcode"=>$gcode,
        ])->count();
    }

    public function createGroup($uid, $gcode, $img, $name, $public, $description, $join_policy)
    {
        $gid=DB::table("group")->insertGetId([
            "gcode"=>$gcode,
            "img"=>$img,
            "name"=>$name,
            "public"=>$public,
            "verified"=>0,
            "description"=>$description,
            "join_policy"=>$join_policy,
            "custom_icon"=>null,
            "custom_title"=>null,
            "create_time"=>date("Y-m-d H:i:s")
        ]);
        return DB::table("group_member")->insert([
            "uid"=>$uid,
            "gid"=>$gid,
            "role"=>3,
            "join_time"=>date("Y-m-d H:i:s")
        ]);
    }

    public function detailNotice($gcode)
    {
        $group=DB::table("group")->where([
            "gcode"=>$gcode,
        ])->first();
        return $group_notice=DB::table("group_notice")->where([
            "gid"=>$group["gid"],
        ])->first();
    }

    public function createNotice($gid, $uid, $title, $content)
    {
        return DB::table("group_notice")->updateOrInsert(
            [
                "gid"=>$gid
            ],
            [
                "uid"=>$uid,
                "title"=>$title,
                "content"=>$content,
                "post_date"=>date("Y-m-d H:i:s"),
            ]);
    }

    public function groupMemberPracticeContestStat($gid)
    {
        $contestModel = new ContestModel();

        $this->rankingUpdate($gid);

        $allPracticeContest = DB::table('contest')
            ->where([
                'gid' => $gid,
                'practice' => 1,
            ])
            ->select('cid','name')
            ->get()->all();
        $user_list = $this->userList($gid);

        $memberData = [];
        foreach ($user_list as $u) {
            $memberData[$u['uid']] = [
                'name' => $u['name'],
                'nick_name' => $u['nick_name'],
                'elo' => $u['ranking'],
                'solved_all' => 0,
                'problem_all' => 0,
                'penalty' => 0,
                'contest_detial' => []
            ];

        }
        foreach ($allPracticeContest as $c) {
            $contestRank = $contestModel->contestRank($c['cid'],0);
            $problemsCount = DB::table('contest_problem')
                ->where('cid',$c['cid'])
                ->count();
            $rank = 0;
            foreach ($contestRank as $cr) {
                $rank++;
                if(in_array($cr['uid'],array_keys($memberData))) {
                    $memberData[$cr['uid']]['solved_all'] += $cr['solved'];
                    $memberData[$cr['uid']]['problem_all'] += $problemsCount;
                    $memberData[$cr['uid']]['penalty'] += $cr['penalty'];
                    $memberData[$cr['uid']]['contest_detial'][$c['cid']] = [
                        'rank' => $rank,
                        'solved' => $cr['solved'],
                        'problems' => $problemsCount,
                        'penalty' => $cr['penalty']
                    ];
                }
            }
        }
        $new_memberData = [];
        foreach ($memberData as $uid => $data) {
            $contest_count = 0;
            $rank_sum = 0;
            foreach ($data['contest_detial'] as $cid => $c) {
                $rank_sum += $c['rank'];
                $contest_count += 1;
            }
            $temp = $data;
            $temp['uid'] = $uid;
            if($contest_count != 0){
                $temp['rank_ave'] = $rank_sum/$contest_count;
            }
            array_push($new_memberData,$temp);
        }
        $ret = [
            'contest_list' => $allPracticeContest,
            'member_data' => $new_memberData
        ];
        return $ret;
    }

    public function groupMemberPracticeTagStat($gid)
    {
        $tags = $this->problemTags($gid);
        $tag_problems = [];

        $user_list = $this->userList($gid);
        foreach ($tags as $tag) {
            $tag_problems[$tag] = DB::table('problem')
                ->join('group_problem_tag','problem.pid','=','group_problem_tag.pid')
                ->where([
                    'group_problem_tag.gid' => $gid,
                    'tag' => $tag
                ])
                ->select('group_problem_tag.pid as pid','pcode','title')
                ->get()->all();
        }
        $all_problems = [];
        foreach ($tag_problems as &$tag_problem_set) {
            foreach ($tag_problem_set as $problem) {
                $all_problems[$problem['pid']] = $problem;
            }
            $tag_problem_set = array_column($tag_problem_set,'pid');
        }
        $submission_data =  DB::table('submission')
            ->whereIn('pid',array_keys($all_problems))
            ->whereIn('uid',array_column($user_list,'uid'))
            ->where('verdict','Accepted')
            ->select('pid','uid')
            ->get()->all();

        $memberData = [];
        foreach ($user_list as $member) {
            $completion = [];
            foreach($tag_problems as $tag => $problems) {
                $completion[$tag] = [];
                foreach ($problems as $problem) {
                    $is_accepted = 0;
                    foreach ($submission_data as $sd) {
                        if($sd['pid'] == $problem && $sd['uid'] == $member['uid']){
                            $is_accepted = 1;
                            break;
                        }
                    }
                    $completion[$tag][$problem] = $is_accepted;
                }
            }
            array_push($memberData,[
                'uid' => $member['uid'],
                'name' => $member['name'],
                'nick_name' => $member['nick_name'],
                'completion' => $completion,
            ]);
        }
        $ret = [
            'all_problems' => $all_problems,
            'tag_problems' => $tag_problems,
            'member_data' => $memberData
        ];
        return $ret;
    }

    public function rankingUpdate($gid)
    {
        $contests = DB::table('contest')
            ->leftJoin('group_rated_change_log','contest.cid','=','group_rated_change_log.cid')
            ->where([
                'contest.gid' => $gid,
                'practice' => 1
            ])->where('end_time','<',date('Y-m-d H:i:s'))
            ->select('contest.cid as cid','group_rated_change_log.cid as cid_rated')
            ->whereNull('group_rated_change_log.cid')
            ->orderBy('end_time')
            ->get()->all();

        if(empty($contests)){
            return true;
        }

        foreach ($contests as $contest) {
            $calc = new GroupRatingCalculator($contest['cid']);
            $calc->calculate();
            $calc->storage();
        }

        return true;
    }
}
