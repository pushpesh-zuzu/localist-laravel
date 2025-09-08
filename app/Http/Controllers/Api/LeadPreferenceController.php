<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserServiceLocation;
use App\Models\UserResponseTime;
use App\Models\RecommendedLead;
use App\Models\ServiceQuestion;
use App\Models\LeadPrefrence;
use App\Models\UniqueVisitor;
use App\Models\SaveForLater;
use App\Models\ActivityLog;
use App\Models\LeadRequest;
use App\Models\UserService;
use App\Models\UserDetail;
use App\Models\CreditList;
use App\Models\SellerNote;
use App\Models\Category;
use App\Models\PlanHistory;
use App\Models\User;
use App\Models\AutobidStatusLog;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator, Http
};

use Illuminate\Support\Facades\Storage;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoEmails;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoLeadBuyers;
use App\Helpers\Zoho\ZohoPurchasedLeads;
use App\Helpers\Zoho\ZohoQuestionAnswer;
use App\Helpers\Zoho\ZohoService;
use App\Helpers\Zoho\ZohoServiceLocations;
use App\Models\NotificationSetting;
use App\Models\NotificationLog;

use App\Services\LeadService;


class LeadPreferenceController extends Controller
{

    public function getLeadRequest(Request $request, LeadService $leadService)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;

        //filters
        $filters['searchName'] = $aVals['name'] ?? null;
        $filters['spotlightFilter'] = $aVals['lead_spotlights'] ?? null;
        $filters['lead_time'] = $aVals['lead_time'] ?? null;
        $filters['services'] = $aVals['service_id'] ?? null;
        $filters['creditFilter'] = $aVals['credits'] ?? null;

        $filters['unread'] = $aVals['unread'] ?? null;
        $distanceFilter = $aVals['distance_filter'] ?? null;
        $requestMiles = null;
        $requestPostcode = null;
        if ($distanceFilter && preg_match('/(\d+)\s*miles\s*from\s*(\w+)/i', $distanceFilter, $matches)) {
            $requestMiles = (int)$matches[1];
            $requestPostcode = strtoupper($matches[2]);
        }
        $baseQuery = $leadService->getSellerLeadsBaseQuery($user_id, $requestPostcode, $requestMiles, $filters);

        $allLeads = $baseQuery->orderBy('id', 'desc')->get();

        //Macting as per seller pref
        $allLeads = $leadService->leadsAccordingTOSellerPref($user_id, $allLeads);

        //add lead view count
        $allLeads = $this->addLeadViewCount($allLeads);

        return $this->sendResponse(__('Lead Request Data'), $allLeads->values());
    }

    private function addLeadViewCount($baseLeads){
        // ===== Add view_count to each lead =====
        $leadIds = $baseLeads->pluck('id')->toArray();
        $customerIds = $baseLeads->pluck('customer_id')->toArray();
        $rawViewCounts = UniqueVisitor::whereIn('buyer_id', $customerIds)
            ->whereIn('lead_id', $leadIds)
            ->select('buyer_id',
                     'lead_id',
                     DB::raw('SUM(visitors_count) as total_views'),
                    //  DB::raw('SUM(random_count) as total_randoms')
                    )
            ->groupBy('buyer_id', 'lead_id')
            ->get();

        // 2. Map them into a nested array like: [buyer_id][lead_id] => count
         $leadMetricsMap = [];
        foreach ($rawViewCounts as $row) {
            $views = $row->total_views >= 30 ? $row->total_views : rand(5, 30);
            $leadMetricsMap[$row->buyer_id][$row->lead_id] = [
                'view_count' => $views,
                // 'randoms' => $row->total_randoms,
            ];
        }


        // 3. Assign each lead its view_count from the map
        $baseLeads = $baseLeads->map(function ($lead) use ($leadMetricsMap) {
            $buyerId = $lead->customer_id;
            $leadId = $lead->id;
            $views = $leadMetricsMap[$buyerId][$leadId]['views'] ?? 0;
            $lead->view_count = $views >= 30 ? $views : rand(5, 30);

            return $lead;
        });

        return $baseLeads;
    }

    public function changePrimaryService(Request $request){
        $validator = Validator::make($request->all(), [
                'service_id' => 'required|integer|exists:categories,id',
            ], [
            'service_id.exists' => 'Provided service id does not exists.',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $user_id = $request->user_id;

        $user = User::find($user_id);
        $user->primary_category = $request->service_id;
        $user->updated_at = date('Y-m-d H:i:s');
        $user->save();

        return ZohoHelper::dispatchAfterResponse(function () use ($user_id) {
                app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($user_id);
            }, [
                'success' => true,
                'message' => 'Primary service changed successfully'
            ]);
        //return $this->sendResponse("Primary service changed successfully");
    }

    public function getservices(Request $request){
        $user_id = $request->user_id;
        $serviceId = UserService::where('user_id', $user_id)->pluck('service_id')->toArray();
        $categories = Category::whereIn('id', $serviceId)->get();
        foreach ($categories as $key => $value) {
            $value['locations'] = UserServiceLocation::whereIn('user_id',[$user_id])->whereIn('service_id', [$value->id])->count();
            $value['leadcount'] =  LeadRequest::whereIn('service_id', [$value->id])->count();

            //for getting primary category in service list
            $value['primaryService'] =  User::where('id', $user_id)->value('primary_category');
        }
        return $this->sendResponse(__('Service Data'), $categories);
    }

    public function questionAnswer(Request $request)
    {
        $service_id = $request->service_id;

        if (empty($service_id)) {
            return $this->sendResponse(__('Category Data'), []);
        }

        // Fetch all records where 'category' matches the given service_id
        $categories = ServiceQuestion::where('category', $service_id)
                                 ->where('status', 1)
                                 ->orderBy('id', 'ASC')
                                 ->get();

        return $this->sendResponse(__('Category Data'), $categories);
    }

    public function getServiceWiseLocation(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        // Get all locations for the user
        $aRows = UserServiceLocation::where('user_id', $userId)
                                    ->whereIn('service_id', [$aVals['service_id']])
                                    ->get();


        return $this->sendResponse(__('User Service Data'), $aRows);
    }

    public function getleadpreferences(Request $request)
    {
        $user_id = $request->user_id;
        $service_id = $request->service_id;
        $leadPreference = ServiceQuestion::where('category', $service_id)->get();
        // print_r($leadPreference->toArray());
        if(count($leadPreference)>0){
            $questions = [];
            foreach($leadPreference as $value){
                $value['answers'] = LeadPrefrence::where('question_id', $value->id)
                                                    ->where('user_id', $user_id)
                                                    ->pluck('answers')
                                                    ->first();
                $catArrAns = json_decode($value['answer'], true);
                $catAns = "";
                foreach($catArrAns as $a){
                    if(!empty($catAns)){
                        $catAns .= ',';
                    }
                    $catAns .= $a['option'];
                }
                $value['answer'] = $catAns;
            }
            $leadPreferences = $leadPreference;
        }else{
            $leadPreferences = ServiceQuestion::where('category', $service_id)->get();
            foreach($leadPreference as $value){
                $catArrAns = json_decode($value['answer'], true);
                $catAns = "";
                foreach($catArrAns as $a){
                    if(!empty($catAns)){
                        $catAns .= ',';
                    }
                    $catAns .= $a['option'];
                }
                $value['answer'] = $catAns;
            }

        }
        return $this->sendResponse(__('Lead Preferences Data'), $leadPreferences);
    }

    public function leadpreferences(Request $request)
    {
        $request->validate([
            'service_id'   => 'required',
            'user_id'      => 'required|integer',
            'question_id'  => 'required|array', // Expecting multiple question IDs
            'answers'      => 'required|array', // Expecting multiple answers
        ]);

        $insertedOrUpdatedData = [];

        foreach ($request->question_id as $index => $questionId) {
            $answers = $request->answers[$index] ?? '';

            // Clean and format answers (comma-separated)
            $cleanedAnswer = preg_replace('/\s*,\s*/', ',', $answers);
            $cleanedAnswer = rtrim($cleanedAnswer, ','); // Remove trailing comma

            // Check if an entry exists
            $leadPreference = LeadPrefrence::where('service_id', $request->service_id)
                ->where('user_id', $request->user_id)
                ->where('question_id', $questionId)
                ->first();

            $serviceIdZoho = $request->service_id;
            if ($leadPreference) {
                // Update existing record
                $leadPreference->update(['answers' => $cleanedAnswer]);
                $userId=$request->user_id;
                // ZohoHelper::dispatchAfterResponse(function () use ($userId, $serviceIdZoho) {
                //     app(ZohoQuestionAnswer::class)->integrateServiceQaSingle($userId, $serviceIdZoho);
                // }, [
                //     'success' => true,
                //     'message' => 'Data processed successfully'
                // ]);
                //app(ZohoQuestionAnswer::class)->integrateServiceQa($request->user_id, $questionActualId);
                //dd($x);
            } else {
                // Create a new record
                $leadPreference = LeadPrefrence::create([
                    'service_id'  => $request->service_id,
                    'question_id' => $questionId,
                    'user_id'     => $request->user_id,
                    'answers'     => $cleanedAnswer,
                ]);
            }

            $insertedOrUpdatedData[] = $leadPreference;
        }


        $userId = $request->user_id;
        $serviceIdZoho = $request->service_id;
        return ZohoHelper::dispatchAfterResponse(function () use ($userId, $serviceIdZoho) {
            app(ZohoQuestionAnswer::class)->integrateServiceQaSingle($userId, $serviceIdZoho);
        }, [
                'success' => true,
                'message' => 'Data processed successfully',
                'data' => $insertedOrUpdatedData
            ]);

        //return $this->sendResponse(__('Data processed successfully'), $insertedOrUpdatedData);
    }

    public function removeService(Request $request){
        $user_id = $request->user_id;
        $serviceid = $request->service_id;
        $user_service_id = UserService::where('user_id',$user_id)->where('service_id',$serviceid)->pluck('zoho_service_id')->first();
        $user_service_locations = UserServiceLocation::where('user_id',$user_id)->where('service_id',$serviceid)->distinct()->pluck('zoho_location_id');
        $user_lead_prefrences = LeadPrefrence::where('user_id',$user_id)->where('service_id',$serviceid)->distinct()->pluck('zoho_question_id');
        UserService::where('user_id',$user_id)->where('service_id',$serviceid)->delete();
        UserServiceLocation::where('user_id',$user_id)->where('service_id',$serviceid)->delete();
        LeadPrefrence::where('user_id',$user_id)->where('service_id',$serviceid)->delete();

        return ZohoHelper::dispatchAfterResponse(function () use ($user_id, $user_service_id, $user_service_locations, $user_lead_prefrences,$serviceid) {
            app(ZohoService::class)->deleteBuyerService($user_service_id);
            app(ZohoServiceLocations::class)->deleteBuyerServiceLocation($user_service_locations);
            app(ZohoQuestionAnswer::class)->deleteServiceQa($user_lead_prefrences,$user_id,$serviceid);
            }, [
                'success' => true,
                'message' => 'Service deleted Sucessfully'
            ]);
        // app(ZohoService::class)->deleteBuyerService($user_service_id);
        // foreach ($user_service_locations as $location_id) {
        //     app(ZohoServiceLocations::class)->deleteBuyerServiceLocation($location_id);

        // }
        // foreach ($user_lead_prefrences as $user_lead_prefrence) {
        //    app(ZohoQuestionAnswer::class)->deleteServiceQa($user_lead_prefrence);

        // }


        //return $this->sendResponse(__('Service deleted Sucessfully'));
    }



    public function sortByCreditValue(Request $request, LeadService $leadService)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;
        $creditFilter = $request->credit_filter;//High, Medium, Low
        $sortType = $request->sort_type; //newest,oldest

        $baseQuery = $leadService->getSellerLeadsBaseQuery($user_id);

        // Apply credit score filter using WHERE conditions
        if ($creditFilter) {
            $baseQuery = match ($creditFilter) {
                'High'   => $baseQuery->where('credit_score', '>=', 15),
                'Medium' => $baseQuery->whereBetween('credit_score', [10, 14]),
                'Low'    => $baseQuery->where('credit_score','<=', 9),
                default  => $baseQuery,
            };
        }

        // Sort by ID direction based on sort_type
        $orderDirection = ($sortType === 'Oldest') ? 'ASC' : 'DESC';
        // Strict matching on Questions & Answers
        $allLeads = $baseQuery->orderBy('id', $orderDirection)->get();

        //Macting as per seller pref
        $allLeads = $leadService->leadsAccordingTOSellerPref($user_id, $allLeads);

        //add lead view count
        $allLeads = $this->addLeadViewCount($allLeads);


        return $this->sendResponse(__('Lead Request Data'), $allLeads->values());
    }

    public function getPendingLeads(Request $request)
    {
        echo "<pre>";
        $aVals = $request->all();
        print_r($aVals);
        $user_id = $request->user_id;
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
            ->where('status','<>', 'hired')
            ->pluck('lead_id')
            ->toArray();
        print_r($recommendedLeadIds);
        $allLeads = LeadRequest::with(['customer', 'category'])
        ->whereIn('id',$recommendedLeadIds)
        ->whereHas('customer', function($query) {
            $query->where('form_status', 1);
        })
        ->orderBy('id', 'DESC')
        ->get();
        print_r($allLeads->toArray()); die;
        foreach ($allLeads as $key => $value) {
            $isActivity = ActivityLog::where('to_user_id',$user_id)
                                 ->where('from_user_id',$value->customer_id)
                                 ->where('lead_id',$value->id)
                                 ->latest()
                                 ->first();
            if(!empty($isActivity)){
                if($isActivity->activity_name == 'Requested a callback'){
                    $value['profile_view'] = "Requested a callback";
                    $value['profile_view_time'] = $isActivity->created_at->diffForHumans();
                }else{
                    $value['profile_view'] = $value['customer']->name." viewed your profile";
                    $value['profile_view_time'] = $isActivity->created_at->diffForHumans();
                }

            }else{
                $value['profile_view'] = "";
                $value['profile_view_time'] = "";
            }

        }
        return $this->sendResponse(__('Lead Request Data'), $allLeads);
    }

    public function getHiredLeads(Request $request)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
            ->where('status','hired')
            ->pluck('lead_id')
            ->toArray();

        $allLeads = LeadRequest::with(['customer', 'category'])
        ->whereIn('id',$recommendedLeadIds)
        ->whereHas('customer', function($query) {
            $query->where('form_status', 1);
        })->where('status','hired')
        ->orderBy('id', 'DESC')
        ->get();

        return $this->sendResponse(__('Lead Request Data'), $allLeads);
    }







    public function addHiredLeads(Request $request)
    {
        $aVals = $request->all();
        $lead = LeadRequest::where('id',$aVals['lead_id'])->first();
        $leadId = $aVals['lead_id'];
        if(empty($lead)){
            return $this->sendError('Lead not found', 404);
        }

        $sellerId = $request->user_id;
        $buyerId = $lead->customer_id;

        $recommendedId= RecommendedLead::where('lead_id', $aVals['lead_id'])
                ->where('seller_id', $sellerId)
                ->where('buyer_id', $buyerId)
                ->value('id');
        $users = User::where('id',$buyerId)->pluck('name')->first();

        $sellerName = User::where('id',$sellerId)->pluck('name')->first();
        $buyerName = User::where('id',$lead->customer_id)->pluck('name')->first();
        $leadTime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();
        $activityname = $sellerName . ' updated status to hired';
        $isActivity = self::getActivityLog($sellerId, $buyerId, $aVals['lead_id'], $activityname);

        if($lead->status != 'hired'){
            LeadRequest::where('id',$aVals['lead_id'])->update([
                'status'=>'hired',
                'hired_by' => $sellerId
            ]);
            // $leadsDetails = LeadRequest::where('id',$aVals['lead_id'])->first();
            // $zohoService = new ZohoService();
            // $zohoService->integrateUser('lead',null,$leadsDetails);
            $statusUpdate = RecommendedLead::where('lead_id', $aVals['lead_id'])
                ->where('seller_id', $sellerId)
                ->where('buyer_id', $buyerId)
                ->update([
                    'status' => 'hired'
                ]);


            $sendmessage = 'Request submited sucessfully';
            if(empty($isActivity)){
                self::addActivityLog($sellerId, $buyerId, $aVals['lead_id'], $activityname, "hired", $leadTime);
            }

            if($statusUpdate){
                return ZohoHelper::dispatchAfterResponse(function () use ($sellerId, $recommendedId,$leadId) {
                    app(ZohoPurchasedLeads::class)->integratePurchaseLeads($sellerId, $recommendedId);
                    ZohoEmails::newLeadClosedEmail($leadId,$sellerId);
                    ZohoEmails::newLeadHiredEmail($leadId,$sellerId);

                }, [
                    'success' => true,
                    'message' => 'Request Submitted Successfully'
                ]);
            }
            else{
                return $this->sendResponse($sendmessage, []);
            }


        }else{
            $sendmessage = 'This lead is already hired!';
            return $this->sendResponse($sendmessage, []);
        }

        //return $this->sendResponse($sendmessage, []);
    }

    public function submitLeads(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer|exists:lead_requests,id',
            'seller_id' => 'required',
            'final_price' => 'sometimes',
            'unit_type' => 'sometimes',
            'disclose_information' => 'sometimes|integer'
            ], [
            'lead_id.required' => 'Lead Id is required.',
            'seller_id.required' => 'Seller Id is required.',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $aVals = $request->all();
        $sellerId = $aVals['seller_id'];
        $buyerId = $request->user_id;
        $lead = LeadRequest::where('id',$aVals['lead_id'])->first();
        if(empty($lead)){
            return $this->sendError('Lead not found', 404);
        }
        $buyerName = User::where('id',$buyerId)->pluck('name')->first();
        $leadTime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();

        $activityname = $buyerName . ' updated status to hired';
        $isActivity = self::getActivityLog($buyerId, $sellerId, $aVals['lead_id'],$activityname);

        if($lead->status != 'hired'){
            LeadRequest::where('id',$aVals['lead_id'])->update([
                'status'=>'hired',
                'hired_by' => $buyerId
            ]);
            RecommendedLead::where('lead_id', $aVals['lead_id'])
                ->where('seller_id', $sellerId)
                ->where('buyer_id', $buyerId)
                ->update([
                    'final_price' => $request->final_price,
                    'unit_type' => $request->unit_type,
                    'disclose_information' => $request->disclose_information,
                    'status' => 'hired'
                ]);
            $sendmessage = 'Request submited sucessfully';
            if(empty($isActivity)){
                self::addActivityLog($buyerId, $sellerId, $aVals['lead_id'],$activityname, "hired", $leadTime);
            }
        }else{
            $sendmessage = 'This lead is already hired!';
        }

        return $this->sendResponse($sendmessage, []);
    }



    public function pendingLeads(Request $request)
    {
        $aValues = $request->all();
        $serviceIds = is_array($aValues['service_id']) ? $aValues['service_id'] : explode(',', $aValues['service_id']);
        $leadcount = LeadRequest::whereIn('service_id', $serviceIds)
                            ->get()->count();
        return $this->sendResponse('Pending Leads', $leadcount);
    }



    public function addUserService(Request $request)
    {
        $aVals = $request->all();
        $userId = $request->user_id;
        $validator = Validator::make($aVals, [
            //'service_id' => 'required|exists:services,id',
            'service_id' => [
                'required',
                'exists:categories,id',
                Rule::unique('user_services', 'service_id')->where(function ($query) use ($userId ) {
                    return $query->where('user_id', $userId );
                })
            ],
            'user_id' => 'required|exists:users,id',
          ],
          [
            'user_id.exists' => 'The selected user does not exist.',
            'service_id.exists' => 'The selected service does not exist.',
            'service_id.unique' => 'You have already added this service to your profile.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
        if ($serviceIds) {
            $locationIds = [];
            $questionIds = [];
            $serviceAllIds = [];
            foreach ($serviceIds as $serviceId) {

                $userService = UserService::createUserService($aVals['user_id'],$serviceId,0);
                $serviceAllIds[] = $userService->id;
                //get existing locations for this user
                $userLocations = UserServiceLocation::where('user_id', $userId)->get();

                foreach($userLocations as $loc){
                    $locData['user_id'] =  $userId;
                    $locData['service_id'] =  $serviceId;
                    $locData['user_service_id'] =  $userService->id;
                    $locData['miles'] =  $loc->miles;
                    $locData['nation_wide'] =  $loc->nation_wide;
                    $locData['postcode'] =  $loc->postcode;
                    $locData['city'] =  $loc->city;
                    $locData['travel_time'] =  $loc->travel_time;
                    $locData['travel_by'] =  $loc->travel_by;
                    $locData['coordinates'] =  $loc->coordinates;
                    $locData['type'] =  $loc->type;
                    $locData['status'] =  1;
                    $locData['created_at'] =  date('Y-m-d H:i:s');
                    //add previous locations to this new service
                    $locExists = UserServiceLocation::where('user_id',$userId)->where('service_id',$serviceId)
                        ->where('user_service_id', $userService->id)->where('miles',$loc->miles)->where('nation_wide',$loc->nation_wide)
                        ->where('postcode', $loc->postcode)->first();
                    if(empty($locExists)){
                        $location = UserServiceLocation::create($locData);
                        $locationIds[] = $location->id;


                    }

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
                        ->where('user_id', $userId)
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
                            'user_id' => $userId,
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

            ZohoHelper::dispatchAfterResponse(function () use ($userId, $serviceAllIds, $locationIds, $questionIds,$serviceIds) {
                app(ZohoService::class)->integrateService($userId, $serviceAllIds);
                app(ZohoServiceLocations::class)->integrateServiceLocations($userId, $locationIds);
                app(ZohoQuestionAnswer::class)->integrateServiceQa($userId,$serviceIds);
            }, [
                'success' => true,
                'message' => 'Service added to your profile successfully'
            ]);
            //return $this->sendResponse(__('Service added to your profile successfully'));
        }else{
            return $this->sendResponse(__('Select Service to proceed'));
        }
    }

    public function getUserServices(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        $aRows = UserService::where('user_id',$userId)
        ->join('categories', 'categories.id', '=', 'user_services.service_id')
        ->select('user_services.*', 'categories.name')
        ->get();
        return $this->sendResponse(__('User Service Data'),$aRows);

    }

    public function expandRadius(Request $request){
        $validator = Validator::make($request->all(), [
            'location_id' => 'required|integer|exists:user_service_locations,id'
            ], [
            'location_id.required' => 'Location Id is required.',
            'location_id.exists' => 'Provided location id does not exists.',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        //$prevMile = UserServiceLocation::where('id',$request->location_id)->value('miles');

        $location = UserServiceLocation::where('id', $request->location_id)->first();

        $userId = $location->user_id;
        $data['miles'] = $location->miles + 10;
        $data['updated_at'] = date('Y-m-d H:i:s');

        UserServiceLocation::where('id',$request->location_id)->update($data);

        $locationId = $request->location_id;
        return ZohoHelper::dispatchAfterResponse(function () use ($userId, $locationId) {
                app(ZohoServiceLocations::class)->integrateServiceSingleLocations($userId, $locationId);
            }, [
                'success' => true,
                'message' => 'Radius Expaned'
            ]);
        //return $this->sendResponse('Radius Expaned');

    }

    public function addUserLocation(Request $request)
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];
        $validator = Validator::make($aVals, [
            //'service_id' => 'required|exists:services,id',
            'service_id' => [
                'required',
                'exists:categories,id',
            ],
            'user_id' => 'required|exists:users,id',
            // 'postcode' => 'required',
          ],
          [
            'user_id.exists' => 'The selected user does not exist.',
            'service_id.exists' => 'The selected service does not exist.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $userlocations = UserServiceLocation::where('user_id',$userId)
                                            ->where('postcode',$aVals['postcode'])
                                            ->where('miles',$aVals['miles'])
                                            ->where('type',$aVals['type'])
                                            ->first();

        if(isset($userlocations) && $userlocations !=''){
            return $this->sendError('Postcode with the same user already exists');
        }

        if(!empty($aVals['travel_time'])){
            $travel_time = $aVals['travel_time'];
        }else{
            $travel_time = "";
        }
        if(!empty($aVals['travel_by'])){
            $travel_by = $aVals['travel_by'];
        }else{
            $travel_by = "";
        }
        $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
        if ($serviceIds) {
            $locationIds = [];
            foreach ($serviceIds as $serviceId) {
                 $userService = UserService::where('user_id', $userId)
                                    ->where('service_id', $serviceId)
                                    ->first();

                    if (!$userService) {
                        continue; // skip if user_service does not exist
                    }

                    $userServiceId = $userService->id;
                    $nationWide = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 1 : 0;

                $aLocation = UserServiceLocation::create(
                    ['user_id' => $aVals['user_id'],
                    'service_id' => $serviceId,
                    'user_service_id' => $userServiceId,
                    'postcode' => $aVals['postcode'],
                    'type'=>$aVals['type'],
                    'miles' => $aVals['miles'],
                    'nation_wide' => $nationWide,
                    'city'=>$aVals['city'],
                    'travel_time'=>$travel_time,
                    'travel_by'=>$travel_by,
                    'coordinates' => $aVals['coordinates']
                    ] // Fields to insert
                );
                $insertedId = $aLocation->id;
                $locationIds[] = $insertedId;
                //app(ZohoServiceLocations::class)->integrateServiceLocations($aVals['user_id'], $insertedId);

            }

            return ZohoHelper::dispatchAfterResponse(function () use ($userId, $locationIds) {
                app(ZohoServiceLocations::class)->integrateServiceLocations($userId, $locationIds);
            }, [
                'success' => true,
                'message' => 'Location updated successfully'
            ]);

            //return $this->sendResponse(__('Location updated successfully'));
        }else{
            return $this->sendResponse(__('Select Service to proceed'));
        }
    }

    public function getUserLocations(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $user_id = $aVals['user_id'];
        $aRows = UserServiceLocation::where('user_id', $user_id)
            ->orderBy('postcode')
            ->get();
            // echo "<pre>";print_r($aRows->toArray());exit;

        // Group by postcode and miles
        $grouped = $aRows->groupBy(function ($item) {
            return $item->postcode . '_' . $item->miles;
        });

        $finalRows = collect();

        foreach ($grouped as $items) {
            $first = $items->first(); // representative row

            // Clone the first row's attributes
            $value = $first->toArray();

            // Add custom fields
            $value['total_services'] = $items->pluck('service_id')->unique()->count();
            $value['leadcount'] = LeadRequest::where('postcode', $first->postcode)->count();
            $value['service_ids'] = $items->pluck('service_id')->unique()->values();

            $finalRows->push($value);
        }
        return $this->sendResponse(__('User Service Data'), $finalRows);
    }

    public function editUserLocation(Request $request)
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        if($aVals['type'] == "Nationwide"){
             $validator = Validator::make($aVals, [
                'service_id' => ['required', 'exists:categories,id'],
                'user_id' => 'required|exists:users,id',
                'miles' => 'required',
                'type' => 'required',
            ], [
                'user_id.exists' => 'The selected user does not exist.',
                'service_id.exists' => 'The selected service does not exist.',
            ]);
        }else{
            $validator = Validator::make($aVals, [
                'service_id' => ['required', 'exists:categories,id'],
                'user_id' => 'required|exists:users,id',
                'postcode' => 'required',
                'miles' => 'required',
                'type' => 'required',
            ], [
                'user_id.exists' => 'The selected user does not exist.',
                'service_id.exists' => 'The selected service does not exist.',
            ]);
        }


        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);


        $travel_time = $aVals['travel_time'] ?? '';
        $travel_by = $aVals['travel_by'] ?? '';
        $nationWide = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 1 : 0;
        // Delete old entry

        $locationIds = UserServiceLocation::where('user_id', $userId)
        ->pluck('zoho_location_id');

         UserServiceLocation::where('user_id', $userId)
         ->whereIn('postcode', [$aVals['postcode_old']])
         ->where('type', $aVals['type'])
         ->delete();

        $locationIdLists = [];

        foreach ($serviceIds as $serviceId) {

            $userService = UserService::where('user_id', $userId)
                ->where('service_id', $serviceId)
                ->first();

            if (!$userService) {
                continue;
            }

            $userServiceId = $userService->id;

            $isPostcodeChanged = ($aVals['postcode_old'] ?? '') != $aVals['postcode'];
            $isMilesChanged = ($aVals['miles_old'] ?? '') != $aVals['miles'];




            // Only check for duplicates if postcode or miles are changed
            if ($aVals['type'] !== 'Nationwide' && $aVals['type'] !== 'Draw on Map') {
                if ($isPostcodeChanged || $isMilesChanged) {
                    $duplicateExists = UserServiceLocation::where('user_id', $userId)
                        ->where('service_id', $serviceId)
                        ->where('type', $aVals['type'])
                        ->where('postcode', $aVals['postcode'])
                        ->where('miles', $aVals['miles']);

                // If this is an edit (not new insert), exclude the current location
                if (!empty($aVals['location_id'])) {
                    $duplicateExists->where('id', '!=', $aVals['location_id']);
                }

                    $duplicateExists = $duplicateExists->exists();

                    if ($duplicateExists) {
                        return $this->sendError("This postcode already exists.");
                    }
                }
            }


            // Insert updated location
            $locationInsert = UserServiceLocation::create([
                'user_id' => $userId,
                'service_id' => $serviceId,
                'user_service_id' => $userServiceId,
                'postcode' => $aVals['postcode'],
                'city' => $aVals['city'] ?? '',
                'miles' => $aVals['miles'],
                'type' => $aVals['type'],
                'nation_wide' => $nationWide,
                'travel_time' => $travel_time,
                'travel_by' => $travel_by,
                'coordinates' => $aVals['coordinates']
            ]);

            $locationIdLists[] = $locationInsert->id;

        }
        $locationIdsList = UserServiceLocation::where('user_id', $userId)
            ->pluck('id');

        return ZohoHelper::dispatchAfterResponse(function () use ($userId, $locationIds,$locationIdsList) {
            app(ZohoServiceLocations::class)->deleteBuyerServiceLocation($locationIds);
            app(ZohoServiceLocations::class)->integrateServiceLocations($userId, $locationIdsList);
        }, [
            'success' => true,
            'message' => 'Location updated successfully'
        ]);

        //return $this->sendResponse(__('Location updated successfully'));
    }




    public function removeLocation(Request $request)
    {
        $aValues = $request->all();

        if ($aValues['nation_wide'] == 1) {

            $query = UserServiceLocation::where('nation_wide', 1)->where('user_id', $aValues['user_id']);
        } else {

            $query = UserServiceLocation::where('user_id', $aValues['user_id'])
                                        ->where('miles', $aValues['miles'])
                                        ->where('postcode', $aValues['postcode']);
        }


        $user_service_locations = $query->pluck('zoho_location_id');

        app(ZohoServiceLocations::class)->deleteBuyerServiceLocation($user_service_locations);

        $query->delete();

        return $this->sendResponse('Location deleted successfully');
    }

    public function leadsByFilter(Request $request, LeadService $leadService){
        $aVals = $request->all();
        $user_id = $aVals['user_id'];

        $leadSpotlights = self::getSpotligths($user_id, $leadService);
        $leadTimeCounts = self::getLeadTimeData($user_id, $leadService);
        $services = self::getFilterservices1($user_id, $leadService);
        $location = self::getFilterLocations1($user_id, $leadService);
        $credits = self::getFilterCreditList1($user_id, $leadService);
        $unread = LeadRequest::where('customer_id', '!=', $user_id)->where('is_read',0)->count();

        return $this->sendResponse(__('Filter Data'), [
            [
                'leadSpotlights' => $leadSpotlights,
                'leadTime' => $leadTimeCounts,
                'services' => $services,
                'location' => $location,
                'credits' => $credits,
                'unread' => $unread,
            ]
        ]);
        // return $this->sendResponse(__('Filter Data'),$datas);
    }

    public function getSpotligths($user_id, $leadService)
    {
        $spotlights = [
            'All lead spotlights',
            'Urgent requests',
            'Updated requests',
            'Has additional details',
        ];
        $leadSpotlights = [];
        foreach ($spotlights as $sp) {
            $query = $leadService->getSellerLeadsBaseQuery($user_id, null, null, ['spotlightFilter' => $sp]);
            $allLeads = $query->orderBy('id', 'asc')->get();
            //Macting as per seller pref
            $allLeads = $leadService->leadsAccordingTOSellerPref($user_id, $allLeads);
            $leadSpotlights[] = [
                'spotlight' => $sp,
                'count' => count($allLeads),
            ];
        }

        return $leadSpotlights;
    }


    private function getLeadTimeData($user_id, $leadService)
    {
        $timeFilters = [
            'Today',
            'Yesterday',
            'Last 2-3 days',
            'Last 7 days',
            'Last 14+ days'
        ];

        $result = [];
        foreach ($timeFilters as $time) {
            $baseQuery = $leadService->getSellerLeadsBaseQuery($user_id, null, null, ['lead_time' => $time]);

            $allLeads = $baseQuery->orderBy('id', 'asc')->get();

            //Macting as per seller pref
            $allLeads = $leadService->leadsAccordingTOSellerPref($user_id, $allLeads);
            $result[] = [
                'time' => $time,
                'count' => count($allLeads),
            ];
        }

        return $result;
    }

    public function getFilterservices1($user_id, $leadService)
    {
        $serviceIds = UserService::where('user_id', $user_id)->pluck('service_id')->toArray();
        $categories = Category::whereIn('id', $serviceIds)->get();

        foreach ($categories as $category) {
            // Use basequery to get all lead IDs matching filters
            $leads = $leadService->getSellerLeadsBaseQuery($user_id)->where('service_id', $category->id)->get();
            $category['locations'] = UserServiceLocation::where('user_id', $user_id)->where('service_id', $category->id)->count();
            $category['leadcount'] = $leads->count();
        }

        return $categories;
    }

    public function getFilterLocations1($user_id, $leadService)
    {
        $aRows = UserServiceLocation::where('user_id', $user_id)->orderBy('postcode')->get();
        $uniqueRows = $aRows->unique('postcode')->values();

        foreach ($uniqueRows as $row) {
            // Use basequery and apply postcode match
            $leadCount = $leadService->getSellerLeadsBaseQuery($user_id)
                            ->where('postcode', $row->postcode)
                            ->count();

            $row['total_services'] = $aRows->where('postcode', $row->postcode)->count();
            $row['leadcount'] = $leadCount;
        }

        return $uniqueRows;
    }

    public function getFilterCreditList1($user_id, $leadService)
    {
        $creditList = CreditList::get();

        foreach ($creditList as $creditItem) {
            // print_r($creditItem->credits);
            // print_r("\n\n\n");
            $baseQuery = $leadService->getSellerLeadsBaseQuery($user_id, null, null, ['creditFilter' => $creditItem->credits]);
            $allLeads = $baseQuery->orderBy('id', 'asc')->get();

            //Macting as per seller pref
            $allLeads = $leadService->leadsAccordingTOSellerPref($user_id, $allLeads);
            $creditItem['leadcount'] = count($allLeads);
        }

        return $creditList;
    }


    public function getFilterservices($user_id){
        $serviceId = UserService::where('user_id', $user_id)->pluck('service_id')->toArray();
        $categories = Category::whereIn('id', $serviceId)->get();
        foreach ($categories as $key => $value) {
            $value['locations'] = UserServiceLocation::whereIn('user_id',[$user_id])->whereIn('service_id', [$value->id])->count();
            $value['leadcount'] =  LeadRequest::whereIn('service_id', [$value->id])->count();

            //for getting primary category in service list
            $value['primaryService'] =  User::where('id', $user_id)->value('primary_category');
        }
        return $categories;
    }



    public function getLeadProfile(Request $request)
    {
        $aVals = $request->all();
        $users = User::find($aVals['customer_id']);

        $myip = $request->ip();
        $visited_date = date("Y-m-d");

        // Check if the current combination already exists
        $visitor = UniqueVisitor::where('seller_id', $aVals['user_id'])
                                ->where('buyer_id', $aVals['customer_id'])
                                ->where('ip_address', $myip)
                                ->where('date', $visited_date)
                                ->first();

        // Fetch total random_count for this buyer-lead
        // $totalRandomCount = UniqueVisitor::where('buyer_id', $aVals['customer_id'])
        //                                 ->where('lead_id', $aVals['lead_id'])
        //                                 ->sum('random_count');
        // If this seller hasn't visited this lead today, add a new row
        if (empty($visitor)) {
            // If total random_count is less than 30, insert 5–30 (but not more than needed)
            // if ($totalRandomCount < 30) {
            //     $remaining = 30 - $totalRandomCount;
            //     $random_count = min(rand(5, 30), $remaining);
            // } else {
            //     // Already reached 30, insert only 1 from now on
            //     $random_count = 1;
            // }

            $visitor = new UniqueVisitor;
            $visitor->ip_address = $myip;
            $visitor->date = $visited_date;
            $visitor->seller_id = $aVals['user_id'];
            $visitor->buyer_id = $aVals['customer_id'];
            $visitor->lead_id = $aVals['lead_id'];
            $visitor->visitors_count = 1;
            // $visitor->random_count = $random_count;
            $visitor->save();
        }

        if ($users) {
            // Mark all lead requests as read
            LeadRequest::where('customer_id', $users->id)->update(['is_read' => 1]);

            // Fetch lead and related details
            $leads = LeadRequest::with(['customer', 'category'])
                                ->where('id', $aVals['lead_id'])
                                ->where('customer_id', $users->id)
                                ->first();

            $leads->purchase_type = RecommendedLead::where('lead_id', $aVals['lead_id'])
                                    ->where('buyer_id', $aVals['customer_id'])
                                    ->where('seller_id', $aVals['user_id'])
                                    ->pluck('purchase_type')
                                    ->first();

            $users->leads = $leads;
        }

        return $this->sendResponse('Profile Data', $users);
    }



    public function saveForLater(Request $request){
        $aVals = $request->all();
        $isDataExists = SaveForLater::where('seller_id',$aVals['user_id'])
                                    ->where('user_id',$aVals['buyer_id'])
                                    ->where('lead_id',$aVals['lead_id'])
                                    ->first();
        if(empty($isDataExists)){
            $bids = SaveForLater::create([
                'seller_id' => $aVals['user_id'], //loggedin user id
                'user_id' => $aVals['buyer_id'], //buyer
                'lead_id' => $aVals['lead_id']
            ]);
            return $this->sendResponse('Added to your saved leads', []);
        }
        return $this->sendError('Added to your saved leads');
    }

    public function getSaveForLaterList(Request $request)
    {
        $userId = $request->user_id; // seller_id

        // Step 1: Get all lead_ids saved by this seller
        $savedLeadIds = SaveForLater::where('seller_id', $userId)
                                    ->pluck('lead_id')
                                    ->toArray();

        // Step 2: Fetch the actual lead data from LeadRequest
        $savedLeads = LeadRequest::with(['customer', 'category'])
                                ->whereIn('id', $savedLeadIds)
                                ->orderBy('id', 'DESC')
                                ->get();
        //add lead view count
        $savedLeads = $this->addLeadViewCount($savedLeads);

        if ($savedLeads->isEmpty()) {
            return $this->sendResponse(__('Saved Leads'), [
                [
                    'savedLeads' => []
                ]
            ]);
        }else{
            return $this->sendResponse(__('Saved Leads'), [
                [
                    'savedLeads' => $savedLeads->values()
                ]
            ]);
        }

        // return $this->sendResponse(__('Saved Leads'), $savedLeads);
    }

    public function onlineRemoteSwitch(Request $request){
        $aVals = $request->all();

        $isDataExists = User::where('id',$aVals['user_id'])->first();
        if(!empty($isDataExists)){
            $bids =  $isDataExists->update(['is_online' => $aVals['is_online']]);
            $isonline  = $aVals['is_online'];
            // return $this->sendResponse('Switched update', $isonline);
            $userId = $aVals['user_id'];
            return ZohoHelper::dispatchAfterResponse(function () use ($userId) {
                app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($userId);
            }, [
                'success' => true,
                'message' => 'Switched update'
            ]);
            //return $this->sendResponse(__('Switched update'), []);
        }
        return $this->sendError('User not found');
    }

    public function getOnlineRemoteSwitch(Request $request){
        $aVals = $request->all();
        $isDataExists = User::where('id',$aVals['user_id'])->first();
        if(!empty($isDataExists)){
            return $this->sendResponse(__('Online Switch Data'), [
                'isonline' => $isDataExists->is_online
            ]);
        }
        return $this->sendError('User not found');
    }

    public function totalCredit(Request $request){
        $user_id = $request->user_id;
        $totalCredits = User::where('id',$user_id)->value('total_credit');
        $data['total_credit'] = !empty($totalCredits) ? $totalCredits : 0;
        $plan = PlanHistory::where('user_id',$user_id)->orderBy('id','desc')->first();
        $data['plan_purchased'] = !empty($plan)? 1 : 0;
        return $this->sendResponse('Total credit', $data);
    }

    public function getSellerRecommendedLeads(Request $request)
    {
        $seller_id = $request->user_id;
        $result = [];
            // Fetch all matching bids
            $bids = RecommendedLead::where('seller_id', $seller_id)
                ->orderBy('distance','ASC')
                ->get();

            // Get seller IDs and unique service IDs
            $sellerIds = $bids->pluck('buyer_id')->toArray();
            $serviceIds = $bids->pluck('service_id')->unique()->toArray();

            // Get users and categories
            $leads = LeadRequest::whereIn('customer_id', $sellerIds)
                        ->whereIn('service_id', $serviceIds)
                        ->with(['customer', 'category'])
                        ->get();
            if(!empty($leads)){
                return $this->sendResponse(__('AutoBid Data'), [
                    [
                        'leads' => $leads
                    ]
                ]);
            }else{
                return $this->sendResponse(__('AutoBid Data'), [
                    [
                        'leads' => []
                    ]
                ]);
            }

    }

    public function sevenDaysAutobidPause(Request $request){
        $aVals = $request->all();
        $userdetails = UserDetail::where('user_id',$aVals['user_id'])->first();
        if(!empty($userdetails)){
            $autobidpause = $aVals['autobid_pause'];
            $msg = $autobidpause  == 1 ? 'Autobid is inactive' : 'Autobid is active';
            if($userdetails->is_autobid == 1){
                $data['autobid_pause'] = $autobidpause;
                UserDetail::where('user_id',$aVals['user_id'])->update($data);

                $bidStatus = $autobidpause == 1 ? 'paused' : 'resumed';
                $data2['user_id'] = $aVals['user_id'];
                $data2['action'] = $bidStatus;
                AutobidStatusLog::insertGetId($data2);
                return $this->sendResponse($msg, [
                    'autobidpause' => $autobidpause
                ]);
            }
            return $this->sendError('Autobid is already off');
        }
        return $this->sendError('User not found!');
    }

    public function getSevenDaysAutobidPause(Request $request){
        $aVals = $request->all();
        $userdetails = UserDetail::where('user_id',$aVals['user_id'])->first();
        if(isset($userdetails) && $userdetails != ''){
            return $this->sendResponse('Seven Days autobid pause data', [
                'autobidpause' => $userdetails->autobid_pause
            ]);
        }
            return $this->sendResponse('Data not found', []);
    }

    public function responseStatus(Request $request)
    {
        $aVals = $request->all();
        $type = $aVals['type'];
        // $sellers = User::where('id',$aVals['user_id'])->pluck('name')->first();
        // $buyer = User::where('id',$aVals['seller_id'])->pluck('name')->first();
        $sellerId = $aVals['seller_id'];
        $buyerId = $aVals['buyer_id'];
        $sellerName = User::where('id', $sellerId)->pluck('name')->first();
        $buyerName = User::where('id', $buyerId)->pluck('name')->first();
        $activityname = "";

        $leadtime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();

        if($aVals['response_type'] == 'seller'){


            if($type == 'Whatsapp'){
                $activityname = $sellerName .' contacted '. $buyerName .' through Whatsapp';
            }
            if($type == 'email'){
            $activityname = $sellerName.' contacted '. $buyerName .' through email';
            }
            if($type == 'mobile'){
                $activityname = $sellerName .' contacted '. $buyerName .' through mobile';
            }
            if($type == 'sms'){
                $activityname = $sellerName .' contacted '. $buyerName .' through SMS';
            }
            $isActivity = self::getActivityLog($sellerId, $buyerId,$aVals['lead_id'],$activityname);
            self::addActivityLog($sellerId, $buyerId,$aVals['lead_id'],$activityname, $type, $leadtime);

        }else{
            if($type == 'Whatsapp'){
                $activityname = $buyerName .' contacted '. $sellerName .' through Whatsapp';
            }
            if($type == 'email'){
            $activityname = $buyerName .' contacted '. $sellerName .' through email';
            }
            if($type == 'mobile'){
                $activityname = $buyerName .' contacted '. $sellerName .' through mobile';
            }
            if($type == 'sms'){
                $activityname = $buyerName .' contacted '. $sellerName .' through SMS';
            }
            $isActivity = self::getActivityLog($buyerId, $sellerId,$aVals['lead_id'],$activityname);
            if(empty($isActivity)){
                self::addActivityLog($buyerId, $sellerId, $aVals['lead_id'],$activityname, $type, $leadtime);
            }

            //Add Notification Log for new contact
            CustomHelper::logNotifications($sellerId, $aVals['lead_id'], 'buyer_browser_customer_sending_message',
                'New Message', 'You Received a New Message');
        }

        return $this->sendResponse(__('Status Updated'), []);
    }

    public function addActivityLog($from_user_id, $to_user_id, $lead_id, $activity_name, $contact_type, $leadtime)
    {
        // Step 1: Log the activity
        $activity = ActivityLog::create([
            'lead_id' => $lead_id,
            'from_user_id' => $from_user_id, // seller
            'to_user_id' => $to_user_id,     // buyer
            'activity_name' => $activity_name,
            'contact_type' => $contact_type,
        ]);

        // Step 2: Calculate the time difference
        // $leadtime = Carbon::parse($leadtime);
        // $createdAt = $activity->created_at;

        $leadtime = Carbon::parse($leadtime)->setTimezone('Asia/Kolkata');
        $createdAt = $activity->created_at->copy()->setTimezone('Asia/Kolkata');

        $diffInMinutes = round(abs($leadtime->diffInMinutes($createdAt)));
        if ($diffInMinutes < 60) {
            $duration = $diffInMinutes;
        } else {
            $hours = round($diffInMinutes / 60);
            $duration = $hours;
        }

        // Step 3: Save duration and raw minutes
        $activity->duration = $duration;
        $activity->duration_minutes = $diffInMinutes; // You must add this column if not present
        $activity->save();

        // Step 4: Fetch all activity logs for the same seller (from_user_id), contact_type, across different lead_ids
        $contactTypes = ['Whatsapp', 'email', 'mobile', 'sms'];
        $entries = ActivityLog::where('from_user_id', $from_user_id)
            ->whereIn('contact_type', $contactTypes)
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('lead_id')
            ->map(function ($logs) {
                return $logs->first(); // Get the earliest log per lead
            });

        $totalMinutes = $entries->sum('duration_minutes');
        $entryCount = $entries->count();

        if ($entryCount > 0) {
            $averageMinutes = round($totalMinutes / $entryCount); // rounded to nearest minute

            UserResponseTime::updateOrCreate(
                [
                    'seller_id' => $from_user_id,
                ],
                [
                    'average' => $averageMinutes
                ]
            );
        }
        return $activity;
    }

    public function getActivityLog($from_user_id, $to_user_id, $lead_id, $activity_name)
    {
        $activities = ActivityLog::where('lead_id',$lead_id)
                                          ->where('from_user_id',$from_user_id)
                                          ->where('to_user_id',$to_user_id)
                                          ->where('lead_id',$lead_id)
                                          ->where('activity_name',$activity_name)
                                          ->first();
         return $activities;
    }

    public function sellerNotes(Request $request)
    {
        $aVals = $request->all();

        if(!empty($aVals['delete_note_id'])){
            SellerNote::where('id', $aVals['delete_note_id'])->delete();
        }else if(!empty($aVals['note_id'])){
            SellerNote::where('id', $aVals['note_id'])->update([
                'notes' => $aVals['notes'],
                'updated_at' => date('Y-m-d H:i:d')
            ]);
        }else{
            $now = now();
            SellerNote::create([
                'seller_id'  => $aVals['user_id'],
                'buyer_id'  => $aVals['buyer_id'],
                'lead_id'  => $aVals['lead_id'],
                'notes' => $aVals['notes'],
                'created_at' => $now
            ]);
        }

        return $this->sendResponse(__('Notes Updated Sucessfully'), []);
    }

    public function getSellerNotes(Request $request)
    {
        $aVals = $request->all();
        $isNotes = SellerNote::where('seller_id',$aVals['user_id'])
                             ->where('buyer_id',$aVals['buyer_id'])
                             ->where('lead_id',$aVals['lead_id'])
                             ->orderBy('updated_at', 'DESC')
                             ->get();
        if(!empty($isNotes)){
            $isNotes = $isNotes;
        }else{
            $isNotes = "";
        }

        return $this->sendResponse(__('Notes'), [
                'notes' => $isNotes
            ]);
    }

    public function pendingPurchaseTypeFilter(Request $request){
        $aVals = $request->all();
        $user_id = $request->user_id;
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id);
        if($aVals['purchase_type'] !== 'All'){
            $recommendedLeadIds = $recommendedLeadIds->where('purchase_type',$aVals['purchase_type']);
        }
        $recommendedLeadIds = $recommendedLeadIds->pluck('lead_id')->toArray();

        $allLeads = LeadRequest::with(['customer', 'category'])
        ->whereIn('id',$recommendedLeadIds)
        ->whereHas('customer', function($query) {
            $query->where('form_status', 1);
        })->where('status','pending')
        ->orderBy('id', 'DESC')
        ->get();

        foreach ($allLeads as $key => $value) {
            $isActivity = ActivityLog::where('to_user_id',$user_id)
                                 ->where('from_user_id',$value->customer_id)
                                 ->where('lead_id',$value->id)
                                 ->latest()
                                 ->first();
            if(!empty($isActivity)){
                if($isActivity->activity_name == 'Requested a callback'){
                    $value['profile_view'] = "Requested a callback";
                    $value['profile_view_time'] = $isActivity->created_at->diffForHumans();
                }else{
                    $value['profile_view'] = $value['customer']->name." viewed your profile";
                    $value['profile_view_time'] = $isActivity->created_at->diffForHumans();
                }

            }else{
                $value['profile_view'] = "";
                $value['profile_view_time'] = "";
            }
        }
        return $this->sendResponse(__('Pending Lead'), $allLeads);
    }

    public function hiredPurchaseTypeFilter(Request $request)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id);
        if($aVals['purchase_type'] !== 'All'){
            $recommendedLeadIds = $recommendedLeadIds->where('purchase_type',$aVals['purchase_type']);
        }
        $recommendedLeadIds = $recommendedLeadIds->pluck('lead_id')->toArray();

        $allLeads = LeadRequest::with(['customer', 'category'])
        ->whereIn('id',$recommendedLeadIds)
        ->whereHas('customer', function($query) {
            $query->where('form_status', 1);
        })->where('status','hired')
        ->orderBy('id', 'DESC')
        ->get();

        return $this->sendResponse(__('Hired Lead'), $allLeads);
    }

    public function leadsEnquiry(Request $request)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;
        $distanceFilter = $aVals['distance_filter'] ?? null;

        $requestMiles = null;
        $requestPostcode = null;
        if ($distanceFilter && preg_match('/(\d+)\s*miles\s*from\s*(\w+)/i', $distanceFilter, $matches)) {
            $requestMiles = (int)$matches[1];
            $requestPostcode = strtoupper($matches[2]);
        }

        $creditRanges = [];
        if (!empty($creditFilter)) {
            $creditParts = array_map('trim', explode(',', $creditFilter));
            foreach ($creditParts as $part) {
                if (preg_match('/(\d+)\s*-\s*(\d+)\s*Credits/', $part, $matches)) {
                    $min = (int) $matches[1];
                    $max = (int) $matches[2];
                    $creditRanges[] = [$min, $max];
                }
            }
        }

        $spotlightConditions = [];
        if (!empty($spotlightFilter)) {
            $spotlightConditions = array_map('trim', explode(',', $spotlightFilter));
        }

        $baseQuery = $this->basequery($user_id, $requestPostcode, $requestMiles);

        // Exclude saved leads
        $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();
        // $baseQuery = $baseQuery->whereNotIn('id', $savedLeadIds);

        // Exclude leads from recommended table starts
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
        ->pluck('lead_id')
        ->toArray();

        // Merge both exclusion arrays
        $excludedLeadIds = array_merge($savedLeadIds, $recommendedLeadIds);

        if (!empty($excludedLeadIds)) {
        $baseQuery = $baseQuery->whereNotIn('id', $excludedLeadIds);
        }

        // Exclude leads from recommended table ends


        if (!empty($aVals['service_id'])) {
            $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
            $baseQuery = $baseQuery->whereIn('service_id', $serviceIds);
        }


        // Strict matching on Questions & Answers
        $allLeads = $baseQuery->orderBy('id', 'DESC')->get();

        $preferenceMap = $this->getUserPreferenceMap($user_id);

        $filteredLeads = $allLeads->filter(function ($lead) use ($preferenceMap) {
            $leadQuestions = json_decode($lead->questions, true);
            if (!is_array($leadQuestions)) return false;

            foreach ($leadQuestions as $q) {
                $buyerAnswers = (array) $q['ans'];

                foreach ($buyerAnswers as $buyerAnswer) {
                    $buyerAnswer = trim($buyerAnswer);

                    // If buyer selected something that seller has NOT selected, reject
                    if (!isset($preferenceMap[$buyerAnswer])) {
                        return false;
                    }
                }
            }

            return true;
        });
         // ===== Add view_count to each lead =====
        $leadIds = $filteredLeads->pluck('id')->toArray();
        $customerIds = $filteredLeads->pluck('customer_id')->toArray();
        $rawViewCounts = UniqueVisitor::whereIn('buyer_id', $customerIds)
            ->whereIn('lead_id', $leadIds)
            ->select('buyer_id',
                     'lead_id',
                     DB::raw('SUM(visitors_count) as total_views'),
                    //  DB::raw('SUM(random_count) as total_randoms')
                    )
            ->groupBy('buyer_id', 'lead_id')
            ->get();

        // 2. Map them into a nested array like: [buyer_id][lead_id] => count
         $leadMetricsMap = [];
        foreach ($rawViewCounts as $row) {
            $views = $row->total_views >= 30 ? $row->total_views : rand(5, 30);
            $leadMetricsMap[$row->buyer_id][$row->lead_id] = [
                'view_count' => $views,
                // 'randoms' => $row->total_randoms,
            ];
        }

        // 3. Assign each lead its view_count from the map
        $filteredLeads = $filteredLeads->map(function ($lead) use ($leadMetricsMap) {
            $buyerId = $lead->customer_id;
            $leadId = $lead->id;
            $views = $leadMetricsMap[$buyerId][$leadId]['views'] ?? 0;
            $lead->view_count = $views >= 30 ? $views : rand(5, 30);
            return $lead;
        });
        return [
                    'response' => [
                        'total_leads' => $filteredLeads->count(),
                        'unread' => $filteredLeads->where('is_read', 0)->count()
                    ]
                ];
        // return $this->sendResponse(__('Lead Request Data'), $filteredLeads->count());
    }

}
