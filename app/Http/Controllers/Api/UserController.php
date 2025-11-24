<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ServiceQuestion;
use App\Models\LeadPrefrence;
use App\Models\UserDetail;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Review;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Log, Mail, Validator
};
use Illuminate\Validation\Rule;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoCustomerQuestionAnswer;
use App\Helpers\Zoho\ZohoFinance;
use App\Services\ZeroBounceService;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\LoginHistory;
use App\Models\UserAccreditation;
use App\Models\AutobidStatusLog;
use App\Models\RecommendedLead;
use App\Models\LeadRequest;
use App\Models\UserResponseTime;
use App\Models\PlanHistory;
use App\Models\Plan;
use App\Models\AbandonedUser;
use App\Services\LeadService;
use App\Services\CompanyRegService;
use App\Helpers\Zoho\ZohoServiceLocations;
use App\Helpers\Zoho\ZohoEmails;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoLeadBuyers;
use App\Helpers\Zoho\ZohoQuestionAnswer;
use App\Helpers\Zoho\ZohoQuoteCustomers;
use App\Helpers\Zoho\ZohoService;
use App\Helpers\Zoho\ZohoSocialMedia;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Models\NotificationSetting;

class UserController extends Controller
{

    protected $zeroBounceService;

    public function __construct(ZeroBounceService $zeroBounceService)
    {
        $this->zeroBounceService = $zeroBounceService;
    }

    public function index()
    {
        return response()->json(User::all(), 200);
    }

    public function getSellerDashboardStats(Request $request, LeadService $ls){
        $userId = $request->user_id;
        $user = User::where('id', $userId)->first();

        if(!empty($user)){

            $leadsBQ = $ls->getSellerLeadsBaseQuery($userId);
            // print_r($leadsBQ->toRawSql());
            // exit;

            //unread leads
            $unreadLeads = (clone $leadsBQ)->where('status','new')->get();
            $unreadLeads = $ls->leadsAccordingTOSellerPref($userId, $unreadLeads);
            $leads['unread_leads_count'] = count($unreadLeads);

            //total leads
            $totalLeads = $leadsBQ->get();
            $totalLeads = $ls->leadsAccordingTOSellerPref($userId, $totalLeads);
            $leads['total_leads_count'] = count($totalLeads);

            $data['leads'] = $leads;
            //user services
            $services  = UserService::with(['category'])->where('user_id', $userId)->get();
            // Transform services to include only category name
            $data['services'] = $services->map(function ($service) {
                return [
                    'id' => optional($service->category)->id,
                    'name' => optional($service->category)->name
                ];
            });
            $data['service_locations'] = UserServiceLocation::where('user_id',$userId)
                ->select(['miles','postcode','city'])->groupBy('miles', 'postcode', 'city')->get();

            //user plan
            $primaryCategory = $user->primary_category;
            $planHistory = PlanHistory::where('user_id',$userId)->orderBy('id','desc')->first();
            if(!empty($planHistory)){
                $plans = Plan::where('category_id', $primaryCategory)->where('status',1)->where('plan_type','normal')->orderBy('id','DESC')->get();
            }else{
                $plans = Plan::where('category_id', $primaryCategory)->where('status',1)->where('plan_type','starter')->orderBy('id','DESC')->get();
            }
            $data['plans'] = $plans;

            //user profile informations
            $profileInfo['name'] = $user->name;
            $profileInfo['percentage_completed'] = $user->getProfileCompletionPercentage();

            $data['profile_info'] = $profileInfo;

            //account details
            $data['account_details'] = [
                'plan_type' => $planHistory->plan_name ?? 'None',
                'credits' => $user->total_credit ?? 0
            ];

            //response count prending list + hired list
            $pendingCount = RecommendedLead::where('seller_id', $userId)->where('status', '<>', 'hired')->count();
            $hiredCount = RecommendedLead::where('seller_id', $userId)->where('status', 'hired')->count();

            $data['response'] = [
                'response_count' => $pendingCount + $hiredCount
            ];



            return $this->sendResponse('Seller Profile.', $data);
        }

        return $this->sendError('No user found!');
    }

    public function getSellerProfile(Request $request){


        if(!empty($request->seller_id_magic)){
            $userDetails = User::where('remember_token',$request->seller_id_magic)->first();
           
          if (!$userDetails) {
                return $this->sendError('The magic link you used is invalid or has expired. Please request a new link and try again.');
            }
           
            $sellerId = $userDetails->id;

            if(!empty($userDetails->id)){
                LoginHistory::create([
                    'user_id' => $userDetails->id,
                    'login_at' => Carbon::now(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                 CustomHelper::runInBackground(function() use ($sellerId) {
                    app(ZohoService::class)->updateZohoLastLogin($sellerId);
                });
            }
        }
        else{

        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer|exists:users,id',
            ], [
            'seller_id.required' => 'Seller id is required.',
            'seller_id.exists' => 'Seller id does not exists.',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $sellerId = "";

            if(empty($request->seller_id)){
                $sellerId = $request->user_id;
            }else{
                $sellerId = $request->seller_id;
            }
        }

        $buyerId = $request->buyer_id;
        $leadId = $request->lead_id;

        $ratings = CustomHelper::getAverageRating($sellerId);

        $user = User::where('id',$sellerId)->first();

        //percentage completed
        $user['percentage_completed'] = $user->getProfileCompletionPercentage();
        $token = $user->createToken('authToken', ['user_id' => $sellerId])->plainTextToken;
        $user['remember_tokens'] = $token;
        //for hired list count
        $hireCount = RecommendedLead::where('seller_id', $sellerId)
            ->where('status','hired')
            ->count();

        $user['hire_count'] = $hireCount;

        $replyCount = RecommendedLead::where('lead_id', $leadId)
            ->where('seller_id',$sellerId)
            ->where('buyer_id', $buyerId)
            ->count();
        $user['lead_purchased'] = $replyCount > 0 ? 1 : 0;
        $responseTime = UserResponseTime::where('seller_id', $sellerId)->value('average');
        $responseTime = !empty($responseTime) ? $responseTime : 15;
        $user['response_time'] = CustomHelper::formatTimeDuration($responseTime);
        $user['user_details'] = UserDetail::where('user_id',$sellerId)->first();
        $user['reviews'] = Review::where('user_id',$sellerId)->get();
        $user['reviews_count'] = $ratings['total_reviews'] ?? 0;
        $user['accreditations'] = UserAccreditation::where('user_id',$sellerId)->get();
        $user['photos'] = UserDetail::where('user_id',$sellerId)->first();
        $user['services'] = UserService::where('user_id',$sellerId)->with(['userServices'])->get();
        $user['qa'] = \DB::table('profile_q_a_s')->where('user_id',$sellerId)->get();

        return $this->sendResponse('Seller Profile.', $user);
    }





    public function registration(Request $request){

        $aVals = $request->all();
        $auto_bid = $request->auto_bid;
        $loggedUser = $request->loggedUser;//For checking seller/buyer
        $users = User::where('email',$request->email)->first();
        if(!empty($users) && $users != ''){
            return $this->sendError('Email already exists');
        }

        if (isset($aVals['phone']) && !empty($aVals['phone'])) {
            $cleanPhone = preg_replace('/\s+/', '', $aVals['phone']); // remove spaces
            if (strpos($cleanPhone, '+44') !== 0) {
                $cleanPhone = ltrim($cleanPhone, '0');
                $aVals['phone'] = '+44' . $cleanPhone;
            } else {
                $aVals['phone'] = $cleanPhone;
            }
        }


        if($aVals['form_status'] == 1){
            $validator = self::validators($aVals,$loggedUser);

            if ($validator->fails()) {
                return $this->sendError($validator->errors());
            }

            $result = $this->zeroBounceService->validateEmail($request->email);
            if (isset($result['status']) && $result['status'] === 'invalid') {
                return $this->sendError('Email is Invalid');
            }
            if($request->company_reg_number){
                $companyRegService = new CompanyRegService();
                $companyDetails = $companyRegService->getCompanyDetails($request->company_reg_number);
                if (isset($companyDetails['status']) && $companyDetails['status'] === 404) {
                    return $this->sendError('Company is Invalid');
                }
            }

            $passwordRandomString = Str::random(8);
            $aVals['password'] = Hash::make($passwordRandomString);
            $randomNumber = rand(1000, 5000);
            $aVals['total_credit'] = 0;
            $aVals['business_profile_name'] = $request->businessname;

            $user = User::create($aVals);
            if ($user->id) {
                $now = now();
                NotificationSetting::insert([
                    [
                        'user_id'   => $user->id,
                        'noti_name' => 'buyer_email_new_lead',
                        'noti_value'=> 1,
                        'user_type' => 'buyer',
                        'noti_type' => 'email',
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ],
                    [
                        'user_id'   => $user->id,
                        'noti_name' => 'buyer_email_customer_closing_leads',
                        'noti_value'=> 1,
                        'user_type' => 'buyer',
                        'noti_type' => 'email',
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ],
                    [
                        'user_id'   => $user->id,
                        'noti_name' => 'buyer_email_customer_hiring_me',
                        'noti_value'=> 1,
                        'user_type' => 'buyer',
                        'noti_type' => 'email',
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ],
                    [
                        'user_id'   => $user->id,
                        'noti_name' => 'buyer_browser_new_lead',
                        'noti_value'=> 1,
                        'user_type' => 'buyer',
                        'noti_type' => 'browser',
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ],
                    [
                        'user_id'   => $user->id,
                        'noti_name' => 'buyer_browser_customer_sending_message',
                        'noti_value'=> 1,
                        'user_type' => 'buyer',
                        'noti_type' => 'browser',
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ],
                    [
                        'user_id'   => $user->id,
                        'noti_name' => 'buyer_browser_new_review',
                        'noti_value'=> 1,
                        'user_type' => 'buyer',
                        'noti_type' => 'browser',
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ],
                ]);
            }


            $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
            $user->update(['remember_token' => $token]);

            $userdetails = UserDetail::where('user_id',$user->id)->first();

            if(empty($userdetails))
            {
                UserDetail::create([
                    'user_id'  => $user->id,
                    'is_autobid'  =>$auto_bid,
                    'billing_contact_name' => $request->name,
                    'billing_address1' => $request->apartment,
                    'billing_address2' => $request->address,
                    'billing_city' => $request->city,
                    'billing_postcode' => ($request->zipcode) ? $request->zipcode : $request->zipcode_old ,
                    'billing_phone' => $request->phone,
                    'billing_vat_register' => 1,
                ]);

                if($auto_bid == 1){
                    $data['user_id'] = $user->id;
                    $data['action'] = 'enabled';
                    AutobidStatusLog::insertGetId($data);
                }

            }
              // Check if service_id is an array or convert it to one
            $cleanedServiceId = str_replace(' ', '', $aVals['service_id']);
            $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $cleanedServiceId);

            if (!empty($serviceIds))
            {
                $user->primary_category = $serviceIds[0];
                $user->save();
            }
            $locationIds = [];
            $questionIds = [];
            $serviceAllIds = [];
            foreach ($serviceIds as $index => $serviceId) {
                $aLocations = []; // Reset for each iteration
                // Create a separate row for each service_id
                $service = UserService::createUserService($user->id, $serviceId);
                $serviceAllIds[] = $service->id;
                if ($service) {
                    $aLocations['service_id'] = $serviceId;
                    $aLocations['user_service_id'] = $service->id;
                    $aLocations['user_id'] = $user->id;

                    if($index === 0){ // for primary category
                        $aLocations['miles'] = !empty($aVals['miles1']) ? $aVals['miles1'] : 0;
                        $aLocations['nation_wide'] = !empty($aVals['nation_wide']) ? $aVals['nation_wide'] : 0;
                        $aLocations['postcode'] = $aVals['postcode'];
                        $aLocations['city'] = $aVals['cities'];
                        $aLocations['type'] = !empty($aVals['nation_wide']) ? "Nationwide" : "Distance";
                        $aLocations['coordinates'] = $aVals['coordinates'];
                    }else{
                        if(!empty($aVals['expanded_radius'])){
                            $aLocations['miles'] = $aVals['miles2'] + $aVals['expanded_radius'];
                        }else{
                            $aLocations['miles'] = $aVals['miles2'];
                        }
                        $aLocations['nation_wide'] = 0;
                        $aLocations['postcode'] = !empty($aVals['postcode2']) ? $aVals['postcode2'] : "000000";
                        $aLocations['city'] = null;
                        $aLocations['type'] = "Distance";
                        $aLocations['coordinates'] = "[]";
                    }

                    $location=UserServiceLocation::createUserServiceLocation($aLocations);

                    $locationIds[] = $location->id;

                }
                //save answer to preferences
                $leadPreferences = ServiceQuestion::where('category', $serviceId)->get();

                foreach ($leadPreferences as $question) {
                    // Get default options from 'answer' column of ServiceQuestion table
                    $arrQues = json_decode( $question->answer, true);
                    $catAns = "";
                    foreach($arrQues as $q){
                        if(!empty($catAns)){
                            $catAns .= ',';
                        }
                        $catAns .= $q['option'];
                    }
                    $defaultOptions = $catAns ?? '';

                    // Check if user already has a saved answer for this question
                    $existingAnswer = LeadPrefrence::where('question_id', $question->id)
                        ->where('user_id', $user->id)
                        ->pluck('answers')
                        ->first();

                    // Use existing answer or fall back to all options from ServiceQuestion.answer
                    $answerToUse = $existingAnswer ?? $defaultOptions;

                    // Clean the format: remove extra spaces around commas and trailing commas
                    $cleanedAnswer = preg_replace('/\s*,\s*/', ',', $answerToUse);
                    $cleanedAnswer = rtrim($cleanedAnswer, ',');

                    // Insert or update the lead preference
                    $leadPref=LeadPrefrence::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'service_id' => $serviceId,
                            'question_id' => $question->id,
                        ],
                        [
                            'answers' => $cleanedAnswer,
                        ]
                    );

                    $questionIds[] = $leadPref->id;

                }

            }
            $user->remember_tokens = $token;

            // CustomHelper::runInBackground(function() use ($user, $serviceAllIds, $locationIds, $questionIds, $passwordRandomString, $auto_bid, $serviceIds) {
            //     if ($user->user_type == 1) {

            //         app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($user->id);
            //         if($user->form_status ==1){
            //             ZohoEmails::sendWelcomeEmail($user->id, $passwordRandomString);
            //         }

            //         //app(ZohoSocialMedia::class)->integrateSocialLinks($user->id);
            //         app(ZohoService::class)->integrateService($user->id, $serviceAllIds);
            //         app(ZohoServiceLocations::class)->integrateServiceLocations($user->id, $locationIds);
            //         app(ZohoQuestionAnswer::class)->integrateServiceQa($user->id,$serviceIds);

            //         if ($auto_bid == 0) {
            //             //app(self::class)->sendEncouragementEmail(['userId' => $user->id]);
            //         }

            //     } elseif ($user->user_type == 2) {
            //         app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($user->id);
            //     }
            // });


            if ($user->user_type == 1) {

                // Zoho Lead Buyers
                CustomHelper::runInBackground(function() use ($user) {
                    app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($user->id);
                });

                // Welcome Email
                if($user->form_status == 1){
                    CustomHelper::runInBackground(function() use ($user, $passwordRandomString) {
                        ZohoEmails::sendWelcomeEmail($user->id, $passwordRandomString);
                    });
                }

                // Service Integration
                CustomHelper::runInBackground(function() use ($user, $serviceAllIds) {
                    app(ZohoService::class)->integrateService($user->id, $serviceAllIds);
                });

                // Service Locations
                CustomHelper::runInBackground(function() use ($user, $locationIds) {
                    app(ZohoServiceLocations::class)->integrateServiceLocations($user->id, $locationIds);
                });

                // Service QA
                CustomHelper::runInBackground(function() use ($user, $serviceIds) {
                    app(ZohoQuestionAnswer::class)->integrateServiceQa($user->id, $serviceIds);
                });

                // Auto-bid check (currently commented)
                if ($auto_bid == 0) {
                    // CustomHelper::runInBackground(function() use ($user) {
                    //     app(self::class)->sendEncouragementEmail(['userId' => $user->id]);
                    // });
                }

            } elseif ($user->user_type == 2) {

                CustomHelper::runInBackground(function() use ($user) {
                    app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($user->id);
                });

            }
            return $this->sendResponse('Registration successfully.', $user);

        }else{

            $user = AbandonedUser::create($aVals);
            $rel = [
                'user_id' => $user->id,
                'email' => $user->email,
                'created_at' => now(),
                'updated_at' => now(),
            ];


            CustomHelper::runInBackground(function() use ($user, $rel) {
                app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($user->id,'abandon');
                app(self::class)->sendIncompleteRegEmail(['userId' => $user->id]);
            });
            return $this->sendResponse('Abandoned Lead Buyers registered Successfully', $rel);
        }

    }

    public function sendIncompleteRegEmail($payload)  // sendIncompleteRegEmail
    {
        Log::info('Incomplete registrtion inserted');

        $sentCount = 0;
        $userId = $payload['userId'] ?? null;
        $users = AbandonedUser::whereNotNull('zoho_record_id')
            ->where('form_status',0)
            ->where('id',$userId)
            // ->with(['emailLogs' => function ($q) {
            //     $q->where('setting_name', 'Send Incomplete Registration Email')->latest();
            // }])
            ->get();

        ZohoEmails::sendIncompleteRegistrationEmail($userId);

        return response()->json([
            'status' => 'success',
            'message' => "$sentCount incomplete registration email(s) sent.",
            'timestamp' => now()->toDateTimeString()
        ]);
    }
    public function checkEmailId(Request $request): JsonResponse{
        if(empty($request->email)){
            return $this->sendError('email: Email is required!');
        }
        $users = User::where('email',$request->email)->first();
        if(!empty($users) && $users != ''){
            return $this->sendError('Your account is already registered with this email, Please contact us if this is not correct.');
        }
        $result = $this->zeroBounceService->validateEmail($request->email);
        if (isset($result['status']) && $result['status'] === 'invalid') {
            return $this->sendError('Email is Invalid');
        }


        return $this->sendResponse('Valid Email');
    }

    public function checkPhoneNumber(Request $request): JsonResponse{
        $validator = Validator::make($request->all(), [
                'phone' => [
                    'required',
                    'unique:users,phone',
                    'regex:/^\d{10}$/'
                ]
              ], [
                'phone.required' => 'Phone number is required.',
                'phone.unique'   => 'Your account is already registered with this phone number, Please contact us if this is not correct.',
                'phone.regex'    => 'Enter a valid  phone number ',
                ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        return $this->sendResponse('Valid Phone Number');
    }

    public function checkCompanyNameWithoutReg(Request $request): JsonResponse
    {
        $companyName = trim($request->input('companyname'));

        if (!$companyName) {
            return $this->sendError('Company name is required.');
        }

        $exists = User::where('company_name', $companyName)
            ->exists();

        if ($exists) {
            return $this->sendError('Your account is already registered with this Company Name. Please contact us if this is not correct.');
        }

        return $this->sendResponse(true, 'Valid Company Name');
    }


    public function checkCompanyName(Request $request): JsonResponse{
        $validator = Validator::make($request->all(), [
            'company_reg_number' => 'required|unique:users,company_reg_number',
        ], [
            'company_reg_number.required' => 'Company Reg No. is required.',
            'company_reg_number.unique'   => 'Your account is already registered with this Company Reg No, Please contact us if this is not correct.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        // Validate company_name only if Step 1 passed
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|unique:users,company_name',
        ], [
            'company_name.required' => 'Company Name is required.',
            'company_name.unique'   => 'Your account is already registered with this Company Name, Please contact us if this is not correct.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if($request->company_reg_number){
            $companyRegService = new CompanyRegService();
            $companyDetails = $companyRegService->getCompanyDetails($request->company_reg_number);
            if(empty($companyDetails)){
                return $this->sendError(['company_reg_number' => ['Company Reg No. is Invalid']]);
            }else if (isset($companyDetails['status']) && $companyDetails['status'] === 404) {
                return $this->sendError(['company_reg_number' => ['Company Reg No. is Invalid']]);
            }else if($companyDetails['company_name'] !== $request->company_name){
                return $this->sendError(['company_name' => ['Company Name is mismatching!']]);
            }
        }

        return $this->sendResponse('Valid Company Name');
    }

    public function checkCompanyLocation(Request $request): JsonResponse{
        $validator = Validator::make($request->all(), [
                'company_location' => [
                    'required',
                    'unique:users,company_location',
                    'min:10',
                    'max:255',
                    'regex:/^[A-Za-z0-9\s,.\-]+$/'
                ]
              ], [
                'company_location.required' => 'Address is required.',
                'company_location.unique'   => 'Your account is already registered with this address,
                    Please contact us if this is not correct.',
                'company_location.regex'    => 'Enter a valid address ',
                ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        return $this->sendResponse('Valid Address');
    }

    public function validators($data,$loggedUser){
        if($loggedUser == 1){
            $validator = Validator::make($data, [
                'miles1' => 'required',
                'miles2' => 'sometimes',
                'postcode' => 'required',
                'service_id' => 'required',
                'name' => 'required',
                'email' => 'required|email|unique:users,email'
              ], [
                'email.unique' => 'Your account is already registered with this email, Please contact us if this is not correct.'
            ]);
        }else{
            $validator = Validator::make($aVals, [
                'name' => 'sometimes',
                'email' => 'required|email|unique:users,email',
                // 'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&]/|max:16'
              ], [
                'email.unique' => 'Your account is already registered with this email, Please contact us if this is not correct.'
            ]);
        }
        return $validator;
    }

    public function createLoginMagicLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $user = User::where('email',$request->email)->where('form_status', '1')->first();

        if(empty($user)){
            return $this->sendError('Given email not found!');
        }
        $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
        $user->update(['remember_token' => $token]);

        $user->remember_tokens = $token;

        ZohoEmails::sendLoginMagicLinkEmail($user, $token);
        return $this->sendResponse('An email is on the way.');
    }


    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        if(!Auth::attempt(['email'=>$request->email, 'password'=>$request->password])){
            return $this->sendError("Unable to Login due to Invalid Credentials");
        }

        $user = Auth::user();

        if ($user && $user->form_status != 0) {
                if($user->status == 0)
                {
                    return $this->sendError("User is inactive");
                }
                 // Update last_login
                $user->last_login = Carbon::now();
                $user->save();

                // Insert into login_histories
                LoginHistory::create([
                    'user_id' => $user->id,
                    'login_at' => Carbon::now(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                CustomHelper::runInBackground(function() use ($user) {
                    app(ZohoService::class)->updateZohoLastLogin($user->id);
                });

                $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
                $user->update(['remember_token' => $token]);
                $user->remember_tokens = $token;
                return $this->sendResponse('Login Successfully.', $user);
        } else {
                return $this->sendError('You are not register or invalid user');
        }

    }



    public function switchUser(Request $request)
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];
        $userType = $aVals['user_type']; // 1 = buyer, 2 = seller

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // If user_type is already 3, don't change it again — only update active_status
        if ($user->user_type == 3) {
            if ($userType == 1) {
                $user->active_status = 1;
                $mode = 'Seller';
            } elseif ($userType == 2) {
                $user->active_status = 2;
                $mode = 'Buyer';
            } else {
                return response()->json(['error' => 'Invalid user type'], 400);
            }

            $user->save();
            CustomHelper::runInBackground(function() use  ($userId,$userType) {
                if ($userType == 2) {
                    $alreadySent = EmailLog::where('user_id', $userId)
                        ->whereDate('created_at', Carbon::today())
                        ->where('setting_name', 'Switch Account From Lead Buyer')
                        ->exists();
                    if (!$alreadySent) {
                        ZohoEmails::switchEmailToQuoteAccount($userId);
                    }
                }
                 if ($userType == 1) {

                    $alreadySent = EmailLog::where('user_id', $userId)
                        ->whereDate('created_at', Carbon::today())
                        ->where('setting_name', 'Switch Account From Quote Customer')
                        ->exists();
                    if (!$alreadySent) {
                        ZohoEmails::switchEmailToLeadAccount($userId);
                    }
                }
            });
            return $this->sendResponse('Switched to ' . $mode);
        }

        // Update user_type and active_status if user_type is not 3 yet
        if ($userType == 2) {
            if ($user->user_type == 1) {
                $user->user_type = 3; // Upgrade to Both
            } else {
                $user->user_type = 2; // Only Seller
            }
            $user->active_status = 2;
            $mode = 'Buyer';
        } elseif ($userType == 1) {
            if ($user->user_type == 2) {
                $user->user_type = 3; // Upgrade to Both
            } else {
                $user->user_type = 1; // Only Buyer
            }
            $user->active_status = 1;
            $mode = 'Seller';
        } else {
            return response()->json(['error' => 'Invalid user type'], 400);
        }

        $user->save();

        CustomHelper::runInBackground(function() use ($userId,$userType){
            if ($userType == 2) {
                $alreadySent = EmailLog::where('user_id', $userId)
                    ->whereDate('created_at', Carbon::today())
                    ->where('setting_name', 'Switch Account From Lead Buyer')
                    ->exists();
                if (!$alreadySent) {
                    ZohoEmails::switchEmailToQuoteAccount($userId);
                }
            }
            if ($userType == 1) {
                $alreadySent = EmailLog::where('user_id', $userId)
                    ->whereDate('created_at', Carbon::today())
                    ->where('setting_name', 'Switch Account From Quote Customer')
                    ->exists();
                if (!$alreadySent) {
                    ZohoEmails::switchEmailToLeadAccount($userId);
                }

            }
        });
        return $this->sendResponse('Switched to ' . $mode);
    }



    public function editProfile(Request $request): JsonResponse
    {
        $users = User::where('id',$request->user_id)->first();
        return $this->sendResponse(__('User Profile Data'), $users);
    }

    public function updateProfile(Request $request)
    {
        $aVals = $request->all();
        $validator = $validator = Validator::make($aVals, [
            'email' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $userId = $request->user_id;
        $user = User::where('id',$userId)->update([
            'email'=>$request->email,
            'phone'=>$request->phone,
            'sms_notification_no'=>$request->sms_notification_no,
        ]);

        CustomHelper::runInBackground(function() use ($userId) {
            app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($userId);
        });
        return $this->sendResponse('User Profile updated');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            // Remove token from `remember_token` column
            $user->update(['remember_token' => null]);

            // Revoke the current token (for Sanctum)
            $request->user()->currentAccessToken()->delete();

            // Optionally revoke all tokens (for broader logout)
            // $user->tokens()->delete();

            return response()->json([
                'message' => 'Logout successful'
            ], 200);
        }

        return response()->json([
            'error' => 'Unauthenticated'
        ], 401);
    }

    public function updateProfileImage(Request $request)
    {
        try {
            $userId = $request->user_id;
             $users = User::where('id',$userId)->first();

                if ($request->hasFile('image')) {
                    $imagePath =  CustomHelper::fileUpload($request->image,'users');
                    // $imagePath = $this->uploadImage($request->file('image'), 'users');
                    $users->profile_image = $imagePath;
                }


            if($users->save()){
                return $this->sendResponse(__('Profile Image Updated Successfully'));
            }else{
                return $this->sendError('Something went wrong. Please try again later!');
            }


        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }

    public function changePassword(Request $request){
        $userId = $request->user_id;
        $password = $request->password;
        $user = User::where('id',$userId)->first();
        $user->update(['password' => $password]);
        return $this->sendResponse(__('Password changed Successfully'));
    }

    public function fetch_company_details($regNumber, Request $request){
        $userId = $request->query('user_id');

        $companyRegService = new CompanyRegService();
        $companyDetails = $companyRegService->getCompanyDetails($regNumber);
        if(isset($companyDetails['company_name'])){
            try {
                $this->checkCompanyExists($companyDetails['company_name'],$userId);
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage()); // returns a JsonResponse
            }
        }
        return $companyDetails;
    }

    private function checkCompanyExists(string $companyName,$userId = null): void
    {
        $query = User::where('company_name', trim($companyName));
        if ($userId) {
            $query->where('id', '!=', $userId);
        }

        if ($query->exists()) {
            throw new \Exception('Your account is already registered with this Company Name. Please contact us if this is not correct.');
        }
    }




    // ActivityLog
    public function leadPurchaseStatusUpdateLog(Request $request, $lead_id, $seller_id, $buyer_id, $log){
        // echo $lead_id .'; ' .$seller_id .'; ' .$buyer_id .'; ' .$log;

        $leadTime = LeadRequest::where('id',$lead_id)->pluck('created_at')->first();

        $leadtime = Carbon::parse($leadTime)->setTimezone('Europe/London');
        $createdAt = Carbon::parse(date('Y-m-d H:i:s'))->setTimezone('Europe/London');

        $diffInMinutes = round(abs($leadtime->diffInMinutes($createdAt)));
        if ($diffInMinutes < 60) {
            $duration = $diffInMinutes;
        } else {
            $hours = round($diffInMinutes / 60);
            $duration = $hours;
        }

        $data['lead_id'] = $lead_id;
        $data['from_user_id'] = $seller_id;
        $data['to_user_id'] = $buyer_id;
        $data['activity_name'] = $log;
        $data['contact_type'] = 'email';
        $data['duration'] = $duration;
        $data['duration_minutes'] = $diffInMinutes;
        $data['created_at'] = date('Y-m-d H:i:s');

        ActivityLog::insertGetId($data);
        $baseUrl = CustomHelper::setting_value('react_base_url', config('app.react_base_url'));
        return redirect($baseUrl .'sellers/leads/my-responses');
    }

}
