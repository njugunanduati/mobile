<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

use App\EmployementDocumentUpload;
use App\Employability;


class EmploymentDocumentUploadController extends Controller {

    public function index(Request $request)
    {
        $data = $request->all();
        if (!isset($data['id']))
            return Response::json(array('status' => 'failure', 'msg' => 'Invalid argument'));
        $employData = EmployementDocumentUpload::where('registration_id','=',$data['id'])->first();
        if(!$employData)
            return Response::json(array("status"=>"failure","msg"=>"No data present"));
        
        $userData["id"] = $data["id"];
        $userData["first_document"] = '';
        $userData["second_document"] = '';
        $userData["third_document"] = '';
        $userData["fourth_document"] = '';

        if ($employData->first_document !="") {
            $image =  base64_encode(file_get_contents($data["id"]."/".$employData->first_document));
            $companyName = $employData["firstDoc_type"];
            $array = array("name"=>$companyName, "image"=>$image);
            $userData["first_document"] = $array;
        }

        if ($employData->second_document !="") {
            $secImage =  base64_encode(file_get_contents($data["id"]."/".$employData->second_document));
            $secCompanyName = $employData["secondDoc_type"];
            $secArray = array("name"=>$secCompanyName, "image"=>$secImage);
            $userData["second_document"] = $secArray;
        }

        if ($employData->third_document!="") {
            $thirdImage =  base64_encode(file_get_contents($data["id"]."/".$employData->third_document));
            $thirdCompanyName = $employData["thirdDoc_type"];
            $thirdArray = array("name"=>$thirdCompanyName, "image"=>$thirdImage);
            $userData["third_document"] = $thirdArray;
        }

        if ($employData->fourth_document!="") {
            $fourthImage =  base64_encode(file_get_contents($data["id"]."/".$employData->fourth_document));
            $fourthCompanyName = $employData["fourthDoc_type"];
            $fourthArray = array("name"=>$fourthCompanyName, "image"=>$fourthImage);
            $userData["fourth_document"] = $fourthArray;
        }
    
        return Response::json(array("status"=>"success","msg"=>$userData));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {

    }


    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    // function to generate random string
    public function generateRandomString($length = 5)
    {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        if (!isset($data['id']))
            return Response::json(array('status'=>'failure','msg'=>'Missing argument'));
        $employData = EmployementDocumentUpload::where('registration_id','=',$data['id'])->first();
        if (!$employData) {
            $employData = new EmployementDocumentUpload;
            $employData->registration_id = $data['id'];
        }

        $imageName = array();
        
        if ($data['first_document']!=null) {
            $imageName = $this->generateRandomString(4);
            $firstUploadArray = json_decode($data['first_document']);
            $employData->firstDoc_type = $firstUploadArray->name;
            $employData->first_document = "first_document_".$firstUploadArray->name."_".$imageName;
            $uploadImageArray[0] = base64_decode($firstUploadArray->image); 
        }
    
        if ($data['second_document']!=null) {
            $imageName = $this->generateRandomString(4);
            $firstUploadArray = json_decode($data['second_document']);
            $employData->secondDoc_type = $firstUploadArray->name;
            $employData->second_document = "second_document_".$firstUploadArray->name."_".$imageName;
            $uploadImageArray[1] = base64_decode($firstUploadArray->image);
        }
        if ($data['third_document']!=null) {
            $imageName = $this->generateRandomString(4);
            $firstUploadArray = json_decode($data['third_document']);
            $employData->thirdDoc_type = $firstUploadArray->name;
            $employData->third_document = "third_document_".$firstUploadArray->name."_".$imageName;
            $uploadImageArray[2] = base64_decode($firstUploadArray->image); 
        }
        if ($data['fourth_document']!=null) {
            $imageName = $this->generateRandomString(4);
            $firstUploadArray = json_decode($data['fourth_document']);
            $employData->fourthDoc_type = $firstUploadArray->name;
            $employData->fourth_document = "fourth_document_".$firstUploadArray->name."_".$imageName;
            $uploadImageArray[3] = base64_decode($firstUploadArray->image); 
        }
        
        if (!$employData->save())
            return Response::json(array('status'=>'failure','msg'=>'Problem in uploading document'));

        $firstDoc = 1;
        $secondDoc = 1;
        $thirdDoc = 1;
        $fourthDoc = 1;
        if ($data['first_document'] != null){
            $check = $this->checkAndRemoveFile('first_document',$data['id']);
            $firstDoc = file_put_contents($data['id']."/".$employData->first_document,$uploadImageArray[0]);
        }
        if ($data['second_document']!=null) {
            $check = $this->checkAndRemoveFile('second_document',$data['id']);
            $secondDoc = file_put_contents($data['id']."/".$employData->second_document,$uploadImageArray[1]);
        }
        if ($data['third_document']!=null){
            $check = $this->checkAndRemoveFile('third_document',$data['id']);
            $thirdDoc = file_put_contents($data['id']."/".$employData->third_document,$uploadImageArray[2]);
        }
        if ($data['fourth_document']!=null){
            $check = $this->checkAndRemoveFile('fourth_document',$data['id']);
            $fourthDoc = file_put_contents($data['id']."/".$employData->fourth_document,$uploadImageArray[3]);
        }

        if ($firstDoc && $secondDoc && $thirdDoc && $fourthDoc) {
            return Response::json(array('status'=>'success','msg'=>'File uploaded successfully'));
        }
        return Response::json(array('status'=>'failure','msg'=>'Problem in saving data'));
    }

    public function checkAndRemoveFile($document,$id)
    {
        $files = scandir($id, 1);
        $count = 0;
        $removeCounter = 0;
        foreach ($files as $key => $value) {
            if (preg_match("/".$document."*/", $value)){
                $count++;
                if(unlink($id."/".$value))
                    $removeCounter++;
            }
        }
        if ($count == $removeCounter)
            return true;
        return false;
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $employData = Employability::where('registration_id','=',$id)->get();
        $data = array();
        foreach ($employData as $key => $value) {
            $data[$value->company_name] = $value->company_name;
        }
        return Response::json(array('status'=> 'success', "data"=>$data));
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
    public function destroy(Request $request, $id)
    {
        $data = $request->all();
        if (!isset($data['document']))
            return Response::json(array('status' => 'failure', 'msg'=> 'Invalid Argument'));
        $employData = EmployementDocumentUpload::where('registration_id','=',$id)->first();

        if (!$employData)
            return Response::json(array('status'=>'failure','msg'=>'No record is present'));

        if ($data['document'] == 'first_document'){
            if (file_exists($id."/".$employData->$data['document']))
                unlink($id."/".$employData->$data['document']);
            $employData->$data['document'] = null;
            $employData->firstDoc_type = null;
            
        }
        if ($data['document'] == 'second_document'){
            if (file_exists($id."/".$employData->$data['document']))
                unlink($id."/".$employData->$data['document']);
            $employData->$data['document'] = null;
            $employData->secondDoc_type = null;
        }
        if ($data['document'] == 'third_document'){
            if (file_exists($id."/".$employData->$data['document']))
                unlink($id."/".$employData->$data['document']);
            $employData->$data['document'] = null;
            $employData->thirdDoc_type = null;
        }
        if ($data['document'] == 'fourth_document'){
            if (file_exists($id."/".$employData->$data['document']))
                unlink($id."/".$employData->$data['document']);
            $employData->$Data['document'] = null;
            $employData->fourthDoc_type = null;
        }

        if ($employData->save())
            return Response::json(array('status' => 'success', 'msg' => 'Data deleted'));
        return Response::json(array('status' => 'failure', 'msg' => 'Problem in deleting data'));
    }
    
}
