<?php

namespace App\Http\Controllers\Ajax;

use App\Models\ProblemModel;
use App\Models\SubmissionModel;
use App\Models\ResponseModel;
use App\Models\CompilerModel;
use App\Babel\Babel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Jobs\ProcessSubmission;
use Illuminate\Support\Facades\Validator;
use Auth;

class ProblemController extends Controller
{
    /**
     * The Ajax Problem Solution Submit.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function submitSolution(Request $request)
    {
        $problemModel=new ProblemModel();
        $submissionModel=new SubmissionModel();
        $compilerModel=new CompilerModel();

        $all_data=$request->all();

        $validator=Validator::make($all_data, [
            'solution' => 'required|string|max:65535',
        ]);

        if ($validator->fails()) {
            return ResponseModel::err(3002);
        }
        if (!$problemModel->ojdetail($problemModel->detail($problemModel->pcode($all_data['pid']))['OJ'])['status']) {
            return ResponseModel::err(6001);
        }
        if ($problemModel->isBlocked($all_data["pid"], isset($all_data["contest"]) ? $all_data["contest"] : null)) {
            return header("HTTP/1.1 403 Forbidden");
        }

        $lang=$compilerModel->detail($all_data["coid"]);

        $sid=$submissionModel->insert([
            'time'=>'0',
            'verdict'=>'Pending',
            'solution'=>$all_data["solution"],
            'language'=>$lang['display_name'],
            'submission_date'=>time(),
            'memory'=>'0',
            'uid'=>Auth::user()->id,
            'pid'=>$all_data["pid"],
            'remote_id'=>'',
            'coid'=>$all_data["coid"],
            'cid'=>isset($all_data["contest"]) ? $all_data["contest"] : null,
            'vcid'=>isset($all_data["vcid"]) ? $all_data["vcid"] : null,
            'jid'=>null,
            'score'=>0
        ]);

        $all_data["sid"]=$sid;
        $all_data["oj"]=$problemModel->ocode($all_data["pid"]);
        $all_data["lang"]=$lang['lcode'];
        dispatch(new ProcessSubmission($all_data))->onQueue($all_data["oj"]);

        return ResponseModel::success(200, null, [
            "sid"=>$sid
        ]);
    }
    /**
     * The Ajax Problem Status Check.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function problemExists(Request $request)
    {
        $all_data=$request->all();
        $problemModel=new ProblemModel();
        $pcode=$problemModel->existPCode($all_data["pcode"]);
        if ($pcode) {
            return ResponseModel::success(200, null, [
                "pcode"=>$pcode
            ]);
        } else {
            return ResponseModel::err(3001);
        }
    }
    /**
     * The Ajax Problem Solution Discussion Submission.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function submitSolutionDiscussion(Request $request)
    {
        $all_data=$request->all();
        $problemModel=new ProblemModel();
        $pid=$all_data["pid"];
        $content=$all_data["content"];
        $basic=$problemModel->basic($pid);
        if (empty($basic)) {
            return ResponseModel::err(3001);
        }
        $ret=$problemModel->addSolution($pid,Auth::user()->id,$content);
        return $ret?ResponseModel::success(200):ResponseModel::err(3003);
    }
    /**
     * The Ajax Problem Solution Discussion Update.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function updateSolutionDiscussion(Request $request)
    {
        $all_data=$request->all();
        $problemModel=new ProblemModel();
        $psoid=$all_data["psoid"];
        $content=$all_data["content"];
        $ret=$problemModel->updateSolution($psoid,Auth::user()->id,$content);
        return $ret?ResponseModel::success(200):ResponseModel::err(3004);
    }
    /**
     * The Ajax Problem Solution Discussion Delete.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function deleteSolutionDiscussion(Request $request)
    {
        $all_data=$request->all();
        $problemModel=new ProblemModel();
        $psoid=$all_data["psoid"];
        $ret=$problemModel->removeSolution($psoid,Auth::user()->id);
        return $ret?ResponseModel::success(200):ResponseModel::err(3004);
    }
    /**
     * The Ajax Problem Solution Discussion Vote.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function voteSolutionDiscussion(Request $request)
    {
        $all_data=$request->all();
        $problemModel=new ProblemModel();
        $psoid=$all_data["psoid"];
        $type=$all_data["type"];
        $ret=$problemModel->voteSolution($psoid,Auth::user()->id,$type);
        return $ret["ret"]?ResponseModel::success(200,null,["votes"=>$ret["votes"],"select"=>$ret["select"]]):ResponseModel::err(3004);
    }
    /**
     * The Ajax Problem Solution Submit.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function downloadCode(Request $request)
    {
        $all_data=$request->all();
        $submissionModel=new SubmissionModel();
        $sid=$all_data["sid"];
        $downloadFile=$submissionModel->downloadCode($sid, Auth::user()->id);
        if (empty($downloadFile)) {
            return ResponseModel::err(2001);
        }
        return response()->streamDownload(function() use ($downloadFile) {
            echo $downloadFile["content"];
        }, $downloadFile["name"]);
    }
    /**
     * The Ajax Problem Judge.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function judgeStatus(Request $request)
    {
        $all_data=$request->all();
        $submission=new SubmissionModel();
        $status=$submission->getJudgeStatus($all_data["sid"], Auth::user()->id);
        return ResponseModel::success(200, null, $status);
    }

    /**
     * The Ajax Problem Manual Judge.
     * [Notice] THIS FUNCTION IS FOR TEST ONLY
     * SHALL BE STRICTLY FORBIDDEN UNDER PRODUCTION ENVIRONMENT.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function manualJudge(Request $request)
    {
        if (Auth::user()->id!=1) {
            return ResponseModel::err(2001);
        }

        $babel=new Babel();
        $vj_judge=$babel->judge();

        return ResponseModel::success(200, null, $vj_judge->ret);
    }

    /**
     * Get the Submit History.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function submitHistory(Request $request)
    {
        $all_data=$request->all();
        $submission=new SubmissionModel();
        if (isset($all_data["cid"])) {
            $history=$submission->getProblemSubmission($all_data["pid"], Auth::user()->id, $all_data["cid"]);
        } else {
            $history=$submission->getProblemSubmission($all_data["pid"], Auth::user()->id);
        }

        return ResponseModel::success(200, null, ["history"=>$history]);
    }
}
