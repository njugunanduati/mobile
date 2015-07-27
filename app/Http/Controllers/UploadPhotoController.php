<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\PersonalDetails;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;


class UploadPhotoController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $data = $request->all();
        if(!isset($data['id'])){
            return Response::json(array('status'=>"failure","msg"=>"Incorrect Information"));
        }
        $userDetails = PersonalDetails::where('registration_id','=',$data['id'])->first();
        if(!$userDetails){
            return Response::json(array('status'=>"failure","msg"=>"Please enter correct Id"));
        }
        $returnData['user_photo'] = null;
        $returnData['user_idproof'] = null;
        $returnData['idproof_type'] = null;
        if ($userDetails->user_photo)
            $userPhoto = base64_decode($data['id'].'/'.$userDetails->user_photo);
        if ($userDetails->user_idproof)
            $idProof = base64_decode($data['id'].'/'.$userDetails->user_idproof);
        if ($userDetails->idproof_type)
            $idproofType = $data['idproof_type'];
        return Response::json(array('status'=>'failure','msg'=>$returnData));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        if (!isset($data['id']) || !isset($data['user_photo']) ||
            !isset($data['user_idproof']) || !isset($data['idproof_type']) ) {
                return Response::json(array('status'=>'failure','message'=>'Argument missing'));
        }
        $id = $data['id'];
        $userPhoto = base64_decode($data['user_photo']);
        $idProof = base64_decode($data['user_idproof']);
        $idproofType = $data['idproof_type'];

        $photo = file_put_contents($id.'/user_photo',$userPhoto);
        $idProg = file_put_contents($id.'/idProof',$idProof);

        if ($photo && $idProg) {
            // save tha data
            $personalDetails = PersonalDetails::where('registration_id','=',$id)->first();
            if (!$personalDetails)
                $personalDetails = new PersonalDetails;
            $personalDetails->registration_id = $id;
            $personalDetails->user_photo = 'user_photo';
            $personalDetails->user_idproof = 'idproof_type';
            $personalDetails->idproof_type = $idproofType;
            if (!$personalDetails->save())
                return Response::json(array('status'=>'failure','msg'=>'Problem in saving data'));
            return Response::json(array('status'=>'success','msg'=>'Data saved successfully'));
        }
        else {
            return Response::json(array('status'=>'failure','msg'=>'Problem in saving files'));
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $personalData = PersonalDetails::where('registration_id','=',$id)->first();
        $personalData->idproof_type = null;
        $personalData->user_idproof = null;
        if (file_exists($id."/idProof"))
            unlink($id."/idProof");
        if ($personalData->save())
            return Response::json(array('status' => 'success', 'msg' => 'Data deleted successfully'));
        return Response::json(array('status' => 'failure', 'msg' => 'Problem in deleting data'));
    }

}
