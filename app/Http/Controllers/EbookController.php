<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\curl\Curl;

use App\RegistrationDetails;


class EbookController extends Controller {

    public function programDetail($id)
    {
        $regDetails = RegistrationDetails::find($id);
        if (!$regDetails)
            return Response::json(array('status'=>'failure','msg'=>'No data present'));
        $username = $regDetails->user_name;

        // check user application is approved or not
        $status = Curl::postCurl($_ENV['core'].'/getForEbookStatus/'.$username);
        $status = json_decode(json_encode($status), true);

        if (isset($status['error']))
            return Response::json(array('status' => 'failure','msg' => $status['error']));

        $coreApplicantDetails = Curl::postCurl($_ENV['core'].'/listApplicantDetailsCore/'.$username);
        $coreApplicantDetails = json_decode(json_encode($coreApplicantDetails), true);
        
        $details = array();
        $details[0]['reg_id'] = "UOM_".$status['success']['id'];
        if (isset($coreApplicantDetails['success']['Data']['0']['Program Details'][0]['program_name'])) {
            $programId = $coreApplicantDetails['success']['Data'][0]['Program Details'][0]['program_name'];
                
            $data = Curl::postCurl($_ENV['core'].'/listAvailableProgram/'.$programId);
            $data = json_decode(json_encode($data), true);
            if(isset($data)) {
                $programDetails = $data['success']['Data'];
                foreach ($programDetails as $key => $value) {
                    $programName = $value['program_name'];
                    $desc = $value['program_description'];
                }
            }
            $details[0]['programId'] = $programId;
            $details[0]['programName'] = $programName;
            $details[0]['programDesc'] = $desc;
        }
        $courseDetails = Curl::postCurl($_ENV['core'].'/listAvailableCourse/'.$programId);
        $courseDetails = json_decode(json_encode($courseDetails), true);
        $courseDetails = $courseDetails['success']['Data'];
        $courses = array();
        foreach ($courseDetails as $key => $value) {
            $courseArray['course_semester'] = $value['course_semester'];
            $courseArray['course_name'] = $value['course_name'];
            $courses[] = $courseArray;
        }
        // if (count($courses)>0)
        //  $details[0]['course'] = $courses;
        
        return Response::json(array('status'=>'success','data'=>$details, 'course'=>$courses));
    }

    public function getCourses($programId,$regId)
    {
        $data = Curl::postCurl($_ENV['ebook'].'/getCourseDetails/'.$programId.'/'.$regId);
        return Response::json(array('status'=>'success','data'=>$data));
    }
}
