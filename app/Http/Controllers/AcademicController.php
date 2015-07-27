<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

use App\RegistrationDetails;
use App\Academics;
use App\Employability;

class AcademicController extends Controller {

    public function fillAcademicDetails(Request $request)
    {
        $data = $request->all();
        if(!isset($data['id'])){
            return Response::json(array('status'=>"failure","msg"=>"Incorrect Information"));
        }
        $userDetails=RegistrationDetails::find($data['id']);
        if(!$userDetails){
            return Response::json(array('status'=>"failure","msg"=>"Wrong id"));
        }
        $AcadDetail=Academics::where('registration_id','=',$data['id'])->first();
        if(!$AcadDetail){
            $AcadDetail=new Academics;
            $AcadDetail->registration_id=$data['id'];
        }
        $AcadDetail->fill($data);
        if ($AcadDetail->save()) {
            return Response::json(array('status'=>'success','msg'=>'Data has been saved successfully','data'=>$AcadDetail));
        }
        return Response::json(array('status'=>'failure','msg'=>'Problem in saving data'));
    }

    public function fetchAcademicDetails(Request $request)
    {
        $data=$request->all();

        if(!isset($data['id'])){
            return Response::json(array('status'=>"failure","msg"=>"Incorrect Information"));
        }
        $acadDetails=Academics::where('registration_id','=',$data['id'])->first();
        if(!$acadDetails){
            return Response::json(array('status'=>"failure","msg"=>"No data is present"));
        }
        unset($acadDetails['id']);
        unset($acadDetails['registration_id']);
        unset($acadDetails['updated_at']);
        unset($acadDetails['created_at']);
        unset($acadDetails['read_only']);

        return Response::json(array('status'=>'success','msg'=>$acadDetails));
    }

}
