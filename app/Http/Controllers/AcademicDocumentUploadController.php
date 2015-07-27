<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

use App\AcademicDocumentUpload;
use App\RegistrationDetails;

class AcademicDocumentUploadController extends Controller {

    public function index(Request $request)
    {
        $data = $request->all();

        $acdemicsData = AcademicDocumentUpload::where('registration_id','=',$data['id'])->first();
        if(!$acdemicsData)
            return Response::json(array("status"=>"failure","msg"=>"No data present"));

        $tenDoc = array();
        $twelveDoc = array();
        $gradDoc = array();
        $otherDoc1 = array();
        $otherDoc2 = array();
        $allDoc = array();

        if ($acdemicsData->x_document){
            $tenDoc = $this->getString($acdemicsData->x_document,$data['id'],'x_document');
        }

        if ($acdemicsData->xii_document){
            $twelveDoc = $this->getString($acdemicsData->xii_document,$data['id'],'xii_document');
        }
        if ($acdemicsData->graduation_document){
            $gradDoc = $this->getString($acdemicsData->graduation_document,$data['id'],'graduation_document');
        }
        if ($acdemicsData->other_document1){
            $otherDoc1 = $this->getString($acdemicsData->other_document1,$data['id'],'other_document1');
        }
        if ($acdemicsData->other_document2){
            $otherDoc2 = $this->getString($acdemicsData->other_document2,$data['id'],'other_document2');
        }
        $allDoc = array_merge($tenDoc,$twelveDoc,$gradDoc,$otherDoc1,$otherDoc2);

        return Response::json(array("status"=>"success","msg"=>$allDoc));

    }

    public function getString($document,$id,$type)
    {
        $finalData = array();
        if ($document) {
            // split string by |

            $tenDocument = explode('|',$document);
            foreach ($tenDocument as $key => $value) {

                $array = explode("_",$value);
                if (!isset($array[2])) {
                    continue;
                }
                $typeOfDoc = $array[2];
                $returnData['document'] = $type;
                $returnData['type'] = $typeOfDoc;
                $returnData['image'] = base64_encode(file_get_contents($id."/".$tenDocument[$key]));
                $finalData[] = $returnData;
            }
        }
        
        return $finalData;
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
        
        if (!isset($data['id']) || !isset($data['data']))
            return Response::json(array('status' => 'failure', 'msg' => 'Invalid arguments'));
        $regId = $data['id'];

        // check whether the user is presnt or not
        $userDetails = RegistrationDetails::find($regId);

        if (!$userDetails)
            return Response::json(array('status' => 'failure', 'msg' => 'User is not present in the system'));

        $academicData = json_decode($data['data']);

        $academic = AcademicDocumentUpload::where('registration_id','=',$regId)->first();
        if (!$academic) {
            $academic = new AcademicDocumentUpload;
            $academic->registration_id = $regId;
        }
        $tenDocumentString = '';
        
        foreach ($academicData as $k=>$v) {
            $string = $this->generateDocumentString($v->document,$v->type,$v->image);
            $documentString = $string['string'];
            $imageUploadArray[$k] = $string['image'];
            if ($v->document == 'x_document'){
                $academic->x_document = $academic->x_document."|".$documentString;
            }
            if ($v->document == 'xii_document'){
                $academic->xii_document = $academic->xii_document."|".$documentString;
            }
            if ($v->document == 'graduation_document'){
                $academic->graduation_document = $academic->graduation_document."|".$documentString;
            }
            if ($v->document == 'other_document1'){
                $academic->other_document1 = $academic->other_document1."|".$documentString;
            }
            if ($v->document == 'other_document2'){
                $academic->other_document2 = $academic->other_document2."|".$documentString;
            }
        }
        
        if (!$academic->save()) {
            return Response::json(array('status'=>'failure','msg'=>'Problem in uploading document'));
        }
        // save the images at server
        foreach ($imageUploadArray as $key => $value) {
            $multi = $this->checkMultiDimArray($value);
            if ($multi) {
                foreach ($value as $k => $v) {
                    if (file_put_contents($regId.'/'.$v['name'],$v['value'])) {
                        continue;
                    }
                    else {
                        return Response::json(array('status'=>'failure','msg'=>'Problem in saving document'));      
                    }
                }
            }
            else {
                if (file_put_contents($regId.'/'.$value['name'],$value['value'])) {
                    continue;
                }
                else {
                    return Response::json(array('status'=>'failure','msg'=>'Problem in saving document'));      
                }
            }       
        }
        return Response::json(array('status'=>'success','msg'=>'Document uploaded successfully'));
    }


    public function checkMultiDimArray($array)
    {
        foreach ($array as $k => $v) {
            if (is_int($k)){
                return true;
            }
            else {
                return false;
            }
        }
    }
    
    public function show($id)
    {
    }

    // function to generate string for saving document
    public function generateDocumentString($document,$type,$imageData)
    {
        $documentString = '';
        $imageArray = array();

        if (is_array($imageData)) {
            $count = count($imageData);
            $counter = 0;
            foreach ($imageData as $key => $value) {
                $counter++;
                $image = base64_decode($value);
                $imageName = $this->generateRandomString(4);
                $documentString .= $document.'_'.$type.'_'.$imageName;
                $imageArray[$key]['name'] = $document.'_'.$type.'_'.$imageName;
                $imageArray[$key]['value'] = $image;
                if ($counter != $count)
                    $documentString .= '|';
            }           
        }
        else {
            $image = base64_decode($imageData);
            $imageName = $this->generateRandomString(4);
            $documentString .= $document.'_'.$type.'_'.$imageName;          
            $imageArray['name'] = $documentString;
            $imageArray['value'] = $image;
        }
        $array['image'] = $imageArray;
        $array['string'] = $documentString;

        return $array;
    }

    // function to generate random string
    public function generateRandomString($length = 5)
    {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
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
        $academic = AcademicDocumentUpload::where('registration_id','=',$id)->first();
        if (!$academic)
            return Response::json(array('status'=>'failure','msg'=>'No record is present'));
        if ($academic->$data['document']) {
            $document = explode('|',$academic->$data['document']);
            foreach ($document as $key => $value) {
                $array = explode("_",$value);
                if (!isset($array[2])) {
                    continue;
                }
                if (file_exists($id."/".$value))
                    unlink($id."/".$value);
            }
            $academic->$data['document'] = null;
        }
        if ($academic->save())
            return Response::json(array('status' => 'success', 'msg' => 'Data deleted'));
        return Response::json(array('status' => 'failure', 'msg' => 'Problem in deleting data'));
    }

}