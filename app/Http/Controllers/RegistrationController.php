<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Response;

// to include curl
use App\curl\Curl;
// to include crypt class
use Illuminate\Support\Facades\Crypt;
// to include redirect class
use Illuminate\Http\RedirectResponse;

// model namespace
use App\RegistrationDetails;
use App\PersonalDetails;
use App\Country;
use App\SelectProgram;
use App\ProgramDetails;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Aloha\Twilio\Facades\Twilio;
use Illuminate\Support\Facades\App;

class RegistrationController extends Controller {

    public function register(Request $request)
    {
            try{
                $data = $request->all();
                if (empty($data)) {
                    $response = array("status"=>"failure","msg"=>"Please enter details","id"=>0);
                    return Response::json($response);
                      
                }
                else {
                    if (!isset($data['user_name']) || !isset($data['user_email']) || 
                            !isset($data['user_password']) || !isset($data['phone_no'])) {
                        $response = array("status"=>"failure","msg"=>"Please enter correct information","id"=>0);
                        return Response::json($response);
                    }
                    $response = RegistrationDetails::where('user_name','=',$data['user_name'])
                                ->where('user_email','=',$data['user_email'])->count();
                    if ($response) {
                       return Response::json(array("status"=>"failure","msg"=>"User already registered","id"=>0));
                    }
                    else {
                        // THE CURL FUNCTION FOR UAUTH SAVE
                        // ================================
                        $status = array();
                        $data['user_password'] = Hash::make($data['user_password']);
                        $createdDate =  date('Y-m-d H:i:s',time('now'));
                        $insertArray = array("registration_type"=>"D","referrer_id"=>0,"referrer_type"=>"sales","created_at"=>$createdDate);
                        $newArray = array_merge($data,$insertArray);
                        $status = Curl::postCurl($_ENV['uauth'].'/registrationPre',$newArray);
                       
                        if (isset($status->success)) {
                            // insert data
                            $user = new RegistrationDetails;
                            $user->user_name = $data['user_name'];
                            $user->user_email = $data['user_email'];
                            $user->user_password = $data['user_password'];
                            $user->phone_no = $data['phone_no'];
                            $user->registration_type = 'D';
                            if ($user->save()) {
                                $path = public_path().'/'.$user->id;
                                File::makeDirectory($path, $mode = 0777, true, true);

                                //Send email to the newly registered user
                                $mailData = array();
                                $mailData['user_name'] = $data['user_name'];
                                $mailData['user_email'] = $data['user_email'];
                                $encrptedId = Crypt::encrypt($user->id);
                                $mailData['url'] =  $_ENV['app_url']."/verifyEmail/$encrptedId";
                                //sending the email to the user
                                $sendData = array();
                                $sendData['username'] = $data['user_name'];
                                $sendData['email'] = $data['user_email'];

                                Mail::send('emails.emailVerification' ,$mailData, function($message) use ($sendData){
                                    $message->to($sendData['email'], $sendData['username'])
                                    ->subject('Registration Success');
                                });
                                return  Response::json(array("status"=>"success","msg"=>"User registered successfully","id"=>$user->id));
                            }
                            else {
                                return Response::json(array("status"=>"failure","msg"=>"Problem in regsitration","id"=>0));
                            }
                        }
                        else{
                            return Response::json(array("status"=>"failure","msg"=>$status->error,"id"=>0));
                        }
                    }
                }
        }
        catch (Exception $e){
            return $e;
        }
    }

    public function fillDetails(Request $request)
    {
        $data = $request->all();
        
        if ($data == null) {
            return Response::json(array('status'=>'failure','msg'=>'Please enter details','id'=>0));
        }
        else {
            if (!isset($data['country']) || !isset($data['phone_no']) || 
                    !isset($data['first_name']) || !isset($data['last_name']) ||
                    !isset($data['dob']) || !isset($data['employee']) || 
                        !isset($data['nationality']) || !isset($data['gender'])||
                        !isset($data['id'])) {
                $response = array("status"=>"failure","msg"=>"Please enter correct info","id"=>0);
                return Response::json($response);
            }

            $userDetails = RegistrationDetails::find($data['id']);
            if (!$userDetails) {
                    return Response::json(array("status"=>'failure',"msg"=>'wrong id'));
            }

            // if user is present then enter its personal details
            $perInfo = PersonalDetails::where('registration_id',"=",$data['id'])->first();
            if (empty($perInfo))
                $perInfo = new PersonalDetails;

            $perInfo->fill($data);
            $perInfo->registration_id = $data["id"];
            if (!$perInfo->save()) {
                return Response::json(array('status'=>'failure','msg'=>'Problem in saving data','id'=>$data['id']));
            }
            $mCode = $this->generateRandomString(4);
            $userDetails->country = $data['country'];
            $userDetails->phone_no = $data['phone_no'];
            $userDetails->mobile_code = $mCode;
            $userDetails->save();
         
            // send data to twilio 
            $sendData['username'] = $userDetails->user_name;
            $sendData['email'] = $userDetails->user_email;
            $countryData = Country::where('name',"LIKE",$userDetails->country)->first();

            $phoneCode = $countryData->phonecode;
            $sendData['mobile_number'] = '+'.$phoneCode.$userDetails->phone_no;
            $sendData['password'] = 'MSLlearning';
            $sendData['password_confirmation'] = 'MSLlearning';
            $sendData['role_id'] = 6;
            $status = Curl::postCurl($_ENV['twilio'].'/api/create_user',$sendData);

            if (isset($status->user_id)){
                $userDetails->twilio_id = $status->user_id;
                $userDetails->save();
            }
            // send message to mobile for verification
            $msg = $this->sendMsgFromTwilio($mCode,$sendData['mobile_number']);

            // now send the email for verification
            $mailData = array();
            $mailData['user_name'] = $userDetails->user_name;
            $mailData['loginCountry'] = $data['country'];
            $mailData['user_email'] =  $userDetails->user_email;
            $mailData['phone_no'] = $data['phone_no'];
            $mailData['country'] = $data['country'];

            //sending the email to the user
            Mail::send('emails.emailregistration' ,$mailData, function($message) use ($sendData){
                $message->to($sendData['email'], $sendData['username'])
                  ->subject('Registration Success');
            });

            return Response::json(array('status'=>'success',
                'msg'=>'Successfully registered','id'=>$data['id'],"mcode"=>$mCode, 
                'email' => $userDetails->user_email, 'countryCode'=>$phoneCode));
        }
    }

    public function login(Request $request)
    {
        $data = $request->all();

        if ($data == null) {
            return Response::json(array('status'=>'failure','msg'=>'Please enter details',
                'id'=>0,'approved'=>'no', 'mobile_verification'=>'no',
                'registeration_confirmaton'=>'no','email_verification'=> 'no'));
        }
        if (!isset($data['user_name']) || !isset($data['user_password'])) {
                $response = array("status"=>"failure","msg"=>"Please enter username and password",
                    "id"=>0,'approved'=>'no' , 'mobile_verification'=>'no',
                    'registeration_confirmaton'=>'no', 'email_verification'=> 'no');
                return Response::json($response);
        }

        // call uAuth for login
        $sendData['username'] = $data['user_name'];
        $sendData['password'] = $data['user_password'];
        $status = Curl::postCurl($_ENV['uauth'].'/users/loginCurl',$sendData);

        if (isset($status->success)) {

            $user = RegistrationDetails::whereRaw('user_name = ?', array($data['user_name']))->first();

            if (!$user) {
                // register user in db
                return Response::json(array("status"=>"failure","msg"=>'Incorrect username',
                    'id'=>"0",'approved'=>'no', 'mobile_verification'=>'no',
                    'registeration_confirmaton'=>'no','email_verification'=> 'no'));
            }

            //if (Hash::check($data['user_password'],$user->user_password)) {
                // check user application is approved or not
                $ebookStatus = Curl::postCurl($_ENV['core'].'/getForEbookStatus/'.$data['user_name']);
                $ebookStatus = json_decode(json_encode($ebookStatus), true);

                $approved = 'yes';
                $registeration_confirmaton = 'no';
                $emailVerification = 'no';

                // email verification
                if ($user->verified_email)
                    $emailVerification = 'yes';

                // whether user is approved by administration or not
                if (isset($ebookStatus['error']))
                    $approved = 'no';

                // to check whether user completed the process of direct registration
                $perDetails = PersonalDetails::where('registration_id','=',$user->id)->first();
                if ($perDetails)
                    $registeration_confirmaton = 'yes';
                
                return Response::json(array('status'=>'success',"msg"=>"Login successfull",
                     'id'=>$user['id'],'approved'=>$approved , 'mobile_verification'=>$user->mobile_status,
                     'registeration_confirmaton'=>$registeration_confirmaton,
                     'email_verification'=> $emailVerification));
            //}
         }

        $error = $status->error;

        if (gettype($error) == 'object')
            return Response::json(array("status"=>"failure","msg"=>'username is invalid','id'=>"0"
                ,'approved'=>'no', 'mobile_verification'=>'no','registeration_confirmaton'=>'no',
                'email_verification'=> 'no'));
        else
            return Response::json(array("status"=>"failure","msg"=>'Incorrect Password','id'=>"0"
                ,'approved'=>'no', 'mobile_verification'=>'no',
                'registeration_confirmaton'=>'no','email_verification'=> 'no'));
    }

    // send msg from twilio
    public function sendMsgFromTwilio($mcode,$phone)
    {

        $sendMessage = "Dear Candidate, Welcome to the University.Your Mobile verification Code:".$mcode.
            ".Please verify your mobile number.Thank You";
        $message = Twilio::message($phone, $sendMessage);

        if ($message){
            return true;
        }
        return false;
    }

    // function to generate random string
    public function generateRandomString($length = 5)
    {
        return substr(str_shuffle("123456789"), 0, $length);
    }

    public function fillPerDetails(Request $request)
    {
        $data = $request->all();
        if (!isset($data['id'])) {
            return Response::json(array('status'=>'failure','msg'=>'Enter correct information'));
        }
        $userDetails = RegistrationDetails::find($data['id']);
        if (!$userDetails) {
                return Response::json(array("status"=>'failure',"msg"=>'wrong id'));
        }
        $perDetail = PersonalDetails::where('registration_id','=',$data['id'])->first();
        if (!$perDetail){
            $perDetail = new PersonalDetails;
            $perDetail->registration_id = $data['id'];
        }
        $perDetail->fill($data);
        if ($perDetail->save()) {
            return Response::json(array('status'=>'success','msg'=>'Data has been saved successfully','data'=>$perDetail));
        }
        return Response::json(array('status'=>'failure','msg'=>'problem in daving data'));
    }

    public function sendMsg(Request $request)
    {
        $data = $request->all();

        if (isset($data['id'])) {
            Response::json(array("status"=>'failure',"msg"=>'Missing argument'));
        }
        $regDetails = RegistrationDetails::find($data['id']);

        if (!$regDetails)
            return Response::json(array("status"=>"failure","msg"=>"No data is present"));
        $phone_no = $regDetails->phone_no;
        $country = $regDetails->country;
        if (!$country)
            return Response::json(array("status"=>"failure","msg"=>"Please enter your country"));
        $countryData = Country::where('name',"LIKE",$country)->first();
 
        $phnCode = $countryData->phonecode;
        $phone = "+".$phnCode.$phone_no;
        $mCode = $this->generateRandomString(4);
        $msg = $this->sendMsgFromTwilio($mCode,$phone);

        if ($msg){
            // save the code to DB
            $regDetails->mobile_code = $mCode;
            if (!$regDetails->save())
                return Response::json(array("status"=>"failure","msg"=>"Problem in saving data"));
            return Response::json(array("status"=>"success","msg"=>"Message send successfully"));
        }
        return Response::json(array("status"=>"failure","msg"=>"Problem in sending message"));
    }

    public function verifyMobileCode(Request $request) 
    {
        $data = $request->all();
        if (!isset($data["id"]) || !isset($data["mCode"]))
            return Response::json(array("status"=>"failure","msg"=>"Missing argument"));
        $regData = RegistrationDetails::find($data['id']);
        if (!$regData)
            return Response::json(array("status"=>"failue","msg"=>"Invalid id"));
        if ($data['mCode'] == $regData->mobile_code){
            $regData->mobile_status = 'yes';
            if (!$regData->save())
                return Response::json(array("status"=>"failue","msg"=>"Please enter the correct mobile code "));
            return Response::json(array("status"=>"success","msg"=>"Number verified"));
        }
        return Response::json(array("status"=>"failue","msg"=>"wrong code entered"));
    }


    public function google()
    {
        $return_url = Crypt::encrypt(10);
        return redirect('https://uauth.unextt.com/googlelogin/'.$return_url);
    }

    public function facebook()
    {
        $return_url = Crypt::encrypt(10);
        return redirect('https://uauth.unextt.com/fblogin/'.$return_url);
    }


    // return uri for fb and google link
    public function socialMediaLogin($encrypt = null)
    {
        $data = array();
        if ($encrypt)
            $data = Crypt::decrypt($encrypt);
        else 
            return Response::json(array('noResponse'=>'Waiting for response'));

        if (isset($data['error']))
            return Response::json(array('status'=>'failure','msg'=>$data['error']));

        if(preg_match('/\s/',$data['user_name'])) {
                $username = str_replace(' ', '%20', $data['user_name']);
        }
        else {
            $username = $data['user_name'];
        }
       
        // insert data
        $user = RegistrationDetails::whereRaw('user_name = ?', array($data['user_name']))->first();

        // info of user
        $sendData = array();
        $sendData['user_name'] = $data['user_name'];
        $sendData['user_email'] = $data['user_email'];
        $sendData['phone_no'] = $data['phone_no'];
        $sendData['gender'] = $data['gender'];
        $sendData['first_name'] = $data['first_name'];
        $sendData['last_name'] = $data['last_name'];
        $sendData['registeration_confirmaton'] = 'no';
        $sendData['mobile_verification'] = 'no';

        if (!$user) {
            $user = new RegistrationDetails;
            $user->user_name = $data['user_name'];
            $user->user_email = $data['user_email'];
            $user->phone_no = $data['phone_no'];
            $user->registration_type = $data['registration_type'];
            $user->verified_email = '1';
            if ($user->save()) {
                $path = public_path().'/'.$user->id;
                File::makeDirectory($path, $mode = 0777, true, true);

                $returnData = $this->checkLogin($sendData);
                return $returnData;
            }
            else {
                return Response::json(array("status"=>"failure","msg"=>"Problem in regsitration",
                    "data"=>0,
                    'approved'=>'no'));
            }
        }
        $sendData['id'] = $user->id;
        
        // check personal details is present or not
        $perDetail = PersonalDetails::where('registration_id','=',$user->id)->first();
        if ($perDetail)
            $sendData['registeration_confirmaton'] = 'yes';
        $sendData['mobile_verification'] = $user->mobile_status;
        return Response::json(array("status"=>"success","msg"=>"User is already present in the system",
                    "data"=>$sendData,
                    'approved'=>'no'));
    }

    public function checkLogin($sendData=null)
    {
        if ($sendData)
            return  Response::json(array("status"=>"success","msg"=>"User registered","data"=>$sendData,
                'approved'=>'no'));
        else
            return Response::json(array("status"=>"failure","msg"=>"Problem in regsitration","data"=>0,
                'approved'=>'no'));
    }

    public function twillioInfo(Request $request)
    {
       $id = $request->get('id');
        if ($id) {
            $data=RegistrationDetails::find($id);
            if (!$data) 
                return Response::json(array('status'=>'failure','msg'=>'User is not present'));
            else
                    $twillio=$data->twilio_id;
                    $username=$data->user_name;
                    $email=$data->user_email;
                    $phone=$data->phone_no;

                return Response::json(array('status'=>'success','TwillioId'=>$twillio,'User_name'=>$username,'User_email'=>$email,'Phone_no'=>$phone,));
        }

        return Response::json(array('status'=>'failure','msg'=>'Please enter valid data'));
    }

    public function getStudentPersonalInfo(Request $request)
    {
       $data = $request->all();

        if (empty($data)) {
            return Response::json(array('status','Failure', 'msg','Please enter data'));
        }
        else{
            $result = PersonalDetails::where('registration_id','=',$data['id'])->first();
            
            if (empty($result)) {
                return Response::json(array('status'=>"failure","msg"=>"No data is present"));
            }
            $userDetails = RegistrationDetails::find($data['id']);
            unset($result['id']);
            unset($result['registration_id']);
            unset($result['updated_at']);
            unset($result['created_at']);
            unset($result['read_only']);
            unset($result['user_photo']);
            unset($result['idproof_type']);
            unset($result['user_idproof']);
            if (isset($userDetails->user_email))
                $result['user_email']  = $userDetails->user_email;
            if (isset($userDetails->phone_no))
                $result['phone_no']  = $userDetails->phone_no;
            if (isset($userDetails->country))
                $result['country']  = $userDetails->country;
            return Response::json(array('status'=>'success','msg'=>$result));
        } 
    }

    // to submit all data
    public function finalSubmission(Request $request)
    {
        $data = $request->all();

        if ($data['selectProgram']!=null) {
            $params = json_decode($data['selectProgram'],true);
            $request = Request::create('selectProgram', 'POST', $params);
            $response =  App::make('App\Http\Controllers\ProgramController')->selectProgram($request)->getContent();
            $programResponse = json_decode($response,true);
            if ($programResponse['status'] == 'failure')
                return Response::json(array('status'=>'failure','msg'=>$programResponse['msg']." ,API:Select Program"));
        }

        if ($data['fillPerDetails']!=null) {
            $params = json_decode($data['fillPerDetails'],true);
            $request = Request::create('fillPerDetails', 'POST', $params);
            $response =  App::make('App\Http\Controllers\RegistrationController')->fillPerDetails($request)->getContent();
            $personalDetailResponse = json_decode($response,true);
            if ($personalDetailResponse['status'] == 'failure')
                return Response::json(array('status'=>'failure','msg'=>$personalDetailResponse['msg']." ,API:Personal Details"));
        }

        if ($data['academicDetails']!=null) {
            $params = json_decode($data['academicDetails'],true);
            $request = Request::create('fillPerDetails', 'POST', $params);
            $response =  App::make('App\Http\Controllers\AcademicController')->fillAcademicDetails($request)->getContent();
            $acadDetailResponse = json_decode($response,true);
            if ($acadDetailResponse['status'] == 'failure')
                return Response::json(array('status'=>'failure','msg'=>$acadDetailResponse['msg']." ,API:Academic Details"));
        }

        if ($data['employabilityDetails']!=null) {
            $params = json_decode($data['employabilityDetails'],true);
            if ($params['company1'] != "")
                $params['company1'] = json_encode($params['company1'][0]);
            if ($params['company2'] != "")
                $params['company2'] = json_encode($params['company2'][0]);
            if ($params['company3'] != "")
                $params['company3'] = json_encode($params['company3'][0]);
            if ($params['company4'] != "")
                $params['company4'] = json_encode($params['company4'][0]);
            $request = Request::create('employabilityDetails', 'POST', $params);
            $response =  App::make('App\Http\Controllers\EmployabilityController')->store($request)->getContent();
            $employmentDetailResponse = json_decode($response,true);
            if ($employmentDetailResponse['status'] == 'failure')
                return Response::json(array('status'=>'failure','msg'=>$employmentDetailResponse['msg']." ,API:Employment Details"));
        }

        if ($data['academicDocUpload']!=null) {
            $params = json_decode($data['academicDocUpload'],true);
            $params['data'] = json_encode($params['data']);
            $request = Request::create('academicDocUpload', 'POST', $params);
            $response =  App::make('App\Http\Controllers\AcademicDocumentUploadController')->store($request)->getContent();
            $acdemicDocResponse = json_decode($response,true);
            if ($acdemicDocResponse['status'] == 'failure')
                return Response::json(array('status'=>'failure','msg'=>$acdemicDocResponse['msg']." ,API:Academic Document Upload"));
        }

        if ($data['employmentDocUpload']!=null) {
            $params = json_decode($data['employmentDocUpload'],true);
            if ($params['first_document'] != "")
                $params['first_document'] = json_encode($params['first_document']);
            if ($params['second_document'] != "")
                $params['second_document'] = json_encode($params['second_document']);
            if ($params['third_document'] != "")
                $params['third_document'] = json_encode($params['third_document']);
            if ($params['fourth_document'] != "")
                $params['fourth_document'] = json_encode($params['fourth_document']);
            $request = Request::create('employmentDocUpload', 'POST', $params);
            $response =  App::make('App\Http\Controllers\EmploymentDocumentUploadController')->store($request)->getContent();
            $employmentDocResponse = json_decode($response,true);
            if ($employmentDocResponse['status'] == 'failure')
                return Response::json(array('status'=>'failure','msg'=>$employmentDocResponse['msg']." ,API:Employment Document Upload"));
        }

        if ($data['uploadPhoto']!=null) {
            $params = json_decode($data['uploadPhoto'],true);
            $request = Request::create('uploadPhoto', 'POST', $params);
            $response =  App::make('App\Http\Controllers\UploadPhotoController')->store($request)->getContent();
            $uploadPhotoResponse = json_decode($response,true);
            if ($uploadPhotoResponse['status'] == 'failure')
                return Response::json(array('status'=>'failure','msg'=>$uploadPhotoResponse['msg']." ,API:Upload Photo"));
        }
        return Response::json(array('status'=>'success','msg'=>'Data Saved Successfully'));
    }

    // check email and send link to user for change password
    public function sendLinkForPassword(Request $request)
    {
        $email = $request->get('user_email');
        if(!$email)
            return Response::json(array('status'=>'failure','msg'=>'Please enter email'));
        $regDetails = RegistrationDetails::where('user_email','LIKE',$email)->first();
        if (!$regDetails)
            return Response::json(array('status'=>'failure','msg'=>'User email does not exist'));
        $EncrptedEmail = Crypt::encrypt($email);
        $return_url = Crypt::encrypt(9);
        //$url = $_ENV['app_url']."/changePassword/$encryptId";
        $url = $_ENV['uauth']."/users/after_forgetnewpassword/$EncrptedEmail/$return_url";
        // send mail
        $sendData['email'] = $email;
        $sendData['username'] = $regDetails->user_name;

        $mailData['url'] = $url;
        $mailData['user_name'] = $regDetails->user_name;

        $mail = Mail::send('emails.changePassword' ,$mailData, function($message) use ($sendData){
                  $message->to($sendData['email'], $sendData['username'])
                    ->subject('Change Password');
            });
        if ($mail) {
            $update_status = Curl::postCurl($_ENV['uauth'].'/users/forget',array('user_email'=>$email));
            // generate and send One Time Password
            $otp = $this->generateRandomString(4);
            $regDetails->otp = $otp;
            $regDetails->save();

            // get country of user
            $countryData = Country::where('name',"LIKE",$regDetails->country)->first();

            // concatenate user's country code with mobile number
            $phoneCode = $countryData->phonecode;
            $phone = '+'.$phoneCode.$regDetails->phone_no;
            $username = $regDetails->user_name;

            $response = $this->sendOTP($otp, $phone, $username);
            if ($response)
                return Response::json(array('status'=>'success','msg'=>'One Time Password sent to your phone'));
            else
                return Response::json(array('status'=>'failure','msg'=>'Problem in sending one time password'));
        }
        return Response::json(array('status'=>'failure','msg'=>'Problem in sending mail to change password'));

    }

    // send OTP 
    public function sendOTP($otp, $phone, $username)
    {
        $sendMessage = "Dear ".$username.", Welcome to the University.Your One Time Password:".$otp.
            ".Thank You";
        $message = Twilio::message($phone, $sendMessage);

        if ($message){
            return true;
        }
        return false;
    }

    public function checkOTP(Request $request)
    {
        $data = $request->all();
        if (!isset($data['user_email']) || !isset($data['otp']))
            return Response::json(array('status'=>'failure','msg'=>'Invalid argument'));
        $regDetails = RegistrationDetails::where('user_email','LIKE',$data['user_email'])->first();
        if (empty($regDetails))
            return Response::json(array('status' => 'failure', 'msg' => 'Invalid Email'));
        if ($data['otp'] == $regDetails->otp)
            return Response::json(array('status' => 'success', 'msg' => 'Correct One Time Password'));
        return Response::json(array('status' => 'failure', 'msg' => 'One Time Password mismatch'));
    }

    public function resetPassword(Request $request)
    {
        $data = $request->all();
        if (!isset($data['user_email']) || !isset($data['user_password']))
            return Response::json(array('status' => 'failure', 'msg' => 'Invalid arguments'));
        $regDetails = RegistrationDetails::where('user_email','LIKE', $data['user_email'])->first();
        if (empty($regDetails))
            return Response::json(array('status' => 'failure', 'msg' => 'User Email is not present'));

        $email = Crypt::encrypt($data['user_email']);
        $password = Crypt::encrypt($data['user_password']);

        // call uauth to change password
        $status = Curl::postCurl($_ENV['uauth'].'/usersAndroid/andChangePass',array('user_email'=>$email,
            'user_password' => $password));
        
        if (isset($status->success))
            return Response::json(array('status' => 'success', 'msg' => 'Password reset successfully'));
        else 
            return Response::json(array('status' => 'failure', 'msg' => $status->error_log(message)));
    }

    public function callForOTP(Request $request)
    {
        $data = $request->all();
        if (!isset($data['user_email']))
            return Response::json(array('status' => 'failure', 'msg' => 'Invalid argument'));

        $regDetails = RegistrationDetails::where('user_email', 'LIKE', $data['user_email'])->first();
        if (empty($regDetails))
            return Response::json(array('status' => 'failure', 'msg' => 'User Email is not present'));

        $country = $regDetails->country;
        $phone_no = $regDetails->phone_no;

        $countryData = Country::where('name',"LIKE",$country)->first();
 
        $phnCode = $countryData->phonecode;
        $phone = "+".$phnCode.$phone_no;
        $otp = $this->generateRandomString(4);
        $array = str_split($otp);
        $regDetails->otp = $otp;
        $regDetails->save();
        $twilio = Twilio::call($phone, $_ENV['app_url']."/callToGetOtp/$array[0]/$array[1]/$array[2]/$array[3]");
    }

    public function changedPassword()
    {
        return view('changePassword');
    }

    public function verifyEmail($encryptId)
    {
       if ($encryptId) {
           $data = Crypt::decrypt($encryptId);
           $user = RegistrationDetails::where('id','LIKE',$data)->first();
           if ($user) {
                $email = $user->user_email;
                $confirmMail = Curl::postCurl($_ENV['uauth'].'/users/verifyEmail',array('user_email'=>$email));
                if ($confirmMail->success) {
                    $user->verified_email = 1;
                    $user->save();
                    return view('emails.emailVerified');
                }
                return view('emails.emailNotVerified');
           }
       }
       return view('emails.emailNotVerified');
    }

    // call if message is not a success
    public function voiceCall(Request $request)
    {
        $data = $request->all();
        if (isset($data['id'])) {
            Response::json(array("status"=>'failure',"msg"=>'Missing argument'));
        }
        $regDetails = RegistrationDetails::find($data['id']);

        if (!$regDetails)
            return Response::json(array("status"=>"failure","msg"=>"No data is present"));
        $phone_no = $regDetails->phone_no;
        $country = $regDetails->country;
        if (!$country)
            return Response::json(array("status"=>"failure","msg"=>"Country missing"));
        $countryData = Country::where('name',"LIKE",$country)->first();
 
        $phnCode = $countryData->phonecode;
        $phone = "+".$phnCode.$phone_no;
        $mCode = $this->generateRandomString(4);
        $array = str_split($mCode);
        $regDetails->mobile_code = $mCode;
        $regDetails->save();
        $twilio = Twilio::call($phone, $_ENV['app_url']."/callForMobileCode/$array[0]/$array[1]/$array[2]/$array[3]");
    }

    public function finishapplication($regid)
    {
        if (!$regid==null) {
            $programdata =array();
            // getting the program name from the select program table
            $data = SelectProgram::where('registration_id', '=', $regid)->first();
        
            if (!$data==null) {
                $programname = $data->program_name;
                $array = ProgramDetails::where('program_name','=', $programname)->first();

                if (!$array==null) {
                    $programdata['Progam Name']= $programname;
                    $programdata['Program Eligibility']= $array->program_eligibility;
                    $programdata['Program Duration']= $array->program_duration;
                    $programdata['Program Description']= $array->program_description;
                    return Response::json(array('status'=>'success','msg'=>$programdata));
                }
                else{
                    return Response::json(array('status'=>'failure','msg'=>'Program does not exits'));
                }
            }
            else{
                return Response::json(array('status'=>'failure','msg'=>'Please enter a valid registration id'));
            }
        }
    }
}