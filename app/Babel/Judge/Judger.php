<?php

namespace App\Babel\Judge;

use App\Models\SubmissionModel;
use App\Models\JudgerModel;
use App\Models\ContestModel;
use App\Babel\Submit\Curl;
use Auth;
use Requests;
use Exception;
use Log;

class Judger extends Curl
{
    public $data=null;
    private $judger=[];
    public $ret=[];

    /**
     * Initial
     *
     * @return Response
     */
    public function __construct()
    {
        $submissionModel=new SubmissionModel();

        $result=$submissionModel->getWaitingSubmission();
        foreach ($result as $row) {
            $ocode=$row["ocode"];
            if(!isset($this->judger[$ocode]) || is_null($this->judger[$ocode])) {
                $this->judger[$ocode]=self::create($ocode);
            }
            $this->judger[$ocode]->judge($row);
        }
    }

    public static function create($ocode) {
        $name=$ocode;
        $judgerProvider="Judger";
        try {
            $BabelConfig=json_decode(file_get_contents(babel_path("Extension/$ocode/babel.json")), true);
            $judgerProvider=$BabelConfig["provider"]["judger"];
        } catch(ErrorException $e) {
        } catch(Exception $e) {
        }
        $className = "App\\Babel\\Extension\\$name\\$judgerProvider";
        if(class_exists($className)) {
            return new $className();
        } else {
            return null;
        }
    }
}
