<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Helpers\CustomHelper;
use App\Helpers\CreditScorePredictor as CreditScore;
use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Http\Controllers\Api\ApiController;

class MyRequestController extends Controller
{
    public function test(){
        return "hello world";
    }

    public function getSubmittedRequestList(Request $request){
        $user_id = $request->user_id;

        $list = LeadRequest::with(['customer','category'])->where('customer_id',$user_id)->get();

        return $this->sendResponse('Submitted Quotes',$list);

    }

    public function getSubmittedRequestInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer|exists:lead_requests,id',
          ], [
            'image_file.required' => 'Location Postcode is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $info = LeadRequest::with(['customer','category'])->where('id',$request->request_id)->get();
        return $this->sendResponse('Quotation Information',$info);

    }

    public function createNewRequest(Request $request){
        
        

        if($request->form_status == "1"){
            $validator = Validator::make($request->all(), [
                'service_id' => 'required|integer|exists:categories,id',
                'postcode' => 'required',
                'questions' => 'required',
                'phone' => 'required',
                'form_status' => 'required'
              ], [
                'postcode.required' => 'Location Postcode is required.',
                'service_id.exists' => 'Provided service id does not exists.',
                'form_status.required' => 'Form Status is required.'
            ]);
    
            if($validator->fails()){
                return $this->sendError($validator->errors());
            }
            
            $phoneOtp = "";
            $euId = "";
            $token = "";
            //check if it is registration request or not
            if(!empty($request->email)){
                
                //check if user exists for the given email or not
                $password = "";
                $euId = User::where('email',$request->email)->value('id');
                if(empty($euId)){
                    $dataUser['name'] = $request->name;
                    $dataUser['email'] = $request->email;
                    $dataUser['phone'] = $request->phone;
                    $password = Str::random(10);
                    $dataUser['password'] = Hash::make($password);
                    $dataUser['user_type'] = 2;
                    $dataUser['active_status'] = 2;
                    $dataUser['form_status'] = $request->form_status;
                    $dataUser['created_at'] = date('y-m-d H:i:s');
                    $dataUser['updated_at'] = date('y-m-d H:i:s');
                    $phoneOtp = "1234"; //random_int(1000, 9999);
                    $dataUser['otp'] = $phoneOtp;
                    $euId = User::insertGetId($dataUser);


                
                    
                    $dataUser['template'] = 'emails.buyer_registration';
                    $dataUser['service'] = Category::where('id',$request->service_id)->value('name');
                    $dataUser['password'] = $password;
                    
                    //send registration mail
                    // Mail::send($dataUser['template'], $dataUser, function ($message) use ($dataUser) {
                    //     $message->from('info@localists.com');
                    //     $message->to($dataUser['email']);
                    //     $message->subject("Welcome to Localist " .$dataUser['name'] ."!");
                    // });

                    // //send otp mail
                    // Mail::send($dataUser['template'], $dataUser, function ($message) use ($dataUser) {
                    //     $message->from('info@localists.com');
                    //     $message->to($dataUser['email']);
                    //     $message->subject("Verify your phone number");
                    // });
                }
                $user = User::where('id',$euId)->first();
                $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
                $user->update(['remember_token' => $token,'otp' => $phoneOtp]);
                $user->remember_tokens = $token;

            

            }else{
                //take bearer token and extract user id from token
                $token = $request->bearerToken();
                if (!$token) {
                    return response()->json(['error' => 'Unauthorized','message' => 'Token is missing.'], 401);
                }
                $accessToken = PersonalAccessToken::findToken($token);
                if (!$accessToken) {
                    return response()->json(['error' => 'Unauthorized','message' => 'Invalid token.'], 401);
                }
                // Extract user_id from token abilities
                $euId = $accessToken->abilities['user_id'] ?? null;
                if (!$euId) {
                    return response()->json(['error' => 'Unauthorized','message' => 'Token is missing.'], 401);
                }
            }

            $data['customer_id'] = $euId;
            $data['service_id'] = $request->service_id;
            $data['city'] = $request->city;
            $data['postcode'] = $request->postcode;
            $data['questions'] = $request->questions;
            $data['phone'] = $request->phone;

            $data['recevive_online'] = !empty($request->recevive_online)? $request->recevive_online : '0';
            
            
            $data['created_at'] = date('y-m-d H:i:s');
            $data['updated_at'] = date('y-m-d H:i:s');

            //evaluate Lead Badges
            $data['is_phone_verified'] = User::where('id',$euId)->value('phone_verified') == 1 ? 1 : 0;

            $leadCount = LeadRequest::where('customer_id',$euId)->where('created_at', '>=', Carbon::now()->subMonths(3))->count();
            $data['is_frequent_user'] = $leadCount > 0 ? 1: 0;

            $patternHighHiring = "/\b(ready to hire|definitely going to hire)\b/i";
            $data['is_high_hiring'] = preg_match($patternHighHiring, $request->questions) ? 1 : 0;

            $patternUrgent = "/\b(as soon as possible)\b/i";
            $data['is_urgent'] = preg_match($patternUrgent, $request->questions) ? 1 : 0;
            //end evaluate Lead Badges

            $predict['Location'] = $request->city .', ' . strtoupper($request->postcode);
            $predict['Urgent'] = $data['is_urgent'];
            $predict['High'] = $data['is_high_hiring'];
            $predict['Verified'] = $data['is_phone_verified'];
            $predict['Frequent'] = $data['is_frequent_user'];

            $questions = json_decode($request->questions, true);
            foreach($questions as $q){
                $predict[$q['ques']] = preg_replace(['/^,/', '/\?$/'], '', $q['ans']);
            }            
            $data['credit_score'] = CreditScore::predict($data['service_id'],$predict);

            $sId = LeadRequest::insertGetId($data);

            if($sId){
                $fUser = User::where('id',$euId)->first();
                $rel['user_id'] = $euId;
                
                $rel['user_type'] = $fUser->user_type; 
                $rel['form_status'] = $fUser->form_status; 
                $rel['active_status'] = $fUser->active_status; 
                $rel['remember_tokens'] = $token; 
                $rel['name'] = $fUser->name; 
                $rel['email'] = $fUser->email; 
                $rel['phone'] = $fUser->phone; 
                $rel['uuid'] = $fUser->uuid; 
                $rel['is_online'] = $fUser->is_online; 
                $rel['profile_image'] = $fUser->profile_image; 
                $rel['total_credit'] = $fUser->total_credit; 
                $rel['nation_wide'] = $fUser->nation_wide;
                $rel['request_id'] = $sId;

                // $apiController = new ApiController();
                // $bidRel = $apiController->autobid($request);
                // unset($apiController);

                return $this->sendResponse('Quote Submitted Sucessfully',$rel);
            }
        }else{
            $euId = User::where('email',$request->email)->value('id');
            if(!empty($euId)){
                return $this->sendResponse('Abodned user!');
            }
            $dataUser['name'] = $request->name;
            $dataUser['email'] = $request->email;
            $dataUser['phone'] = $request->phone;
            $password = Str::random(10);
            $dataUser['password'] = Hash::make($password);
            $dataUser['user_type'] = 2;
            $dataUser['active_status'] = 2;
            $dataUser['form_status'] = $request->form_status;
            $dataUser['created_at'] = date('y-m-d H:i:s');
            $dataUser['updated_at'] = date('y-m-d H:i:s');
            $euId = User::insertGetId($dataUser);
            return $this->sendResponse('Abodned user!');
        }
        return $this->sendError('Something went wrong, try again!');
    }

    public function addImageToSubmittedRequest(Request $request){
        $user_id = $request->user_id;

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer|exists:lead_requests,id',
            'image_file' => 'required|mimes:jpeg,jpg,png',
          ], [
            'image_file.required' => 'Image is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        if($request->hasfile('image_file')){

            $dir = 'public/images/customer/leads';
            $single_img=$request->file('image_file');
            $file_name = "img_" .time() ."." .$single_img->getClientOriginalExtension();
            $single_img->move($dir, $file_name);

            $prevImages = LeadRequest::where('id',$request->request_id)->value('images');
            $prevImages .= !empty($prevImages) ? ';' : '';


            $data['images'] = $prevImages. $dir .'/' .$file_name;
            $data['updated_at'] = date('y-m-d H:i:s');
            $sId = LeadRequest::where('id',$request->request_id)->update($data);
            return $this->sendResponse('Image Uploaded');
        }

        return $this->sendError('Something went wrong, try again!');
    }

    public function addDetailsToRequest(Request $request){
        $user_id = $request->user_id;

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer|exists:lead_requests,id',
            'details' => 'required',
          ], [
            'image_file.required' => 'Location Postcode is required.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $data['details'] = $request->details;
        $data['professional_letin'] = !empty($request->professional_letin)? $request->professional_letin : '0';
        $data['has_additional_details'] = '1';
        $sId = LeadRequest::where('id',$request->request_id)->update($data);
        if($sId){
            return $this->sendResponse('Details Added');
        }
         
        return $this->sendError('Something went wrong, try again!');
    }  
    

    public function checkParagraphQuality(Request $request){       

        $validator = Validator::make($request->all(), [
            'text' => 'required',
          ], [
            'text.required' => 'Text is required for checking the quality score.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $text = $request->text;

        $response = Http::asForm()->post('https://api.languagetool.org/v2/check', [
            'text' => $text,
            'language' => 'en-US'
        ]);

        $data = $response->json();
        
        if(!empty($data)){

            $baseScore = 100;
            $errorCount = count($data['matches']);
            $wordCount = str_word_count($text);
            $errorPenalty = $errorCount * 5;
            $minParagraphWordLength = 20; 
            if ($wordCount < $minParagraphWordLength) {
                $lengthPenalty = ($minParagraphWordLength - $wordCount) * 5; 
            } else {
                $lengthPenalty = 0; 
            }
            $qualityScore = $baseScore - $errorPenalty - $lengthPenalty;
            $qualityScore = max(0, min(100, $qualityScore));

            // $rel['length_penalty'] = $lengthPenalty;
            // $rel['word_count'] = $wordCount;
            // $rel['error_count'] = $errorCount;
            $rel['text'] = $text;
            $rel['quality_score'] = $qualityScore;
            return $this->sendResponse('Quality Details',$rel);
        }
                 
        return $this->sendError('Something went wrong, try again!',$data);
    } 


    public function verifyPhoneNumber(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'otp' => 'required',
          ], [
            'image_file.required' => 'Location Postcode is required.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $cOtp = User::where('id',$request->user_id)->value('otp');
        $otp = $request->otp;

        if($cOtp == $otp){
            $data['phone_verified'] = 1;
            $data['updated_at'] = date('Y-m-d H:i:s');
            User::where('id',$request->user_id)->update($data);
            return $this->sendResponse('Phone number verified successfully!');

        }
        print_r($cOtp);
        echo "<br>";
        print_r($otp);
        return $this->sendError('Wrong OTP, try again!');
    }

}