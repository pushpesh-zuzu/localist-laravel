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
use App\Helpers\Zoho\ZohoEmails;
use App\Helpers\Zoho\ZohoHelper;
use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Http\Controllers\Api\ApiController;
use App\Models\EmailLog;
use App\Services\LeadService;

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

    public function createNewRequest(Request $request, LeadService $leadService){


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
                    $dataUser['zipcode'] = $request->postcode;
                    $dataUser['city'] = $request->city;
                    //for
                    $password = '12345678';//Str::random(10);
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
                //dd($user);
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

            // remove null from question
            $jQuestions = $request->questions;
            $decodedQ = json_decode($jQuestions, true);
            $filtered = array_filter($decodedQ, function($item) {
                return !is_null($item);
            });
            $filtered = array_values($filtered);
            $data['questions'] = json_encode($filtered);

            //make the answers in proper json array so that it can be used for strict macthing
            $arrQuesD = json_decode($request->questions, true);
            $arrQues = [];
            foreach ($arrQuesD as $aq) {
                if(!empty($aq)){
                    $temp['ques'] = $aq['ques'];
                    $temp['ans'] = array_map('trim', explode(',', $aq['ans']));
                    $arrQues[] = $temp;
                }
            }
            $data['arrayed_questions'] = json_encode($arrQues);

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

            $data['credit_score'] = CreditScore::predict($data['service_id'],$predict,$request->questions);

            $leadDetails = LeadRequest::create($data);
            $sId = $leadDetails->id;
            // $leadsDetails = LeadRequest::where('id',$sId)->first();
            // $zohoService = new ZohoService();
            // $zohoService->integrateUser('lead',null,$leadsDetails);

            //create Notification on lead creation

            User::where('form_status', 1)
                ->whereIn('user_type', [1, 3])
                ->select('id')
                ->chunk(1000, function ($sellersChunk) use ($leadService) {
                    foreach ($sellersChunk as $seller) {
                        $baseQuery = $leadService->getSellerLeadsBaseQuery($seller->id);
                        $allLeads = $baseQuery->orderBy('id', 'desc')->get();

                        $allLeads = $leadService->leadsAccordingTOSellerPref($seller->id, $allLeads);

                        foreach ($allLeads as $lead) {
                            CustomHelper::logNotifications(
                                $seller->id,
                                $lead->id,
                                'buyer_browser_new_lead',
                                'New Lead',
                                'You have got a new lead',
                                true
                            );
                        }
                    }
                });

            unset($leadPref);

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

                ZohoHelper::dispatchAfterResponse([$this, 'autoBidBased'], [
                    'success' => true,
                    'message' => 'Quote Submitted Successfully',
                    'data' => $rel
                ]);

                //return $this->sendResponse('Quote Submitted Sucessfully',$rel);
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

    public function autoBidBased()
    {
        $newLead = $this->sendNewLeadRequestAutoBidOff();
        $newLeadBidEnough = $this->sendLeadEmailCreditEnough();

        $newLeadBidNotEnough = $this->sendLeadEmailCreditNotEnough();


    }

    public function sendNewLeadRequestAutoBidOff()
    {

        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            ->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
            ->where('recommended_leads.purchase_type', 'Autobid')
            ->where('form_status', 1)
            ->where('user_type', 1)
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 1)
                     ->orWhere('is_autobid', 0);
            })
            ->select('users.id')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {
                foreach ($sellersChunk as $seller) {

                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id);
                        // ->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();

                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    foreach ($filteredLeads as $lead) {
                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->whereDate('created_at', Carbon::today())
                            ->where('setting_name', 'New Lead-Auto Bid Disable (Check Credit)')
                            ->exists();


                        if (!$alreadySent) {
                            ZohoEmails::sendLeadRequestEmail($seller->id, $lead->id);

                            $totalUnsentLeadEmails++;
                        }
                    }
                }
            });

        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function sendLeadEmailCreditEnough()
    {

        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            ->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
            ->where('recommended_leads.purchase_type', 'Autobid')
            ->where('form_status', 1)
            ->where('total_credit', '>', 0)
            ->where('user_type', 1)
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 0)
                    ->where('is_autobid', 1);
            })
            ->select('users.id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {
                foreach ($sellersChunk as $seller) {



                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id);
                       //->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();

                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    $finalLeads = $filteredLeads->filter(function ($lead) use ($seller) {
                        return $lead->credit_score <= $seller->total_credit;
                    });



                    foreach ($finalLeads as $lead) {

                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->whereDate('created_at', Carbon::today())
                            ->where('setting_name', 'New Lead - Auto Bid Enabled (With Credits)')
                            ->exists();


                        if (!$alreadySent) {
                            ZohoEmails::sendLeadEmailBidEnough($seller->id, $lead->id);
                            $totalUnsentLeadEmails++;
                        }

                    }
                }
            });

        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function sendLeadEmailCreditNotEnough()
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            //->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
            //->where('recommended_leads.purchase_type', 'Autobid')
            ->where('id',4)
            ->where('form_status', 1)
            ->where('user_type', 1)
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 0)
                    ->where('is_autobid', 1);
            })
            ->select('id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {

                foreach ($sellersChunk as $seller) {

                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id);
                        //->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();
                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    $finalLeads = $filteredLeads->filter(function ($lead) use ($seller) {
                        return $lead->credit_score > $seller->total_credit;
                    });



                    foreach ($finalLeads as $lead) {
                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->whereDate('created_at', Carbon::today())
                            ->where('setting_name', 'New Lead- Auto Bid Enabled (Without  Enough Credits)')
                            ->exists();


                        if (!$alreadySent) {
                            ZohoEmails::sendLeadEmailBidNotEnough($seller->id, $lead->id);
                            $totalUnsentLeadEmails++;
                        }
                    }
                }
            });

        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
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
            LeadRequest::where('id',$request->request_id)->update($data);
            // $leadsDetails = LeadRequest::where('id',$request->request_id)->first();
            // $zohoService = new ZohoService();
            // $zohoService->integrateUser('lead',null,$leadsDetails);
            return $this->sendResponse('Image Uploaded');
        }

        return $this->sendError('Something went wrong, try again!');
    }

    public function addDetailsToRequest(Request $request){
        $user_id = $request->user_id;

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer|exists:lead_requests,id',
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
        // $leadsDetails = LeadRequest::where('id',$request->request_id)->first();
        // $zohoService = new ZohoService();
        // $zohoService->integrateUser('lead',null,$leadsDetails);
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

            $dataLead['is_phone_verified'] = '1';
            $dataLead['updated_at'] = date('Y-m-d H:i:s');
            LeadRequest::where('customer_id', $request->user_id)->update($dataLead);
            // $leadsDetails = LeadRequest::where('customer_id',$request->user_id)->first();
            // $zohoService = new ZohoService();
            // $zohoService->integrateUser('lead',null,$leadsDetails);
            return $this->sendResponse('Phone number verified successfully!');

        }
        return $this->sendError('Wrong OTP, try again!');
    }

}
