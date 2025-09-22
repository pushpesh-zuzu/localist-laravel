<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Log, Mail, Validator
};
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Helpers\CustomHelper;
use App\Helpers\CreditScorePredictor as CreditScore;
use App\Helpers\Zoho\ZohoCustomerQuestionAnswer;
use App\Helpers\Zoho\ZohoEmails;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoQuoteCustomers;
use App\Helpers\Zoho\ZohoQuoteRequest;
use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Http\Controllers\Api\ApiController;
use App\Models\EmailLog;
use App\Models\NotificationSetting;
use App\Models\UserDetail;
use App\Models\AutobidStatusLog;
use App\Models\AbandonedUser;
use App\Models\SmsLog;
use App\Services\LeadService;
use Exception;
use GuzzleHttp\Client;

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

    public  function registerQuoteCustomer(Request $request){
        $validator = Validator::make($request->all(), [
            'form_status' => 'required'
            ], [
            'form_status.required' => 'Form status is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        if($request->form_status == "1"){
            $validator2 = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|unique:users,email',
                'phone' => 'required'
              ], [
                'name.required' => 'Name is required.',
                'email.required' => 'Email is required.',
                'email.unique' => 'Email already exists.',
                'phone.required' => 'Phone is required.'
            ]);

            if($validator2->fails()){
                return $this->sendError($validator2->errors());
            }
            $euId = "";
            $token = "";
            $password =Str::random(8);
            $euidInsert=0;
            $phoneOtp = 0;

            $dataUser['name'] = $request->name;
            $dataUser['email'] = $request->email;
            if (isset($request->phone) && !empty($request->phone)) {
                $cleanPhone = preg_replace('/\s+/', '', $request->phone); // remove spaces
                if (strpos($cleanPhone, '+44') !== 0) {
                    $cleanPhone = ltrim($cleanPhone, '0');
                    $dataUser['phone'] = '+44' . $cleanPhone;
                } else {
                    $dataUser['phone'] = $cleanPhone;
                }
            }
            $dataUser['zipcode'] = $request->postcode;
            $dataUser['city'] = $request->city;
            $dataUser['questions'] = $request->questions;
            $dataUser['service_id'] = $request->service_id;
            //for

            $dataUser['password'] = Hash::make($password);
            $dataUser['user_type'] = 2;
            $dataUser['active_status'] = 2;
            $dataUser['form_status'] = 0;
            $dataUser['created_at'] = date('y-m-d H:i:s');
            $dataUser['updated_at'] = date('y-m-d H:i:s');
            //$phoneOtp = "1234";
            $phoneOtp = random_int(1000, 9999);
            $dataUser['otp'] = $phoneOtp;

            $euId = AbandonedUser::insertGetId($dataUser);

            $user = AbandonedUser::where('id',$euId)->first();

            $rel['user_id'] = $euId;

            $rel['user_type'] = $user->user_type;
            $rel['form_status'] = $user->form_status;
            $rel['active_status'] = $user->active_status;
            $rel['phone'] = $user->phone;

            if($user->phone){
                $this->sendOtpDirect($user->phone,$phoneOtp,$euId);
            }


            return ZohoHelper::dispatchAfterResponse(function () use ($euId, $rel) {

                app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($euId,'abandon');

            }, [
                'success' => true,
                'message' => 'Quote Customer registered Successfully',
                'data' => $rel
            ]);
        }else{

            $euId = AbandonedUser::where('email',$request->email)->value('id');
            if(!empty($euId)){
                return $this->sendResponse('Abandoned Quote Customer already exists');
            }
            $dataUser['name'] = $request->name;
            $dataUser['email'] = $request->email;
            // $dataUser['phone'] = $request->phone;
             if (isset($request->phone) && !empty($request->phone)) {
                $cleanPhone = preg_replace('/\s+/', '', $request->phone); // remove spaces
                if (strpos($cleanPhone, '+44') !== 0) {
                    $cleanPhone = ltrim($cleanPhone, '0');
                    $dataUser['phone'] = '+44' . $cleanPhone;
                } else {
                    $dataUser['phone'] = $cleanPhone;
                }
            }
            $password = Str::random(8);
            $dataUser['password'] = Hash::make($password);
            $dataUser['user_type'] = 2;
            $dataUser['active_status'] = 2;
            $dataUser['form_status'] = 0;
            $dataUser['created_at'] = date('y-m-d H:i:s');
            $dataUser['updated_at'] = date('y-m-d H:i:s');
            $euId = AbandonedUser::insertGetId($dataUser);

            return ZohoHelper::dispatchAfterResponse(function () use ($euId) {
                app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($euId,'abandon');

                app(self::class)->sendEncouragementEmail(['userId' => $euId]);
            }, [
                    'success' => true,
                    'message' => 'Abandoned Quote Customer'
                ]
            );
        }

    }

    public function verifyPhoneNumber(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:abandoned_users,id',
            'otp' => 'required',
          ], [
            'image_file.required' => 'Location Postcode is required.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $abUser = AbandonedUser::where('id',$request->user_id)->first();
        $cOtp = $abUser->otp;
        $otp = $request->otp;

        if($cOtp == $otp){
            $password =Str::random(8);
            $nuData['name'] = $abUser->name;
            $nuData['email'] = $abUser->email;
            $nuData['phone'] = $abUser->phone;
            $nuData['otp'] = $abUser->otp;
            $nuData['otp_sinch_status'] = $abUser->otp_sinch_status;
            $nuData['zoho_record_id'] = $abUser->zoho_record_id;
            $nuData['password'] = Hash::make($password);
            $nuData['user_type'] = 2;
            $nuData['active_status'] = 2;
            $nuData['form_status'] = 1;
            $nuData['created_at'] = date('y-m-d H:i:s');
            $nuData['updated_at'] = date('y-m-d H:i:s');
            $euId = User::insertGetId($nuData);
            AbandonedUser::where('email', $nuData['email'])->delete();

            $phoneOtp = $abUser->otp;
            if(!empty($euId)){

                UserDetail::create([
                    'user_id'  => $euId,
                    'is_autobid'  =>1,
                    'billing_contact_name' => $nuData['name'],
                    'billing_phone' => $nuData['phone'],
                    'billing_vat_register' => 1,
                ]);

                $dataAb['user_id'] = $euId;
                $dataAb['action'] = 'enabled';
                AutobidStatusLog::insertGetId($dataAb);


                $now = now();
                NotificationSetting::insert([
                    [
                        'user_id'   => $euId,
                        'noti_name' => 'customer_email_change_in_request',
                        'noti_value'=> 1,
                        'user_type' => 'customer',
                        'noti_type' => 'email',
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ],
                    [
                        'user_id'   => $euId,
                        'noti_name' => 'customer_email_reminder_to_reply',
                        'noti_value'=> 1,
                        'user_type' => 'customer',
                        'noti_type' => 'email',
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ],
                    [
                        'user_id'   => $euId,
                        'noti_name' => 'customer_email_update_about_new_feature',
                        'noti_value'=> 1,
                        'user_type' => 'customer',
                        'noti_type' => 'email',
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ],
                ]);

            }

            $user = User::where('id',$euId)->first();
            //dd($user);
            $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
            $user->update(['remember_token' => $token]);
            $user->remember_tokens = $token;
            $userId = $user->id;
            $rel['user_id'] = $euId;
            $rel['user_type'] = $user->user_type;
            $rel['form_status'] = $user->form_status;
            $rel['active_status'] = $user->active_status;
            $rel['remember_tokens'] = $token;
            $rel['name'] = $user->name;
            $rel['email'] = $user->email;
            $rel['phone'] = $user->phone;
            $rel['uuid'] = $user->uuid;
            $rel['is_online'] = $user->is_online;
            $rel['profile_image'] = $user->profile_image;
            $rel['total_credit'] = $user->total_credit;
            $rel['nation_wide'] = $user->nation_wide;

            return ZohoHelper::dispatchAfterResponse(function () use ($userId, $rel, $password, $phoneOtp, $user) {

                  app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($userId); // change it to update form status in zoho crm
                if($user->form_status ==1){
                    ZohoEmails::sendWelcomeEmailQuoteCustomer($userId, $password, $phoneOtp);
                }
            }, [
                'success' => true,
                'message' => 'Phone verified Successfully',
                'data' => $rel
            ]);

        }
        return $this->sendError('Wrong OTP, try again!');
    }

    public function createNewRequest(Request $request, LeadService $leadService){

        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer|exists:categories,id',
            'postcode' => 'required',
            'questions' => 'required',
            'phone' => 'required',
            ], [
            'postcode.required' => 'Location Postcode is required.',
            'service_id.exists' => 'Provided service id does not exists.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

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

        $serviceId =$request->service_id;
        $data['customer_id'] = $euId;
        $data['service_id'] = $serviceId;
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

        $sId = 0;
        if($data['credit_score'] > 0){
            $leadDetails = LeadRequest::create($data);
            $sId = $leadDetails->id;
        }

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

            return ZohoHelper::dispatchAfterResponse(
                function () use ($euId, $rel, $sId, $leadService, $fUser) {


                    User::where('form_status', 1)
                        ->whereIn('user_type', [1, 3])
                        ->select('id')
                        ->chunk(800, function ($sellersChunk) use ($leadService) {
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

                    app(ZohoQuoteRequest::class)->integrateQuoteRequest($euId,$sId);

                    $lead = LeadRequest::find($sId);
                    $sellers = $leadService->getAllSellers($lead);
                    if(!empty($sellers['response']['sellers'])){
                        $sortedSellers = $sellers['response']['sellers']
                            ->sortByDesc('total_credit')
                            ->values()
                            ->take(7);
                        foreach($sortedSellers as $seller){
                            ZohoEmails::newLeadPoolOf7LeadBuyerEmail($sId, $seller->user_id);
                        }
                    }
                    $this->autoBidBased([
                        'success' => true,
                        'message' => 'Quote Submitted Successfully',
                        'data' => $rel,
                        'euId' => $euId,
                    ]);
            }, [
                'success' => true,
                'message' => 'Quote Submitted Successfully',
                'data' => $rel
            ]);
        }

       return $this->sendError('Something went wrong, try again!');
    }

    public function sendEncouragementEmail($request)
    {
        $userId = $request['userId'];
        $sentCount = 0;
        $users = AbandonedUser::whereNotNull('zoho_record_id')
            ->where('id',$userId)
            ->where('form_status',0)

            // ->with(['details', 'emailLogs' => function ($q) {
            //     $q->where('setting_name', 'Send Autobid Encouragement Email')
            //         ->latest();
            // }])
            ->get();


        ZohoEmails::sendAbandonedEncouragementEmail($userId);
        return response()->json([
            'status' => 'success',
            'message' => "$sentCount encouragement email(s) sent.",
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    public function autoBidBased()
    {

        $newLead = $this->sendNewLeadRequestAutoBidOff();
        $newLeadBidEnough = $this->sendLeadEmailCreditEnough();
        $newLeadBidNotEnough = $this->sendLeadEmailCreditNotEnough();


        return response()->json([
            'step1' => $newLead,
            'step2' => $newLeadBidEnough,
            'step3' => $newLeadBidNotEnough,
            'summary' => 'All steps completed'
        ]);


    }

    // public function sendNewLeadRequestAutoBidOff()
    // {

    //     $totalUnsentLeadEmails = 0;
    //     $leadPref = new LeadService();

    //     User::whereNotNull('zoho_record_id')
    //         ->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
    //         ->where('recommended_leads.purchase_type', 'Autobid')
    //         ->where('form_status', 1)
    //         ->where('user_type', 1)
    //         ->whereHas('details', function ($query) {
    //             $query->where('autobid_pause', 1)
    //                  ->orWhere('is_autobid', 0);
    //         })
    //         ->select('users.id')
    //         ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {
    //             //dd($sellersChunk);
    //             foreach ($sellersChunk as $seller) {

    //                 $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id);
    //                     // ->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);

    //                 $allLeads = $baseQuery->orderBy('id', 'desc')->get();

    //                 $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

    //                 foreach ($filteredLeads as $lead) {
    //                     $alreadySent = EmailLog::where('user_id', $seller->id)
    //                         ->where('lead_id', $lead->id)
    //                         //->whereDate('created_at', Carbon::today())
    //                         ->where('setting_name', 'New Lead-Auto Bid Disable (Check Credit)')
    //                         ->exists();


    //                     if (!$alreadySent) {
    //                         ZohoEmails::sendLeadNotBid($seller->id, $lead->id);

    //                         $totalUnsentLeadEmails++;
    //                     }
    //                 }
    //             }
    //         });

    //     unset($leadPref);
    //      return [
    //         'status' => 'success',
    //         'unsent_lead_emails' => $totalUnsentLeadEmails,
    //         'timestamp' => now()->toDateTimeString(),
    //     ];
    // }

    public function sendNewLeadRequestAutoBidOff()
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            // ->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
            // ->where('recommended_leads.purchase_type', 'Autobid')
            ->where('form_status', 1)
            ->whereIn('user_type', [1, 3])
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 1)
                    ->orWhere('is_autobid', 0);
            })
            ->select('users.id')
            ->distinct()
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {

                foreach ($sellersChunk as $seller) {
                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id,null,null,null,'Autobid');
                    $allLeads = $baseQuery->orderBy('id', 'desc')->limit(1)->get();

                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    $finalLeads = [];

                    foreach ($filteredLeads as $lead) {
                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'New Lead-Auto Bid Disable (Check Credit)')
                            ->exists();

                        if (!$alreadySent) {
                            $finalLeads[] = $lead;
                        }
                    }

                    if (!empty($finalLeads)) {
                        // Send one email with all leads
                        $result=ZohoEmails::sendLeadNotBidMultiple($seller->id, $finalLeads);

                         Log::info('Zoho Email for autobidoff request', [
                            'user_id' => $seller->id,
                            'response' => $result,
                        ]);

                        // Log each lead to avoid re-sending later

                         $totalUnsentLeadEmails++;

                    }
                }
            });

        unset($leadPref);
        return [
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ];
    }


    // public function sendLeadEmailCreditEnough()
    // {
    //     $totalUnsentLeadEmails = 0;
    //     $leadPref = new LeadService();

    //     User::whereNotNull('zoho_record_id')
    //         ->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
    //         ->where('recommended_leads.purchase_type', 'Autobid')
    //         ->where('form_status', 1)
    //         ->where('total_credit', '>', 0)
    //         ->where('user_type', 1)
    //         // ->whereHas('details', function ($query) {
    //         //     $query->where('autobid_pause', 0)
    //         //         ->where('is_autobid', 1);
    //         // })
    //         ->select('users.id', 'total_credit')
    //         ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {
    //             foreach ($sellersChunk as $seller) {

    //                 $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id,null,null,null,'Autobid');
    //                    //->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);

    //                 $allLeads = $baseQuery->orderBy('id', 'desc')->get();

    //                 $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

    //                 $finalLeads = $filteredLeads->filter(function ($lead) use ($seller) {
    //                     return $lead->credit_score <= $seller->total_credit;
    //                 });



    //                 foreach ($finalLeads as $lead) {

    //                     $alreadySent = EmailLog::where('user_id', $seller->id)
    //                         ->where('lead_id', $lead->id)
    //                         //->whereDate('created_at', Carbon::today())
    //                         ->where('setting_name', 'New Lead - Auto Bid Enabled (With Credits)')
    //                         ->exists();


    //                     if (!$alreadySent) {
    //                         ZohoEmails::sendLeadEmailBidEnough($seller->id, $lead->id);
    //                         $totalUnsentLeadEmails++;
    //                     }

    //                 }
    //             }
    //         });

    //     unset($leadPref);
    //     return [
    //         'status' => 'success',
    //         'unsent_lead_emails' => $totalUnsentLeadEmails,
    //         'timestamp' => now()->toDateTimeString(),
    //     ];
    // }
    public function sendLeadEmailCreditEnough()
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            // ->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
            // ->where('recommended_leads.purchase_type', 'Autobid')
            ->where('form_status', 1)
            ->where('total_credit', '>', 0)
            ->where('user_type', 1)
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 0)
                    ->where('is_autobid', 1);
            })
            ->select('users.id', 'total_credit')
            ->distinct()
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {
                foreach ($sellersChunk as $seller) {

                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id, null, null, null, 'Autobid');
                    $allLeads = $baseQuery->orderBy('id', 'desc')->limit(1)->get();

                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    // Filter by available credit
                    $finalLeads = $filteredLeads->filter(function ($lead) use ($seller) {
                        return $lead->credit_score <= $seller->total_credit;
                    })->filter(function ($lead) use ($seller) {
                        // Only include leads not already emailed
                        return !EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'New Lead - Auto Bid Enabled (With Credits)')
                            ->exists();
                    })->values();

                    if ($finalLeads->isNotEmpty()) {
                        // Send one email with all leads
                        $result = ZohoEmails::sendLeadBidEnoughMultiple([
                            $seller->id => $finalLeads->pluck('id')->toArray()
                        ]);

                        Log::info('Zoho Email for bid-enough leads', [
                            'user_id' => $seller->id,
                            'response' => $result,
                        ]);

                        // Log each lead to prevent resending

                        $totalUnsentLeadEmails++;
                    }
                }
            });

        unset($leadPref);

        return [
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ];
    }


    // public function sendLeadEmailCreditNotEnough()
    // {
    //     $totalUnsentLeadEmails = 0;
    //     $leadPref = new LeadService();

    //     User::whereNotNull('zoho_record_id')
    //         //->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
    //         //->where('recommended_leads.purchase_type', 'Autobid')
    //         ->where('form_status', 1)
    //         ->where('user_type', 1)
    //         // ->whereHas('details', function ($query) {
    //         //     $query->where('autobid_pause', 0)
    //         //         ->where('is_autobid', 1);
    //         // })
    //         ->select('id', 'total_credit')
    //         ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {

    //             foreach ($sellersChunk as $seller) {

    //                 $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id);
    //                     //->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);

    //                 $allLeads = $baseQuery->orderBy('id', 'desc')->get();
    //                 $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

    //                 $finalLeads = $filteredLeads->filter(function ($lead) use ($seller) {
    //                     return $lead->credit_score > $seller->total_credit;
    //                 });



    //                 foreach ($finalLeads as $lead) {
    //                     $alreadySent = EmailLog::where('user_id', $seller->id)
    //                         ->where('lead_id', $lead->id)
    //                         //->whereDate('created_at', Carbon::today())
    //                         ->where('setting_name', 'New Lead- Auto Bid Enabled (Without  Enough Credits)')
    //                         ->exists();


    //                     if (!$alreadySent) {
    //                         ZohoEmails::sendLeadEmailBidNotEnough($seller->id, $lead->id);
    //                         $totalUnsentLeadEmails++;
    //                     }
    //                 }
    //             }
    //         });

    //     unset($leadPref);

    //     return [
    //         'status' => 'success',
    //         'unsent_lead_emails' => $totalUnsentLeadEmails,
    //         'timestamp' => now()->toDateTimeString(),
    //     ];
    // }

    public function sendLeadEmailCreditNotEnough()
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            ->where('form_status', 1)
            ->where('user_type', 1)
            ->select('id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {

                foreach ($sellersChunk as $seller) {

                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->limit(1)->get();


                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    $finalLeads = $filteredLeads->filter(function ($lead) use ($seller) {
                        return $lead->credit_score > $seller->total_credit;
                    })->filter(function ($lead) use ($seller) {
                        // Only include leads not already emailed
                        return !EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'New Lead- Auto Bid Enabled (Without  Enough Credits)')
                            ->exists();
                    })->values();

                    if ($finalLeads->isEmpty()) {
                        continue;
                    }

                    if ($finalLeads->isNotEmpty()) {
                        // Check if email was already sent today for this seller


                            // Send one email for all leads
                            $result=ZohoEmails::sendGroupedLeadEmailBidNotEnough($seller->id, $finalLeads->pluck('id')->toArray()); // you must implement this
                            $totalUnsentLeadEmails++;

                            Log::info('Zoho Email for bid-not-enough leads', [
                                'user_id' => $seller->id,
                                'response' => $result,
                            ]);
                            // Log one entry per seller to avoid re-sending

                    }
                }
            });

        unset($leadPref);

        return [
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ];
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
        $leadRequestId = $request->request_id;
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
        $sId = LeadRequest::where('id',$leadRequestId)->update($data);


        // $leadsDetails = LeadRequest::where('id',$request->request_id)->first();
        // $zohoService = new ZohoService();
        // $zohoService->integrateUser('lead',null,$leadsDetails);
        if($sId){
            return ZohoHelper::dispatchAfterResponse(
                    function () use ($user_id, $leadRequestId) {
                        app(ZohoQuoteRequest::class)->integrateQuoteRequest($user_id,$leadRequestId);
                    },
                    [
                        'success' => true,
                        'message' => 'Details Added',
                    ]
                );
            //return $this->sendResponse('Details Added');
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


    public function sendOtpDirect($toNumber, $otpCode, $quoteId)
    {

        $sinchKey    = "morB1J2tPJPO8kkvx0A8";
        $sinchSecret = "OvgetB5Fx6gwCxwRA719yrJEV6gVco";



        $maxAttempts = 30;
        $delaySecs   = 2;

        $client = new Client();

        try {
            // 1) Build SMS payload
            $messageText = "Your verification code is {$otpCode}. Do not share this code with anyone.";

            $payload = [
                'messages' => [
                    [
                        'content' => $messageText,
                        'destination_number' => $toNumber,
                        'format' => 'SMS',
                        'delivery_report' => true,
                        'callback_url' => 'https://localists.com/admin/api/sinch/delivery-report', // optional
                        'source_number' => 'LOCALISTS'
                    ]
                ]
            ];

            // 2) Send with Basic auth (MessageMedia / Sinch)
            $authHeader = base64_encode("{$sinchKey}:{$sinchSecret}");
            $sendResp = $client->request('POST', 'https://api.messagemedia.com/v1/messages', [
                'headers' => [
                    'Authorization' => "Basic {$authHeader}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload,
                'http_errors' => false
            ]);

            $sendBody = json_decode((string)$sendResp->getBody(), true);

             Log::info('First Response', [
                'response' => $sendBody
            ]);

            // extract message id and initial status (if present)
            $messageId = null;
            $initialStatus = null;
            if (!empty($sendBody['messages'][0])) {
                $m = $sendBody['messages'][0];
                $messageId = $m['message_id'] ?? null;
                $initialStatus = $m['status'] ?? null;
            }

            // Create DB log row (initial)
            $smsLog = SmsLog::create([
                'quote_id' => $quoteId,
                'to_number' => $toNumber,
                'message_id' => $messageId,
                'message' => $messageText,
                'status' => $initialStatus,
                'otp' => $otpCode,
                'raw_response' => $sendBody
            ]);

             Log::info('First Response insert database');
            // 3) Poll for status (if we have an ID)
            $finalStatus = $initialStatus;
            if ($messageId) {
                $statusUrl = "https://api.messagemedia.com/v1/messages/{$messageId}";

                for ($i = 0; $i < $maxAttempts; $i++) {
                    $statusResp = $client->request('GET', $statusUrl, [
                        'headers' => [
                            'Authorization' => "Basic {$authHeader}",
                            'Accept' => 'application/json'
                        ],
                        'http_errors' => false
                    ]);

                    $statusBody = json_decode((string)$statusResp->getBody(), true);
                      Log::info('Second Response', [
                        'response' => $statusBody
                    ]);

                    // MessageMedia may return status at root or inside messages[]
                    $curStatus = $statusBody['status'] ?? $statusBody['state'] ?? ($statusBody['messages'][0]['status'] ?? null);

                    if ($curStatus) {
                        $finalStatus = $curStatus;
                        // update sms log with latest raw_response and status
                        $smsLog->update([
                            'status' => $finalStatus,
                            'raw_response' => $statusBody
                        ]);

                        $lc = strtolower((string)$curStatus);
                        if ($lc === 'delivered' || $lc === 'failed') {
                            break;
                        }
                    }

                    sleep($delaySecs);
                }
            }

            // 4) Update quote/your local table so status is saved with quote (if you have such table)
            if ($quoteId) {
                // Example: assume you have quotes table with id = quoteId
                // and columns: last_otp_sent, sms_status
                try {
                    DB::table('abandoned_users')->where('id', $quoteId)->update([
                        'otp_sinch_status' => $finalStatus
                    ]);
                    Log::info('database abandon Response', [
                        'status' => $finalStatus
                    ]);
                } catch (\Exception $e) {
                    // if your table name or columns differ, change above accordingly
                    Log::warning("Could not update quotes table for quote {$quoteId}: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message_id' => $messageId,
                'final_status' => $finalStatus,
                'sms_log_id' => $smsLog->id,
                'send_response' => $sendBody
            ], 200);

        } catch (Exception $e) {
            Log::error('DirectSinch sendOtpDirect error: ' . $e->getMessage(), [
                'to' => $toNumber, 'quoteId' => $quoteId, 'otp' => $otpCode
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }




}
