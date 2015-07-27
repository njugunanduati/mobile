<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

use App\Nationality;
use App\ProgramDetails;
use App\SelectProgram;

class ProgramController extends Controller {

    public function getProgram(Request $request)
    {
        $data = $request->all();
        if (!isset($data['nationality']) && !isset($data['country'])) {
            return Response::json(array('status'=>'failure','msg'=>'Enter nationality and country'));
        }
        $nationality = new Nationality;
        $programs = $nationality->fetchPrgDescription($data['nationality'],$data['country']);
        return Response::json(array('status'=>'success','data'=>$programs));
    }

    public function selectProgram(Request $request)
    {
        $data = $request->all();
        
        if (!isset($data['nationality']) || !isset($data['country']) || !isset($data['program_name'])
            || !isset($data['exam_mode']) || !isset($data['id']) || !isset($data['agree'])
            || !isset($data['dual_specialization'])) {
            return Response::json(array('status'=>'failure','msg'=>'Enter correct information'));
        }

        // get region of a user
        $nationality = new Nationality;
        $region = $nationality->getStudentType($data['nationality'],$data['country']);
        $program = SelectProgram::where('registration_id','=',$data['id'])->first();
        if (!$program)
            $program = new SelectProgram;
        else {
            if ($program->read_only == 'True')
                return Response::json(array("status"=>"failure","msg"=>"Already applied for program"));
        }
        $program->fill($data);
        $program->registration_id = $data['id'];
        $program->student_region = $region;
        if ($program->save()) {
            return Response::json(array('status'=>'success','msg'=>'Data has been saved successfully','id'=>$data['id']));
        }
        else{
            return Response::json(array('status'=>'failure','msg'=>'Problem in saving data'));
        }
    }

    public function fetchProgramDetails(Request $request)
    {
        $data = $request->all();
        
        if (!isset($data['id']))
            return Response::json(array("status"=>"failure","msg"=>"Missing Argument"));
        $programs = SelectProgram::where('registration_id','=',$data['id'])->first();
        
        if (!$programs)
            return Response::json(array("status"=>"failure","msg"=>"No data is present"));
        $array = array();
        $array['program_name'] = $programs->program_name;
        $array['dual_specialization'] = $programs->dual_specialization;
        $array['nationality'] = $programs->nationality;
        $array['country'] = $programs->country;
        $array['student_region'] = $programs->student_region;
        $array['agree'] = $programs->agree;
        $array['exam_mode'] = $programs->exam_mode;
        return Response::json(array("status"=>"success","msg"=>$array));
    }

}
