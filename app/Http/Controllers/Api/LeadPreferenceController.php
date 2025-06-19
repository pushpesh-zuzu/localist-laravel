<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserServiceLocation;
use App\Models\UserHiringHistory;
use App\Models\UserResponseTime;
use App\Models\RecommendedLead;
use App\Models\ServiceQuestion;
use App\Models\LeadPrefrence;
use App\Models\UniqueVisitor;
use App\Models\SaveForLater;
use App\Models\ActivityLog;
use App\Models\LeadRequest;
use App\Models\UserService;
use App\Models\LeadStatus;
use App\Models\UserDetail;
use App\Models\CreditList;
use App\Models\SellerNote;
use App\Models\Category;
use App\Models\PlanHistory;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator, Http
};

use Illuminate\Support\Facades\Storage;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helpers\CustomHelper;

class LeadPreferenceController extends Controller
{
    
    
    public function getBaseQuery($user_id, $requestPostcode = null, $requestMiles = null, $filters = []){
        $userServices = UserService::where('user_id',$user_id)->select('service_id')->get();
        //get all types of locations
        $ulNationWide = UserServiceLocation::where('user_id', $user_id)->where('nation_wide','1')->get();
        $ulDistance = UserServiceLocation::where('user_id', $user_id)->where('type','Distance')->get()->toArray();
        $ulTravel = UserServiceLocation::where('user_id', $user_id)->where('type','Travel Time')->get()->toArray();
        $ulMap = UserServiceLocation::where('user_id', $user_id)->where('type','Draw on Map')->get()->toArray();

        //get Nation Wide services
        $nwServices = [];
        foreach($ulNationWide as $ul){
            array_push($nwServices, $ul->service_id);
        }

        //remove duplicate services from array
        $nwServices = array_unique($nwServices);

        //remove location if it is nation wide
        $ulDistance = array_filter($ulDistance, function($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });
        $ulTravel = array_filter($ulTravel, function($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });
        $ulMap = array_filter($ulMap, function($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });


        //add other services
        $otherServices = [];
        foreach($ulDistance as $d){
            array_push($otherServices, $d['service_id']);
        }
        foreach($ulTravel as $t){
            array_push($otherServices, $t['service_id']);
        }
        foreach($ulMap as $m){
            array_push($otherServices, $m['service_id']);
        }
        
        //remove duplicate services from array
        $otherServices = array_unique($otherServices);

        //merge both arrays into one array
        $allServices = array_merge($nwServices,$otherServices);       
        
        $baseQuery = LeadRequest::with(['customer', 'category'])
            ->whereHas('customer', function ($query) {
                $query->where('form_status', 1);
            })
            ->where('user_id', '<>', $user_id) //do not include self request leads
            //closure condition
            ->where('status','!=','hired')
            ->where('created_at', '>', Carbon::now()->subDays(14)->toDateString());
        $leadIdsWithFiveBids = DB::table('recommended_leads')
            ->select('lead_id')
            ->groupBy('lead_id')
            ->havingRaw('COUNT(*) >= 5')
            ->pluck('lead_id')
            ->toArray();

        $baseQuery = $baseQuery->whereNotIn('id', $leadIdsWithFiveBids);
                
        if($requestPostcode === null){ //select default condition for location
            //include locations
            $baseQuery = $baseQuery->where(function ($query) use ($user_id, $ulDistance, $ulTravel, $ulMap, $nwServices) {
                //for distance type

                
                foreach ($ulDistance as $item) {
                    $radiusPostcode = CustomHelper::getPostcodesWithinRadius($item['postcode'], $item['miles']);
                    
                    
                    $query->orWhere(function ($q) use ($item, $radiusPostcode) {
                        $q->where('service_id', $item['service_id'])
                            ->whereIn('postcode', array_column($radiusPostcode, 'postcode'));
                    });
                }

                //include nation wide services
                if (!empty($nwServices)) {
                    $query->orWhereIn('service_id', $nwServices);
                }

            });
        }else{
            
            $baseQuery = $baseQuery->where(function ($query) use ($nwServices, $allServices, $requestPostcode, $requestMiles, $user_id) {
                //for distance type
                $radiusPostcode = CustomHelper::getPostcodesWithinRadius($requestPostcode, $requestMiles);                
                foreach($allServices as $item){

                    $quesPref = $this->getUserPreferenceMap($user_id, $item);
                    print_r($quesPref);

                    $query->orWhere(function ($q) use ($item, $radiusPostcode, $user_id) {
                        $q->where('service_id', $item)
                            ->whereIn('postcode', array_column($radiusPostcode, 'postcode'));
                    });
                }
            });
        }


        // Exclude saved leads
        $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();
        
        // Exclude leads from recommended table starts as a bid has been placed
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
            ->pluck('lead_id')
            ->toArray();

        // Merge both exclusion arrays
        $excludedLeadIds = array_merge($savedLeadIds, $recommendedLeadIds);
        if (!empty($excludedLeadIds)) {
            $baseQuery = $baseQuery->whereNotIn('id', $excludedLeadIds);
        }


        //apply filters
        if(!empty($filters['searchName'])){
            $baseQuery = $baseQuery->where(function ($query) use ($filters) {
                $query->whereHas('customer', function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['searchName'] . '%');
                    // ->orWhere('city', 'like', '%' . $searchTerm . '%');
                })
                ->orWhereHas('category', function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['searchName'] . '%');
                })
                ->orWhere('city', 'like', '%' .  $filters['searchName'] . '%')
                ->orWhere('postcode', 'like', '%' .  $filters['searchName'] . '%')
                ->orWhere('phone', 'like', '%' .  $filters['searchName'] . '%');
            });
        }

        if(!empty($filters['spotlightFilter'])){
            $splghts = explode(',', $filters['spotlightFilter']);
            foreach($splghts as $sl){
                if(strtolower(trim($sl)) === 'all lead spotlights'){
                        $baseQuery = $baseQuery->where(function ($query){
                            $query->where('is_urgent', '=', '1')
                                ->where('is_updated', '=', '1')
                                ->where('has_additional_details', '=', '1');
                        });
                }else{
                    if(strtolower(trim($sl)) === 'urgent requests'){
                        $baseQuery = $baseQuery->where(function ($query){
                            $query->where('is_urgent', '=', '1');
                        });
                    }
                    if(strtolower(trim($sl)) === 'updated requests'){
                        $baseQuery = $baseQuery->where(function ($query){
                            $query->where('is_updated', '=', '1');
                        });
                    }
                    if(strtolower(trim($sl)) === 'has additional details'){
                        $baseQuery = $baseQuery->where(function ($query){
                            $query->where('has_additional_details', '=', '1');
                        });
                    }
                }
                
            }
        }
        
        if(!empty($filters['lead_time'])){
            if(strtolower(trim($filters['lead_time'])) === 'today'){
                $baseQuery = $baseQuery->where(function ($query){
                    $query->whereDate('created_at', Carbon::now()->toDateString());
                });
            }
            if(strtolower(trim($filters['lead_time'])) === 'yesterday'){
                $baseQuery = $baseQuery->where(function ($query){
                    $query->whereDate('created_at', Carbon::now()->subDay()->toDateString());
                });
            }
            if(strtolower(trim($filters['lead_time'])) === 'last 2-3 days'){
                $baseQuery = $baseQuery->where(function ($query){
                    $query->whereDate('created_at', '>' , Carbon::now()->subDay(3)->toDateString());
                });
            }
            if(strtolower(trim($filters['lead_time'])) === 'last 7 days'){
                $baseQuery = $baseQuery->where(function ($query){
                    $query->whereDate('created_at', '>', Carbon::now()->subDay(7)->toDateString());
                });
            }
            if(strtolower(trim($filters['lead_time'])) === 'last 14+ days'){
                $baseQuery = $baseQuery->where(function ($query){
                    $query->whereDate('created_at', '<' ,Carbon::now()->subDay()->toDateString());
                });
            }
        }
        if(!empty($filters['services'])){
            $sIds = explode(',', $filters['services']);
            $baseQuery = $baseQuery->where(function ($query) use ($sIds){
                $query->whereIn('service_id', $sIds);
            });
        }

        if(!empty($filters['creditFilter'])){
            $crFs = explode(',', str_replace('Credits','',$filters['creditFilter']));
            $creditRanges = [];
            foreach($crFs as $crf){
                $cc1 = explode('-',str_replace(' ','',$crf));
                $creditRanges[] = [ min($cc1),  max($cc1)];
            }
            $baseQuery = $baseQuery->where(function ($query) use ($creditRanges) {
                foreach ($creditRanges as $range) {
                    $query->orWhereRaw('CAST(credit_score AS UNSIGNED) BETWEEN ? AND ?', [$range[0], $range[1]]);
                }
            });
        }
        
        return $baseQuery;
        
    }

    private function getUserPreferenceMap($user_id){
        $rawAnswers = LeadPrefrence::with(['question'])
            ->where('user_id', $user_id)            
            ->get();
        $prefs = [];
        foreach ($rawAnswers as $ra) {
            $temp['service_id'] = $ra->service_id;
            $temp['question'] = $ra->question->questions;
            $temp['answers'] = array_map('trim', explode(',', $ra->answers));
            $prefs[] = $temp;
        }
        return $prefs;

    }

    function normalizeQuestion(string $question): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9 ]/', '', $question)));
    }
    private function filterLeadsByGroupedPreferences(\Illuminate\Support\Collection $leads, array $groupedPrefs)
    {
        return $leads->filter(function ($lead) use ($groupedPrefs) {
            $serviceId = $lead->service_id;

            if (!isset($groupedPrefs[$serviceId])) {
                // logger("No preferences for service_id: $serviceId");
                return false;
            }

            $prefs = $groupedPrefs[$serviceId];
            $leadQuestions = json_decode($lead->arrayed_questions, true);

            if (!is_array($leadQuestions)) {
                // logger("Invalid questions JSON for lead ID: {$lead->id}");
                return false;
            }

            $leadMap = [];
            foreach ($leadQuestions as $q) {
                $normalized = $this->normalizeQuestion($q['ques']);
                $leadMap[$normalized] = $q['ans'];
            }

            foreach ($prefs as $pref) {
                $question = $this->normalizeQuestion($pref['question']);
                $expectedAnswers = $pref['answers'];

                if (!isset($leadMap[$question])) {
                    // logger("Lead ID {$lead->id} missing question: $question");
                    return false;
                }

                $leadAnswers = $leadMap[$question];

                $intersect = array_intersect($expectedAnswers, $leadAnswers);

                if (empty($intersect) && !in_array('Other', $expectedAnswers)) {
                    // logger("Lead ID {$lead->id} failed on question: $question");
                    // logger("Lead answers: " . json_encode($leadAnswers));
                    // logger("Expected answers: " . json_encode($expectedAnswers));
                    return false;
                }
            }

            // logger("Matched Lead ID: {$lead->id}, Service ID: $serviceId");
            return true;
        });
    }



    private function leadsAccordingTOSellerPref($user_id, $leads){
        $pref = $this->getUserPreferenceMap($user_id);
        $leads  = collect($leads);
        $groupedPrefs = collect($pref)->groupBy('service_id')->toArray();
        $filteredLeads = $this->filterLeadsByGroupedPreferences($leads, $groupedPrefs);
        return $filteredLeads;
    }

    public function getLeadRequest(Request $request)
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
        $baseQuery = $this->getBaseQuery($user_id, $requestPostcode, $requestMiles, $filters);
        
        $allLeads = $baseQuery->orderBy('id', 'desc')->get();

        //Macting as per seller pref
        $allLeads = $this->leadsAccordingTOSellerPref($user_id, $allLeads);
        
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
        $data['primary_category'] = $request->service_id;
        $data['updated_at'] = date('Y-m-d H:i:s');
        User::where('id',$user_id)->update($data);
        return $this->sendResponse("Primary service changed successfully");
    }

    public function getservices(Request $request){
        $user_id = $request->user_id; 
        $categories = self::getFilterservices($user_id);
        // $serviceId = UserService::where('user_id', $user_id)->pluck('service_id')->toArray();
        // $categories = Category::whereIn('id', $serviceId)->get();
        // foreach ($categories as $key => $value) {
        //     $value['locations'] = UserServiceLocation::whereIn('user_id',[$user_id])->whereIn('service_id', [$value->id])->count();
        //     $value['leadcount'] =  LeadRequest::whereIn('service_id', [$value->id])->count();
        // }
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
                                 ->orderBy('id', 'DESC')
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

    public function getleadpreferences(Request $request): JsonResponse
    {
        $user_id = $request->user_id; 
        $service_id = $request->service_id; 
        $leadPreference = ServiceQuestion::where('category', $service_id)->get();
        if(count($leadPreference)>0){
            $questions = [];
            foreach($leadPreference as $value){
                $value['answers'] = LeadPrefrence::where('question_id', $value->id)
                                                    ->where('user_id', $user_id)
                                                    ->pluck('answers')
                                                    ->first();
            }
            $leadPreferences = $leadPreference;
        }else{
            $leadPreferences = ServiceQuestion::where('category', $service_id)->get();
            
        }                          
        return $this->sendResponse(__('Lead Preferences Data'), $leadPreferences);                              
    }

    public function leadpreferences(Request $request): JsonResponse
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

            if ($leadPreference) {
                // Update existing record
                $leadPreference->update(['answers' => $cleanedAnswer]);
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

     
        return $this->sendResponse(__('Data processed successfully'), $insertedOrUpdatedData);   
    }

    public function removeService(Request $request){
        $user_id = $request->user_id; 
        $serviceid = $request->service_id; 
        UserService::where('user_id',$user_id)->where('service_id',$serviceid)->delete();
        return $this->sendResponse(__('Service deleted Sucessfully')); 
    }

    
    
    public function sortByCreditValue(Request $request)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;
        $creditFilter = $request->credit_filter;//High, Medium, Low
        $sortType = $request->sort_type; //newest,oldest

        $baseQuery = $this->getBaseQuery($user_id);

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
        $allLeads = $this->leadsAccordingTOSellerPref($user_id, $allLeads);
        
        //add lead view count
        $allLeads = $this->addLeadViewCount($allLeads);


        return $this->sendResponse(__('Lead Request Data'), $allLeads->values());
    }

    public function getPendingLeads(Request $request)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
        ->pluck('lead_id')
        ->toArray();

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
        return $this->sendResponse(__('Lead Request Data'), $allLeads);
    }

    public function getHiredLeads(Request $request)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
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
        $leads = LeadRequest::where('id',$aVals['lead_id'])->first();
        $users = User::where('id',$leads->customer_id)->pluck('name')->first();
        $isDataExists = LeadStatus::where('lead_id',$aVals['lead_id'])->where('status',$aVals['status_type'])->first();
        $statustype = $aVals['status_type'];
        $sellers = User::where('id',$aVals['user_id'])->pluck('name')->first();
        $buyer = User::where('id',$leads->customer_id)->pluck('name')->first(); 
        $leadtime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();            
        $activityname = 'You updated ' .$buyer. ' status to hired';
        $isActivity = self::getActivityLog($aVals['user_id'],$leads->customer_id,$aVals['lead_id'],$activityname);

        if (!empty($leads)) {
            if ($leads->status == "hired" && $aVals['status_type'] == "hired") {
                return $this->sendError(__("You already hired this buyer, now you can't change this status"), 404);
            }
        
            // Allow updating to 'hired' or re-assigning the same status
            LeadRequest::where('id',$aVals['lead_id'])->update(['status' => $statustype]);
        
            $sendmessage = 'You hired ' . $users;
        
            if (empty($isDataExists)) {
                LeadStatus::create([
                    'lead_id' => $aVals['lead_id'],
                    'user_id' => $aVals['user_id'],
                    'status' => $statustype,
                    'clicked_from' => 1,
                ]);
            }

            
        } else {
            $sendmessage = 'No Leads found';
        }
        if(empty($isActivity)){
                self::addActivityLog($aVals['user_id'],$leads->customer_id,$aVals['lead_id'],$activityname, "hired", $leadtime);
            }
        
        return $this->sendResponse($sendmessage, []);
    }

    public function submitLeads(Request $request)
    {
        $aVals = $request->all();
        $leadsreq = LeadRequest::where('id',$aVals['lead_id'])->first();
        $leads = UserHiringHistory::where('lead_id',$aVals['lead_id'])
                                  ->where('user_id',$aVals['seller_id'])
                                  ->where('name',$aVals['name'])
                                  ->first();
        $sellers = User::where('id',$aVals['seller_id'])->pluck('name')->first();
        $buyer = User::where('id',$leadsreq->customer_id)->pluck('name')->first(); 
        $leadtime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();            
        $activityname = $buyer . ' updated your status to hired';
        $isActivity = self::getActivityLog($leadsreq->customer_id,$aVals['seller_id'],$aVals['lead_id'],$activityname);

        if (empty($leads)) {
            UserHiringHistory::create([
                'lead_id' => $aVals['lead_id'],
                'user_id' => $aVals['seller_id'],
                'name' => $aVals['name']
            ]);
            LeadRequest::where('id',$aVals['lead_id'])->update(['status'=>'hired']);
            
            if(empty($isActivity)){
                self::addActivityLog($leadsreq->customer_id,$aVals['seller_id'],$aVals['lead_id'],$activityname, "hired", $leadtime);
            }
            
            // $sendmessage = 'You hired this job';
            $sendmessage = 'Request submited sucessfully';
        } else {
            $sendmessage = 'Already you hired this user';
        }
        return $this->sendResponse($sendmessage, []);
    }
    // ------------------------

    

    // ------------------------

    

    public function getDistance($postcode1, $postcode2)
    {
        $encodedPostcode1 = urlencode($postcode1);
        $encodedPostcode2 = urlencode($postcode2);
        //$apiKey = "AIzaSyDwAeV7juA_VpzLHqmKXACBtcZxR52TwoE"; //"AIzaSyB29PyyFmCsm_nw8ELavLskRzMPd3XEIac"; // Replace with your API key
        $apiKey = CustomHelper::setting_value('google_maps_api');

        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$encodedPostcode1}&destinations={$encodedPostcode2}&key={$apiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] == 'OK' && isset($data['rows'][0]['elements'][0]['distance'])) {
            $distanceText = $data['rows'][0]['elements'][0]['distance']['text']; // e.g., "12.5 km"
            return floatval(str_replace(['km', ','], '', $distanceText)); // return distance as float (km)
        } else {
            return null;
        }
    }

    

    public function pendingLeads(Request $request)
    {
        $aValues = $request->all();
        $serviceIds = is_array($aValues['service_id']) ? $aValues['service_id'] : explode(',', $aValues['service_id']);
        $leadcount = LeadRequest::whereIn('service_id', $serviceIds)
                            ->get()->count();
        return $this->sendResponse('Pending Leads', $leadcount);
    }

    

    public function addUserService(Request $request): JsonResponse
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
            foreach ($serviceIds as $serviceId) {
                //  $userService = UserService::where('user_id', $userId)
                //                     ->where('service_id', $serviceId)
                //                     ->first();

                UserService::createUserService($aVals['user_id'],$serviceId,0);
                //save answer to preferences
                $leadPreferences = ServiceQuestion::where('category', $serviceId)->get();

                foreach ($leadPreferences as $question) {
                    // Get default options from 'answer' column of ServiceQuestion table
                    $defaultOptions = $question->answer ?? '';
                
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
                    LeadPrefrence::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'service_id' => $serviceId,
                            'question_id' => $question->id,
                        ],
                        [
                            'answers' => $cleanedAnswer,
                        ]
                    );
                }
            }
        // $service = UserService::createUserService($aVals['user_id'],$aVals['service_id'],0);
            return $this->sendResponse(__('Service added to your profile successfully'));
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

        $prevMile = UserServiceLocation::where('id',$request->location_id)->value('miles');
        $data['miles'] = $prevMile + 10;
        $data['updated_at'] = date('Y-m-d H:i:s');
        UserServiceLocation::where('id',$request->location_id)->update($data);
        return $this->sendResponse('Radius Expaned');

    }

    public function addUserLocation(Request $request): JsonResponse
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
            }
            return $this->sendResponse(__('Location updated successfully'));
        }else{
            return $this->sendResponse(__('Select Service to proceed'));
        }
    }

    public function getUserLocations(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];
        $uniqueRows = self::getFilterLocations($userId);
        return $this->sendResponse(__('User Service Data'), $uniqueRows);
    }

    public function editUserLocation(Request $request): JsonResponse
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
        UserServiceLocation::where('user_id', $userId)
        ->whereIn('postcode', [$aVals['postcode_old']])
        ->where('type', $aVals['type'])
        ->delete();
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
            UserServiceLocation::create([
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
        }
    
        return $this->sendResponse(__('Location updated successfully'));
    }
    
    
    public function editUserLocation_08_05(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        $validator = Validator::make($aVals, [
            'service_id' => [
                'required',
                'exists:categories,id',
            ],
            'user_id' => 'required|exists:users,id',
        ],
        [
            'user_id.exists' => 'The selected user does not exist.',
            'service_id.exists' => 'The selected service does not exist.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
        $travel_time = !empty($aVals['travel_time']) ? $aVals['travel_time'] : "";
        $travel_by = !empty($aVals['travel_by']) ? $aVals['travel_by'] : "";
        $nationWide = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 1 : 0;

        foreach ($serviceIds as $serviceId) {
            $userService = UserService::where('user_id', $userId)
                                    ->where('service_id', $serviceId)
                                    ->first();

            if (!$userService) {
                continue; // Skip if user_service does not exist
            }

            // Check for duplicate postcode + miles for the same user/service/type
            $duplicate = UserServiceLocation::where('user_id', $userId)
                ->where('service_id', $serviceId)
                ->where('type', $aVals['type'])
                ->where('postcode', $aVals['postcode'])
                ->where('miles', $aVals['miles'])
                ->when(!empty($aVals['postcode_old']) && !empty($aVals['miles_old']), function ($query) use ($aVals) {
                    // Exclude the current record being updated
                    $query->where(function ($q) use ($aVals) {
                        $q->where('postcode', '!=', $aVals['postcode_old'])
                        ->orWhere('miles', '!=', $aVals['miles_old']);
                    });
                })
                ->exists();

            if ($duplicate) {
                return $this->sendError('This postcode and miles combination already exists for this service.');
            }

            // Perform update only (no create)
            UserServiceLocation::where('user_id', $userId)
                ->where('service_id', $serviceId)
                ->where('postcode', $aVals['postcode_old'])
                ->where('miles', $aVals['miles_old'])
                ->where('type', $aVals['type'])
                ->update([
                    'postcode' => $aVals['postcode'],
                    'miles' => $aVals['miles'],
                    'city' => $aVals['city'],
                    'travel_time' => $travel_time,
                    'travel_by' => $travel_by,
                    'nation_wide' => $nationWide,
                ]);
        }

        return $this->sendResponse(__('Location updated successfully'));
    }

    public function editUserLocation11(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        $validator = Validator::make($aVals, [
            'service_id' => [
                'required',
                'exists:categories,id',
            ],
            'user_id' => 'required|exists:users,id',
        ],
        [
            'user_id.exists' => 'The selected user does not exist.',
            'service_id.exists' => 'The selected service does not exist.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);

        // $userlocations = UserServiceLocation::where('user_id',$userId)
        // ->where('postcode',$aVals['postcode'])
        // ->where('miles',$aVals['miles'])
        // ->where('type',$aVals['type'])
        // ->first();

        // if(isset($userlocations) && $userlocations !=''){
        // return $this->sendError('Postcode with the same user already exists');
        // }
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
        
        // Step 1: Remove entries not in the new list
        UserServiceLocation::where('user_id', $userId)
            ->whereIn('postcode', [$aVals['postcode_old']])
            ->where('type', $aVals['type'])
            ->where('miles', $aVals['miles'])
            ->delete();
        
        // Step 2: Update or create the rest
        foreach ($serviceIds as $serviceId) {
            $userService = UserService::where('user_id', $userId)
                                    ->where('service_id', $serviceId)
                                    ->first();

            if (!$userService) {
                continue; // skip if user_service does not exist
            }
            
            $userServiceId = $userService->id;
            // $postcode = isset($aVals['postcode']) && $aVals['postcode'] !== '' ? $aVals['postcode'] : '000000';
            // $miles = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 0 : $aVals['miles'];
            $nationWide = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 1 : 0;
           
            // $aLocations['service_id'] = $serviceId;
            // $aLocations['user_service_id'] = $userServiceId;
            // $aLocations['user_id'] = $aVals['user_id'];
            // $aLocations['postcode'] = $aVals['postcode'];
            // $aLocations['miles'] = $aVals['miles'];
            // $aLocations['nation_wide'] = $nationWide;
            // $aLocations['city'] = $aVals['city'];
            // $aLocations['travel_time'] = $travel_time;
            // $aLocations['travel_by'] = $travel_by;
            // $aLocations['type'] = $aVals['type'];
            // dd($userServiceId);
            $aLocation =  UserServiceLocation::where('user_id',$userId)
                                             ->where('postcode',$aVals['postcode'])
                                             ->where('service_id',$serviceId)
                                             ->where('type',$aVals['type'])
                                             ->update(['miles' => $aVals['miles'],
                                                'city'=>$aVals['city'],
                                                'travel_time'=>$travel_time,
                                                'travel_by'=>$travel_by,
                                                'nation_wide'=>$nationWide,
                                                ]);
            // UserServiceLocation::updateUserServiceLocation($aLocations);
        }

        return $this->sendResponse(__('Location updated successfully'));
    }

    public function removeLocation(Request $request)
    {
        $aValues = $request->all();
        if($aValues['nation_wide'] == 1){
                UserServiceLocation::where('nation_wide', 1)
                            ->where('user_id', $aValues['user_id'])
                            ->delete();
        }else{
                UserServiceLocation::whereIn('postcode', [$aValues['postcode']])
                            ->where('user_id', $aValues['user_id'])
                            ->delete();
        }
        
        return $this->sendResponse('Location deleted sucessfully', []);
    }

    public function leadsByFilter(Request $request){
        $aVals = $request->all();
        $user_id = $aVals['user_id'];
        $spotlights = [
            'All lead spotlights' => 'all',
            'Urgent requests' => 'is_urgent',
            'Updated requests' => 'is_updated',
            'Has additional details' => 'has_additional_details',
        ];
        
        $leadSpotlights = self::filterCount($spotlights, $user_id);
        $leadTimeCounts = self::getLeadTimeData($user_id);
        $services = self::getFilterservices1($user_id);
        $location = self::getFilterLocations1($user_id);
        $credits = self::getFilterCreditList1($user_id);
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

    public function filterCount($spotlights, $user_id)
    {
        $leadSpotlights = [];
        
        $filters = [
            'postcode' => null, // Add if using distance filtering
            'miles' => null,
            'unread' => null, // Add if needed
            'service_ids' => [], // Add if filtering by services
            'credit_ranges' => [], // Same
            'lead_time' => null, // Same
        ];

        foreach ($spotlights as $label => $column) {
            $query = $this->getFilteredLeadQuery($user_id, $filters);

            if ($column !== 'all') {
                $query = $query->where($column, 1);
            }

            $leads = $query->get();

            // Now filter by answer match
            $preferenceMap = $this->getUserPreferenceMap($user_id);
            

             $filteredLeads = $leads->filter(function ($lead) use ($preferenceMap) {
            $leadQuestions = json_decode($lead->questions, true);
            if (!is_array($leadQuestions)) return false;
           
            foreach ($leadQuestions as $q) {
                $buyerAnswers = (array) $q['ans'];
  
                foreach ($buyerAnswers as $rawAnswer) {
                     // Split multiple answers by comma
                    $answers = array_map('trim', explode(',', $rawAnswer));

                    foreach ($answers as $answer) {
                        if (!isset($preferenceMap[$answer])) {
                            return false; // One of the answers not matched by seller
                        }
                    }
                }
            }

            return true;
        });
            // $filteredLeads = $leads->filter(function ($lead) use ($preferenceMap) {
            //     $leadQuestions = json_decode($lead->questions, true);
            //     if (!is_array($leadQuestions)) return false;

            //     foreach ($leadQuestions as $q) {
            //         $buyerAnswers = (array) $q['ans'];
            //         foreach ($buyerAnswers as $rawAnswer) {
            //             $answers = array_map('trim', explode(',', $rawAnswer));
            //             foreach ($answers as $answer) {
            //                 if (!isset($preferenceMap[$answer])) {
            //                     return false;
            //                 }
            //             }
            //         }
            //     }
            //     return true;
            // });

            $leadSpotlights[] = [
                'spotlight' => $label,
                'count' => $filteredLeads->count(),
            ];
        }

        return $leadSpotlights;
    }


    public static function getLeadTimeData($user_id = null)
    {
        $now = Carbon::now();
        $instance = new self(); // because basequery is not static
        $baseQuery = $instance->basequery($user_id);

        $timeFilters = [
            'Any time' => function () use ($baseQuery) {
                return (clone $baseQuery)->count();
            },
            'Today' => function () use ($baseQuery, $now) {
                return (clone $baseQuery)->whereDate('created_at', $now->toDateString())->count();
            },
            'Yesterday' => function () use ($baseQuery, $now) {
                return (clone $baseQuery)->whereDate('created_at', $now->copy()->subDay()->toDateString())->count();
            },
            'Last 2-3 days' => function () use ($baseQuery, $now) {
                return (clone $baseQuery)->whereDate('created_at', '>=', $now->copy()->subDays(3))->count();
            },
            'Last 7 days' => function () use ($baseQuery, $now) {
                return (clone $baseQuery)->whereDate('created_at', '>=', $now->copy()->subDays(7))->count();
            },
        ];

        $result = [];
        foreach ($timeFilters as $label => $callback) {
            $result[] = [
                'time' => $label,
                'count' => $callback(),
            ];
        }

        return $result;
    }

    public function getFilterservices1($user_id)
    {
        $serviceIds = UserService::where('user_id', $user_id)->pluck('service_id')->toArray();
        $categories = Category::whereIn('id', $serviceIds)->get();

        foreach ($categories as $category) {
            // Use basequery to get all lead IDs matching filters
            $leads = $this->basequery($user_id)->where('service_id', $category->id)->get();
            $category['locations'] = UserServiceLocation::where('user_id', $user_id)->where('service_id', $category->id)->count();
            $category['leadcount'] = $leads->count();
        }

        return $categories;
    }

    public function getFilterLocations1($user_id)
    {
        $aRows = UserServiceLocation::where('user_id', $user_id)->orderBy('postcode')->get();
        $uniqueRows = $aRows->unique('postcode')->values();

        foreach ($uniqueRows as $row) {
            // Use basequery and apply postcode match
            $leadCount = $this->basequery($user_id)
                            ->where('postcode', $row->postcode)
                            ->count();

            $row['total_services'] = $aRows->where('postcode', $row->postcode)->count();
            $row['leadcount'] = $leadCount;
        }

        return $uniqueRows;
    }

    public function getFilterCreditList1($user_id = null)
    {
        $creditList = CreditList::get();
    
        foreach ($creditList as $creditItem) {
            if (preg_match('/(\d+)\s*-\s*(\d+)/', $creditItem->credits, $matches)) {
                $min = (int)$matches[1];
                $max = (int)$matches[2];
    
                // Cast credit_score to integer if stored as string
                $creditItem['leadcount'] = $this->basequery($user_id)
                    ->whereRaw('CAST(credit_score AS UNSIGNED) BETWEEN ? AND ?', [$min, $max])
                    ->count();
            } else {
                $creditItem['leadcount'] = 0;
            }
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

    public function getFilterLocations($user_id)
    {
        $aRows = UserServiceLocation::where('user_id', $user_id)
            ->orderBy('postcode')
            ->get();

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
            $value['total_services'] = $items->count();
            $value['leadcount'] = LeadRequest::where('postcode', $first->postcode)->count();
            $value['service_ids'] = $items->pluck('service_id')->unique()->values();

            $finalRows->push($value);
        }

        return $finalRows;
    }

    public function getFilteredLeadQuery($user_id, $filters = [])
    {
        $baseQuery = $this->basequery($user_id, $filters['postcode'] ?? null, $filters['miles'] ?? null);

        $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)->pluck('lead_id')->toArray();
        $excludedLeadIds = array_merge($savedLeadIds, $recommendedLeadIds);
        if (!empty($excludedLeadIds)) {
            $baseQuery = $baseQuery->whereNotIn('id', $excludedLeadIds);
        }

        if (!empty($filters['unread'])) {
            $baseQuery = $baseQuery->where('is_read', 0);
        }

        if (!empty($filters['service_ids'])) {
            $baseQuery = $baseQuery->whereIn('service_id', $filters['service_ids']);
        }

        if (!empty($filters['credit_ranges'])) {
            $baseQuery = $baseQuery->where(function ($query) use ($filters) {
                foreach ($filters['credit_ranges'] as $range) {
                    $query->orWhereBetween('credit_score', $range);
                }
            });
        }

        if (!empty($filters['lead_time'])) {
            $now = Carbon::now();
            $baseQuery = $baseQuery->where(function ($query) use ($filters, $now) {
                switch ($filters['lead_time']) {
                    case 'Today':
                        $query->whereDate('created_at', $now->toDateString());
                        break;
                    case 'Yesterday':
                        $query->whereDate('created_at', $now->subDay()->toDateString());
                        break;
                    case 'Last 2-3 days':
                        $query->whereDate('created_at', '>=', Carbon::now()->subDays(3));
                        break;
                    case 'Last 7 days':
                        $query->whereDate('created_at', '>=', Carbon::now()->subDays(7));
                        break;
                    case 'Last 14+ days':
                        $query->whereDate('created_at', '<', Carbon::now()->subDays(14));
                        break;
                }
            });
        }

        return $baseQuery;
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

    public function getLeadProfile_22_05_2025(Request $request){
        $aVals = $request->all();
        $users = User::where('id',$aVals['customer_id'])->first();  

        $myip = $request->ip();
        $visited_date = date("Y-m-d");
        $visitor = UniqueVisitor::where('seller_id',$aVals['user_id'])
                                ->where('buyer_id',$aVals['customer_id'])
                                ->where('ip_address',$myip)
                                ->where('date',$visited_date)->first();
        if(empty($visitor)){
                // $visitor->visitors_count = $visitor->visitors_count +1;
                // $visitor->save();
        // }else{
                $visitor = new UniqueVisitor;
                $visitor->ip_address = $myip;
                $visitor->date = $visited_date;
                $visitor->seller_id = $aVals['user_id'];
                $visitor->buyer_id = $aVals['customer_id'];
                $visitor->lead_id = $aVals['lead_id'];
                $visitor->visitors_count = 1;
                $visitor->save();
        }
        

        if ($users) {
            // Update is_read = 1 for all lead requests of this user (or filter as needed)
            LeadRequest::where('customer_id', $users->id)->update(['is_read' => 1]);
        
            // Fetch updated lead request with relationships
            $leads = LeadRequest::with(['customer', 'category'])
                                ->where('id', $aVals['lead_id'])
                                ->where('customer_id', $users->id)
                                ->first();
            $leads->purchase_type = RecommendedLead::where('lead_id', $aVals['lead_id'])
                                       ->where('buyer_id', $aVals['customer_id'])
                                       ->where('seller_id', $aVals['user_id'])
                                       ->pluck('purchase_type')
                                       ->first();                   
            // $leads->responsestatus = UserResponseTime::where('lead_id',$leads->id)->where('buyer_id',$leads->customer_id)->where('seller_id',$leads['customer']['id'])->first();
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
            return $this->sendResponse('Data added Sucessfully', []);   
        }      
        return $this->sendError('Data already added for this user');                                              
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
            return $this->sendResponse(__('Switched update'), []);
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
        if(isset($userdetails) && $userdetails != ''){
            $userdetails->update(['autobid_pause' => $aVals['autobid_pause']]);
        }else{
            $userdetails = UserDetail::create([
                'user_id'  => $aVals['user_id'],
                'autobid_pause' => $aVals['autobid_pause']
            ]);
        }
        
        if($aVals['autobid_pause'] == 1){
            $modes = 'Autobid is inactive';
        }else{
            $modes = 'Autobid is active';
        }
        $autobidpause = $aVals['autobid_pause'];
        // return $this->sendResponse($modes, $autobidpause); 
        return $this->sendResponse($modes, [
            'autobidpause' => $autobidpause
        ]);                                          
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
        $sellers = User::where('id',$aVals['user_id'])->pluck('name')->first();
        $buyer = User::where('id',$aVals['buyer_id'])->pluck('name')->first();
        $activityname = "";
        if($type == 'Whatsapp'){
            // $activityname = $sellers .' viewed '. $buyer .' profile';
            $activityname = 'You contacted '. $buyer .' through Whatsapp';
        }
        if($type == 'email'){
           $activityname = 'You contacted '. $buyer .' through email'; 
        }
        if($type == 'mobile'){
            $activityname = 'You contacted '. $buyer .' through mobile';
        }
        if($type == 'sms'){
            $activityname = 'You contacted '. $buyer .' through SMS';
        }
        $leadtime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();
        $isActivity = self::getActivityLog($aVals['user_id'],$aVals['buyer_id'],$aVals['lead_id'],$activityname);
        if(empty($isActivity)){
            self::addActivityLog($aVals['user_id'],$aVals['buyer_id'],$aVals['lead_id'],$activityname, $type, $leadtime);
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
        $isNotes = SellerNote::where('id',$aVals['note_id'])->first();
        
        if(!empty($isNotes)){
            $isNotes->where('id',$aVals['note_id'])->update(['notes'=>$aVals['notes']]);
        }else{
            $isNotes = SellerNote::create([
                'seller_id'  => $aVals['user_id'],
                'buyer_id'  => $aVals['buyer_id'],
                'lead_id'  => $aVals['lead_id'],
                'notes' => $aVals['notes'],
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
                             ->first();
        if(!empty($isNotes)){
            $isNotes = $isNotes;
        }else{
            $isNotes = "";
        }
        
        return $this->sendResponse(__('No Notes added'), [
                'notes' => $isNotes
            ]);                                  
    }

    public function pendingPurchaseTypeFilter(Request $request){
        $aVals = $request->all();
        $user_id = $request->user_id;
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
                                             ->where('purchase_type',$aVals['purchase_type'])
                                             ->pluck('lead_id')
                                             ->toArray();
      
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
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
                                             ->where('purchase_type',$aVals['purchase_type'])
                                             ->pluck('lead_id')
                                             ->toArray();

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
