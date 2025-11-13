<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\Category;
use App\Models\Bid;
use App\Models\UserDetail;
use App\Models\UserAccreditation;
use App\Models\UserServiceDetail;
use App\Models\ProfileQuestion;
use App\Models\ProfileQA;
use App\Models\UserCardDetail;
use App\Models\UserService;
use App\Models\LeadRequest;
use App\Models\PurchaseHistory;
use App\Models\Plan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Otp;
use App\Models\AbandonedUser;
use App\Models\UserServiceLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Log as FacadesLog, Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use Log;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoLeadBuyers;
use App\Helpers\Zoho\ZohoQuoteCustomers;
use App\Models\SmsLog;
use \Carbon\Carbon;
use Exception;
use Illuminate\Container\Attributes\Log as AttributesLog;
use GuzzleHttp\Client;
use App\Models\ServiceQuestion;

class ApiController extends Controller
{
    public function getProgressPercentage(Request $request){
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer|exists:categories,id',
            'questions' => 'required'
        ], [
            'service_id.exists' => 'Provided service id does not exists.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $sQuestions = ServiceQuestion::where('category',$request->service_id)->get()->toArray();
        $requestQuestions = json_decode($request->questions, true);
        $serviceQuestions = [];
        $leadQuestions = [];

        foreach($sQuestions as $sq){
            $temp['question_no'] = $sq['question_no'];
            $temp['ques'] = $sq['questions'];
            $temp['question_type'] = $sq['question_type'];
            $ans = [];
            $ansDecoded = json_decode($sq['answer'], true);
            foreach($ansDecoded as $a){
                $temp2['option'] = $a['option'];
                $temp2['next_question'] = $a['next_question'];
                $ans[] = $temp2;
            }
            $temp['ans'] = $ans;
            $serviceQuestions[] = $temp;
        }

        foreach($requestQuestions as $rq){
            $temp3['ques'] = $rq['ques'];
            $temp3['ans'] = $rq['ans'];
            $leadQuestions[] = $temp3;
        }

        // Map questions by question_no
        $questionMap = [];
        foreach ($serviceQuestions as $q) {
            $questionMap[$q['question_no']] = $q;
        }

        // Map question text to question_no for easy lookup
        $quesToNo = [];
        foreach ($serviceQuestions as $q) {
            $quesToNo[$q['ques']] = $q['question_no'];
        }

        // Count all compulsory questions initially
        $totalCount = 0;
        $countedQuestions = [];

        foreach ($serviceQuestions as $q) {
            if ($q['question_type'] === 'compulsory') {
                $totalCount++;
                $countedQuestions[$q['question_no']] = true;
            }
        }

        // Include all answered questions as well if not counted yet
        foreach ($leadQuestions as $lead) {
            $qNo = $quesToNo[$lead['ques']] ?? null;
            if ($qNo !== null && !isset($countedQuestions[$qNo])) {
                $totalCount++;
                $countedQuestions[$qNo] = true;
            }
        }

        $answeredCount = count($leadQuestions);
        $percentage = $totalCount > 0 ? round(($answeredCount / $totalCount) * 100) : 0;

        // echo "<pre>";
        // print_r($serviceQuestions);
        // print_r($leadQuestions);
        // print_r([
        //     'total_count' => $totalCount,
        //     'answered_count' => $answeredCount,
        //     'percentage' => $percentage
        // ]);
        return $this->sendResponse('Progress Percentage', [
            'total_count' => $totalCount,
            'answered_count' => $answeredCount,
            'percentage' => $percentage
        ]);
    }


    public function getCityName(Request $request){
        $validator = Validator::make($request->all(), [
            'postcode' => 'required'
            ], [
            'postcode.required' => 'Postcode is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $res = CustomHelper::getCityNameFromPostcode($request->postcode);
        if(!empty($res['valid']) && $res['valid'] == true){
            return $this->sendResponse('City Name Found', $res);        
        }else{
            return $this->sendError('City Name Not Found', $res);
        }
    }
    public function getCategories()
    {
        $aRows = Category::where('status',1)->get();
        return $this->sendResponse(__('Category Data'),$aRows);
    }

    public function homeServices()
    {

        $aRows = Category::where('is_home',1)->where('parent_id','<>', 0)->orderBy('id','DESC')->where('status',1)->get();
        foreach($aRows as $value){
            $value['baseurl'] = url('/').Storage::url('app/public/images/category');
        }

        return $this->sendResponse(__('Home Services'),$aRows);
    }

    public function popularServices()
    {
        $oneWeekAgo = Carbon::now()->subWeek();

        $aRows = Category::where('is_popular', 1)
            ->where('parent_id', '<>', 0)
            ->where('status', 1)
            ->withCount(['leadRequests as leads_count' => function ($query) use ($oneWeekAgo) {
                $query->where('created_at', '>=', $oneWeekAgo);
            }])
            ->orderByDesc('leads_count')
            ->get();

        foreach ($aRows as $value) {
            $value['baseurl'] = url('/') . Storage::url('app/public/images/category');
        }

        return $this->sendResponse(__('Popular Services'), $aRows);
    }

    public function popularUserServices(Request $request)
    {
        $userId = $request->user_id;
        $oneWeekAgo = Carbon::now()->subWeek();

        // get services already added by the user
        $userServices = UserService::where('user_id', $userId)
            ->pluck('service_id')
            ->toArray();

        $aRows = Category::where('is_popular', 1)
            ->where('parent_id', '<>', 0)
            ->where('status', 1)
            ->whereHas('serviceQuestions')
            ->whereNotIn('id', $userServices)
            ->withCount(['leadRequests as leads_count' => function ($query) use ($oneWeekAgo) {
                $query->where('created_at', '>=', $oneWeekAgo);
            }])
            ->orderByDesc('leads_count')
            ->get();

        foreach ($aRows as $value) {
            $value['baseurl'] = url('/') . Storage::url('app/public/images/category');
        }

        return $this->sendResponse(__('Popular User Services'), $aRows);
    }

    private function flattenCategories($categories) {
        $result = [];

        foreach ($categories as $category) {
            // only include if show_in_search = 1
            if (isset($category['show_in_search']) && $category['show_in_search'] == 1) {
                $item = $category;
                unset($item['home_subsectors'], $item['subsectors']);
                $result[] = $item;
            }

            // recurse into children
            if (!empty($category['home_subsectors'])) {
                $result = array_merge($result, $this->flattenCategories($category['home_subsectors']));
            }

            if (!empty($category['subsectors'])) {
                $result = array_merge($result, $this->flattenCategories($category['subsectors']));
            }
        }

        return $result;
    }

    public function allServices()
    {
        $categories = Category::with(['homeSubsectors'])
            ->where('is_home', 1)
            ->where('parent_id', '0')->get()->toArray();
        $result = [];
        foreach ($categories as $category) {
            $item = $category;
            unset($item['home_subsectors'], $item['subsectors']);
            $item['subcategory'] = $this->flattenCategories($category['home_subsectors']);
            $item['baseurl'] = url('/') . Storage::url('app/public/images/category');
            $result[] = $item;
        }

        return $this->sendResponse(__('Category Data'), $result);
    }
    public function leadsSearchServices(Request $request)
    {
        $search = $request->search; // Get search keyword from request
        $serviceid = $request->serviceid; // Get search keyword from request

        // Check if search keyword is provided; otherwise, return empty
        if (empty($search)) {
            $categories = [];
            return $this->sendResponse(__('Category Data'), $categories);
        }
        if(!empty($serviceid)){
            // Convert serviceid into an array
            $serviceIds = explode(',', $serviceid);
            $categories = Category::where('status', 1)
                            ->whereNotIn('id', $serviceIds)
                            ->where(function ($query) use ($search) {
                                $query->where('name', 'LIKE', "%{$search}%")
                                    ->orWhere('description', 'LIKE', "%{$search}%");
                            })
                            ->get();
        }else{
            $categories = Category::where('status', 1)
                              ->where(function ($query) use ($search) {
                                  $query->where('name', 'LIKE', "%{$search}%")
                                        ->orWhere('description', 'LIKE', "%{$search}%");
                              })
                              ->get();
        }



        return $this->sendResponse(__('Category Data'), $categories);
    }
    public function searchServices(Request $request)
    {
        $search = $request->search; // Get search keyword from request

        $serviceTitle = $request->serviceTitle;

        $search = substr((string) $search, 0, 4);

        $serviceid = $request->serviceid; // Get search keyword from request
        $base = Category::query()
        ->where('status', 1)
        ->where('show_in_search', 1);

            if ($serviceid > 0) {
                $base->where('id', '!=', $serviceid);
            }

            // if (!empty($serviceTitle)  || $serviceTitle != null) {
            //     $base->where('slug', '!=', $serviceTitle);
            // }

        // Check if search keyword is provided; otherwise, return empty
        if ($search === '') {
        // You can order as you like; name asc is common for dropdowns
            $categories = $base
                ->orderBy('name')
                ->get(['id', 'name', 'description']); // keep payload lean if you want

            return $this->sendResponse(__('Category Data'), $categories);
        }
        if(!empty($serviceid)){
            $categories = Category::where('status', 1)
            ->where('id', '!=', $serviceid)
            ->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('tags', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
            })
            ->where('show_in_search', '1')
            ->get();
        }else{
            $categoriess = Category::where('status', 1)
                              ->where(function ($query) use ($search) {
                                  $query->where('name', 'LIKE', "%{$search}%")
                                        ->orWhere('tags', 'LIKE', "%{$search}%")
                                        ->orWhere('description', 'LIKE', "%{$search}%");
                              });
                              if (!empty($serviceTitle)  || $serviceTitle != null) {
                                    $categoriess->where('slug', '!=', $serviceTitle);
                                }
                              $categoriess->where('show_in_search', '1');
                         $categories= $categoriess->get();
        }



        return $this->sendResponse(__('Category Data'), $categories);
    }

    public function searchAvailableServices(Request $request)
    {
    $search = $request->search;
    $serviceid = $request->serviceid;
    $userId = $request->user_id; // frontend should pass user_id

    if (empty($search)) {
        return $this->sendResponse(__('Category Data'), []);
    }

    // Fetch user's existing services
    $userServices = UserService::where('user_id', $userId)
        ->pluck('service_id')
        ->toArray();

    $query = Category::select('categories.*')
        ->join('service_questions', 'categories.id', '=', 'service_questions.category')
        ->where('categories.status', 1)
        ->where(function ($q) use ($search) {
            $q->where('categories.name', 'LIKE', "%{$search}%")
              ->orWhere('categories.description', 'LIKE', "%{$search}%");
        })
        ->where('show_in_search', '1')
        ->distinct();

    if (!empty($serviceid)) {
        $query->where('categories.id', '!=', $serviceid);
    }

    // ✅ apply exclusion only if user already has services
    if (!empty($userServices)) {
        $query->whereNotIn('categories.id', $userServices);
    }
    $categories = $query->get();

    return $this->sendResponse(__('Category Data'), $categories);
    }


    //

    public function requestOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|min:10',
        ], [
            'phone_number.required' => 'Phone number is required.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $data['otp'] = random_int(1000, 9999);
        $data['phone_number'] = $request->phone_number;

        Otp::create($data);

        $user_id = $request->user_id;

        if ($user_id) {
            $updated = User::where('id', $user_id)->update(['otp' => $data['otp']]);

            if ($updated) {
                // fetch the updated user
                $user = User::find($user_id);

                if ($user && $user->otp) {

                    CustomHelper::runInBackground(function() use ($user_id) {
                        app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($user_id);
                    });
                    return $this->sendResponse('OTP created successfully');
                }
                return $this->sendError('OTP creation failed');
            }
            return $this->sendError('OTP creation failed');

        }
        return $this->sendError('OTP creation failed');
    }


    public function verifyOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|min:10',
            'otp' => 'required|min:4',
        ], [
            'phone_number.required' => 'Phone number is required.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $dbOtp = Otp::where('phone_number', $request->phone_number)
            ->where('otp_used', '0')
            ->orderBy('id','desc')->first();
        if(!empty($dbOtp)){
            if($dbOtp->otp == $request->otp){
                $data['otp_used'] = 1;
                Otp::where('id', $dbOtp->id)->update($data);
                return $this->sendResponse('OTP verified successfully');
            }else{
                return $this->sendError('Wrong OTP, try again!');
            }
        }

        return $this->sendError('Otp not found! Please generate OTP first.');
    }

    public function regOtps(Request $request, $email){
        $aRows = AbandonedUser::where('email', $email)->select(['name', 'email', 'otp'])->get();
        return $this->sendResponse('OTP Data',$aRows);
    }

    public function mailTest(Request $request){
        $dataUser['email'] = 'pushpeshsh@zuzucodes.com';
        $dataUser['fullName'] = 'Pushpesh Sharma';
        $dataUser['subject'] = "Thank you for contacting Localists – We've received your request";
        try {
            Mail::send('emails.contact_form.contact_form_user', $dataUser, function ($message) use ($dataUser) {
                $message->from('contactform@localistssenders.com');
                $message->to($dataUser['email']);
                $message->subject($dataUser['subject']);
            });
        } catch (\Throwable $e) {
            // Build full debug array, including previous exceptions
            $debug = [
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
                'trace'   => $e->getTraceAsString(),
                'previous' => [],
            ];

            $p = $e->getPrevious();
            while ($p) {
                $debug['previous'][] = [
                    'type'    => get_class($p),
                    'message' => $p->getMessage(),
                    'file'    => $p->getFile() . ':' . $p->getLine(),
                    'trace'   => $p->getTraceAsString(),
                ];
                $p = $p->getPrevious();
            }

            // Log full debug to laravel log
            Log::error('Mail send failed (detailed)', $debug);

            // Return full debug in response (only while debugging)
            return response()->json([
                'status' => 'error',
                'debug'  => $debug
            ], 500);
        }


        // $dataAdmin['to'] = 'pushpeshsh@zuzucodes.com';
        // $dataAdmin['fullName'] = 'Pushpesh Sharma';
        // $dataAdmin['email'] = 'pushpeshsh@zuzucodes.com';
        // $dataAdmin['phone'] = '+44 1234567890';
        // $dataAdmin['userType'] = '1';
        // $dataAdmin['user_message'] = 'Some message from user.';
        // $dataAdmin['subject'] = "New Contact Form Submission – Localists";
        // try {
        //     Mail::send('emails.contact_form.contact_form_admin', $dataAdmin, function ($message) use ($dataAdmin) {
        //         $message->from('contactform@localistssenders.com');
        //         $message->to($dataAdmin['to']);
        //         $message->cc(['zoofishan@zuzucodes.com', 'pushpesh@zuzucodes.com']); // <-- Add multiple CCs
        //         $message->subject($dataAdmin['subject']);
        //     });
        // } catch (\Throwable $e) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => $e->getMessage()
        //     ]);
        // }
    }

    public function testApi(Request $request, \App\Services\LeadService $leadService)
    {
        // $user_id = 13;
        // $baseQuery = $leadService->getSellerLeadsBaseQuery($user_id);
        // $allLeads = $baseQuery->orderBy('id', 'desc')->get();
        // print_r($allLeads->toArray());

        $start = microtime(true);
        $lead = LeadRequest::find($request->lead_id);
        \Log::info('lead took ' . (microtime(true) - $start) . ' seconds');

        $start = microtime(true);
        $result = $leadService->getAllSellers($lead);
        \Log::info('getAllSellers took ' . (microtime(true) - $start) . ' seconds');

        echo "<pre>";
        print_r($result);
        exit;
    }

    public function resendOtpEnable(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ], [
            'user_id.required' => 'Something Went wrong.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $userId = $request->input('user_id');

        return response()->json([
                'status' =>200,
                'user_id' =>$userId,
                'enable' => true,
                'message' => 'Resend Otp Button Enable Now'
            ], 400);

    }

    public function resendOtp(Request $request){


        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ], [
            'user_id.required' => 'Something Went wrong.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $userId = $request->input('user_id');
        if (! $userId) {
            return response()->json([
                'ok' => false,
                'message' => 'user_id is required'
            ], 400);
        }

        try {
        // Find user
        $user = AbandonedUser::find($userId);



        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Generate OTP (4 digits)
        $phoneOtp = random_int(1000, 9999);

        // Update user record
        $user->otp = $phoneOtp;   // make sure this column exists in your users table

        $user->save();

        if($user->phone){
            $this->sendOtpDirect($user->phone,$phoneOtp,$userId);
        }

        // (Optional) send OTP via SMS here, e.g. using your Sinch function


        CustomHelper::runInBackground(function() use ($userId) {
            app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($userId,'abandon');
        });
        return $this->sendResponse('OTP resent Successfully');

    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ], 500);
    }

    }


    public function updateSmsStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'quote_id' => 'required',
        ], [
            'quote_id.required' => 'Something Went wrong.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $quoteId = $request->input('quote_id');
        $status = $request->input('status');

        if (empty($quoteId)) {
            return response()->json([
                'ok' => false,
                'message' => 'Missing quote_id or status'
            ], 400);
        }

        try {
            // Find the user (adjust model/table if needed)
            $user = AbandonedUser::where('zoho_record_id', $quoteId)->first();

            if (! $user) {
                // not found: you can optionally create an audit record or return 404
                FacadesLog::info('SMS status update: user not found', ['quote_id' => $quoteId, 'status' => $status]);
                return response()->json([
                    'ok' => false,
                    'message' => 'No user found for this quote_id'
                ], 404);
            }

            // Update the otp status column
            $user->otp_sinch_status = $status;
            $user->save();

            FacadesLog::info('SMS status updated', ['quote_id' => $quoteId, 'status' => $status, 'user_id' => $user->id]);

            return response()->json([
                'ok' => true,
                'message' => 'Status updated',
                'quote_id' => $quoteId,
                'status' => $status
            ]);
        } catch (\Throwable $e) {
            FacadesLog::error('Error updating SMS status', ['error' => $e->getMessage(), 'payload' => $request->all()]);
            return response()->json([
                'ok' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    public function sendOtpDirect($toNumber, $otpCode, $userId)
    {

        $sinchKey    = CustomHelper::setting_value('sinch_key'); //"morB1J2tPJPO8kkvx0A8";
        $sinchSecret = CustomHelper::setting_value('sinch_secret'); //"OvgetB5Fx6gwCxwRA719yrJEV6gVco";

        $user = AbandonedUser::where('id',$userId)->first();
        $quoteId = $user->zoho_record_id;

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

            

            $finalStatus = $initialStatus;

            if ($messageId) {

                CustomHelper::runInBackground(function() use ($messageId, $quoteId, $client, $authHeader, $smsLog, $toNumber, $messageText, $otpCode) {
                    $statusUrl = "https://api.messagemedia.com/v1/messages/{$messageId}";

                    // Wait 30 seconds before checking status
                    sleep(50);

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

                    $curStatus = $statusBody['status']
                        ?? $statusBody['state']
                        ?? ($statusBody['messages'][0]['status'] ?? null);

                    if ($curStatus) {
                        $finalStatus = $curStatus;

                        $smsLog->update([
                            'status' => $finalStatus,
                            'raw_response' => $statusBody
                        ]);

                        $lc = strtolower((string)$curStatus);

                        if ($lc === 'delivered' || $lc === 'failed') {
                            if ($quoteId) {
                                $moduleAPIName = "twiliosmsextension0__Sent_SMS";

                                $recordData = [
                                    "Message" => $messageText .'test',
                                    "Name" => "Sinch Sms",
                                    "twiliosmsextension0__Status" => $curStatus,
                                    "twiliosmsextension0__Activity_ID" => $messageId,
                                    "Quote_CustomerName" => $quoteId
                                ];

                                $record = [
                                    "data" => [$recordData]
                                ];

                                $access_token = ZohoHelper::getAccessToken();
                                $ch = curl_init("https://www.zohoapis.eu/crm/v2/$moduleAPIName");
                                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                    "Authorization: Zoho-oauthtoken $access_token",
                                    "Content-Type: application/json"
                                ]);
                                curl_setopt($ch, CURLOPT_POST, 1);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($record));
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                                $response = curl_exec($ch);

                                Log::info('response from sinch resend api ', [
                                    'response' => $response
                                ]);
                            }
                        }
                    }
                });
                
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
