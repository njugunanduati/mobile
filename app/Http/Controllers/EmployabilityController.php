<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

use App\Employability;
use App\RegistrationDetails;


class EmployabilityController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
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

        if (!isset($data['id'])) {
            return Response::json(array('status'=>"failure","msg"=>"Incorrect Information"));
        }

        $userDetails = RegistrationDetails::find($data['id']);
        if (!$userDetails) {
            return Response::json(array('status'=>"failure","msg"=>"Wrong id"));
        }

        $empDetail=Employability::where('registration_id','=',$data['id'])->first();
        if (!$empDetail) {
            $empDetail = new Employability;
            $empDetail->registration_id=$data['id'];
        }
        else{
            // delete the details if they are already present
            // then insert the new entries
            $deleteDetails = Employability::where('registration_id','=',$data['id'])->delete();
            $empDetail = new Employability;
            $empDetail->registration_id=$data['id'];
        }
        if ($data['company1'] != null) {
            $empDetail->work_experience_year=$data['work_experience_year'];
            $empDetail->work_experience_month=$data['work_experience_month'];
            $empDetail->fill(json_decode($data['company1'],true));

            if (!$empDetail->save()) {
                return Response::json(array('status'=>'failure','msg'=>'Problem in saving data'));
            }
        }

        if ($data['company2'] != null) {
            $empDetail = new Employability;
            $empDetail->registration_id = $data['id'];
            $empDetail->work_experience_year = $data['work_experience_year'];
            $empDetail->work_experience_month = $data['work_experience_month'];
            $empDetail->fill(json_decode($data['company2'],true));
            if (!$empDetail->save()) {
                return Response::json(array('status'=>'failure','msg'=>'Problem in saving data'));
            }
        }
        if ($data['company3'] != null) {
            $empDetail = new Employability;
            $empDetail->registration_id = $data['id'];
            $empDetail->work_experience_year = $data['work_experience_year'];
            $empDetail->work_experience_month = $data['work_experience_month'];
            $empDetail->fill(json_decode($data['company3'],true));
            if (!$empDetail->save()) {
                return Response::json(array('status'=>'failure','msg'=>'Problem in saving data'));
            }
        }
        if ($data['company4'] != null) {
            $empDetail = new Employability;
            $empDetail->registration_id = $data['id'];
            $empDetail->work_experience_year = $data['work_experience_year'];
            $empDetail->work_experience_month = $data['work_experience_month'];
            $empDetail->fill(json_decode($data['company4'],true));
            if (!$empDetail->save()) {
                return Response::json(array('status'=>'failure','msg'=>'Problem in saving data'));
            }
        }
        return Response::json(array('status'=>'success','msg'=>'Data has been saved successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $employDetails=Employability::where('registration_id','=',$id)->get();

        if(count($employDetails)==0){
            return Response::json(array('status'=>"failure","msg"=>"No data is present"));
        }

        $count = count($employDetails);
        $details = array();
        $details['id'] = $id;
        for($i = $count-1; $i>=0; $i--) {
            // echo $employDetails[$i]->id;
            // die;
            //unset($employDetails[$i]->id);
            unset($employDetails[$i]->registration_id);
            unset($employDetails[$i]->updated_at);
            unset($employDetails[$i]->created_at);
            unset($employDetails[$i]->read_only);
            $details['work_experience_year'] = $employDetails[$i]->work_experience_year;
            $details['work_experience_month'] = $employDetails[$i]->work_experience_month;
            unset($employDetails[$i]->work_experience_month);
            unset($employDetails[$i]->work_experience_year);
            $j = $i+1;
            $details['company'.$j] = $employDetails[$i];
        }

        return Response::json(array('status'=>'success','msg'=>$details));
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
        $data = Employability::find($id);
        if ($data->delete())
            return Response::json(array('status'=>'success','msg'=>'Record deleted successfully'));
        return Response::json(array('status'=>'success','msg'=>'Problem in deleting data'));

    }

}
