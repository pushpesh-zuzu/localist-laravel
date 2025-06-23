<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Models\UserServiceLocation;
use App\Models\UserResponseTime;
use App\Models\ServiceQuestion;
use App\Models\LeadPrefrence;
use App\Models\SaveForLater;
use App\Models\LeadRequest;
use App\Models\ActivityLog;
use App\Models\UserService;
use App\Models\UserDetail;
use App\Models\LeadStatus;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Models\RecommendedLead;
use App\Models\AutobidStatusLog;

use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use \Carbon\Carbon;
use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\Log;

class RecommendedLeadsController extends Controller
{

    public function switchAutobid(Request $request): JsonResponse
    {
        $userdetails = UserDetail::where('user_id',$request->user_id)->first();
        if(!empty($userdetails)){
            $user_id = $request->user_id; 
            $autobid = $request->is_autobid;
            $data['is_autobid'] = $autobid;
            $data['autobid_pause'] = 0;            
            $data['updated_at'] = date('Y-m-d H:i:s');
            UserDetail::where('user_id', $user_id)->update($data);

            $bidStatus = $autobid == 1 ? 'enabled' : 'disabled';
            $data2['user_id'] = $request->user_id;
            $data2['action'] = $bidStatus;
            AutobidStatusLog::insertGetId($data2);
            return $this->sendResponse(__('Autobid switched successfully'), $data ); 
        }
        return $this->sendError('User not found!');
          
    }

    public function getAutobid(Request $request){ 
        $aVals = $request->all();
        $isDataExists = UserDetail::where('user_id',$aVals['user_id'])->first();
        if(!empty($isDataExists)){
            return $this->sendResponse(__('Autobid Data'), [
                'isautobid' => $isDataExists->is_autobid
            ]);
        }      
        return $this->sendError('User not found');                                              
    }

    public function getRecommendedLeads(Request $request) 
    {
        $seller_id = $request->user_id; 
        $leadid = $request->lead_id; 
        $result = [];

        if (!empty($leadid)) {
            $lead = LeadRequest::find($leadid); // get lead created_at
            if (!$lead) {
                return $this->sendResponse('Lead not found', []);
            }

            // Fetch all matching bids
            $bids = RecommendedLead::where('buyer_id', $seller_id)
                ->where('lead_id', $leadid)
                // ->where('distance','!=', 0)
                ->orderBy('distance', 'ASC')
                ->get();

            
            // Get seller IDs and unique service IDs
            $sellerIds = $bids->pluck('seller_id')->toArray();
            $serviceIds = $bids->pluck('service_id')->unique()->toArray();

            // Get users and categories
            $users = User::whereIn('id', $sellerIds)->get()->keyBy('id'); // index by seller_id
            $services = Category::whereIn('id', $serviceIds)->pluck('name', 'id'); // id => name

            foreach ($bids as $bid) {
                $seller = $users[$bid->seller_id] ?? null;
                if ($seller) {
                    // 👇 Apply quicktorespond check
                    $contactTypes = ['Whatsapp', 'email', 'mobile', 'sms'];
                    $firstResponse = ActivityLog::where('lead_id', $leadid)
                        ->where('from_user_id', $bid->seller_id)
                        ->whereIn('contact_type', $contactTypes)
                        ->orderBy('created_at', 'asc')
                        ->first();

                    $quickToRespond = 0;
                    if ($firstResponse) {
                        $leadTime = Carbon::parse($lead->created_at)->setTimezone('Asia/Kolkata');
                        $createdAt = $firstResponse->created_at->copy()->setTimezone('Asia/Kolkata');

                        $diffInMinutes = round(abs($leadTime->diffInMinutes($createdAt)));
                        if ($diffInMinutes <= 720) {
                            $quickToRespond = 1;
                        }
                    }

                    $sellerData = $seller->toArray();
                    $sellerData['service_name'] = $services[$bid->service_id] ?? 'Unknown Service';
                    $sellerData['bid'] = $bid->bid;
                    $sellerData['distance'] = $bid->distance;
                    $sellerData['quicktorespond'] = $quickToRespond;

                    $result[] = $sellerData;
                }
            }
        }

        return $this->sendResponse(__('AutoBid Data'), $result);
    }
    
    public function getManualLeads(Request $request){
        
        $lead = LeadRequest::find($request->lead_id);
        

        if (!$lead) return $this->sendError(__('No Lead found'), 404);
        $responseTimeFilter = $request->responseTimeFilter ?? [];
        $ratingFilter = $request->rating ?? [];

        $result = $this->getAllSellers($lead);

        if(!empty($result['response']['sellers'])){
            // for weightage sorting
            $recommendedCount = CustomHelper::setting_value("recommended_list_count", 0);
            $w80 = (int) ($recommendedCount * 0.8);
            
            // Step 1: Sort all by credit_score DESC
            $sorted = $result['response']['sellers']
                ->sortByDesc('total_credit')           
                ->values();

            $topN = $sorted->take($w80); //Step 2: Take first 4
            $remaining = $sorted->slice($w80)             // Step 3: Get remaining
                ->sortBy('distance')                   // Sort remaining by distance ASC
                ->values();

            $finalSorted = $topN->merge($remaining);

            $result['response']['sellers'] = $finalSorted->values()->toArray();
        }else{
            return $this->sendResponse('No Seller Found!', [$result['response']]);
        }
        
        return $this->sendResponse('Your Matches List', [$result['response']]);
    }
    
    public function closeLeads(Request $request){
        // unpause auto bid after 7 days
        $this->unpauseAutobidAfter7Days();
        //close leads after 14 days
        $this->leadCloseAfter2Weeks();
        
        $now = Carbon::now();
        $fiveMinutesAgo = $now->copy()->subMinutes(5);
        

        //start getting auto bid leads
        //get Leads which are N minutes older
        $startBidAfter = CustomHelper::setting_value("start_autobid_after", 5);
        //get Leads which are created N munites before
        $nMinutesAgo = Carbon::now()->subMinutes($startBidAfter);
        $leads = LeadRequest::where('closed_status', 0)
            ->where('should_autobid', 0)
            ->where('created_at', '>=', $nMinutesAgo)
            // ->toRawSql();
            ->get();
        foreach($leads as $lead){            
            $sellers = $this->getAllSellers($lead, [], true);
            if(!empty($sellers['response']['sellers'])){
                foreach($sellers['response']['sellers'] as $s){                    
                    $request->replace($request->only(['user_id']));
                    $request['bidtype'] = 'autobid';
                    $request['lead_id'] = $lead->id;
                    $request['service_id'] = $lead->service_id;
                    $request['distance'] = $s->distance;
                    $request['seller_id'] = $s->id;
                    $request['user_id'] = $lead->customer_id;
                    $fResponse =  $this->addManualBid($request);
                    $fData = json_decode($fResponse->getContent(), true);
                    if (!empty($fData['success'])) {
                        print_r("Autobid inserted for lead_id: " .$lead->id ."; sellerId: " .$s->id);
                    }else{

                    }
                }
                
            }
        }


        
    }

    private function getAllSellers($lead, $filters = [], $autobid = false){
        // echo "<pre>";print_r($lead->toArray());exit;

        $recommendedCount = CustomHelper::setting_value("recommended_list_count", 5);
        $serviceId = $lead->service_id;
        $leadCreditScore = $lead->credit_score;
        $refPostcode = $lead->postcode;
        $customerId = $lead->customer_id;
        $question = $lead->arrayed_questions;
        $serviceName = Category::find($serviceId)->name ?? '';
        
        if (!is_array(json_decode($question, true))) {
            return $this->sendError('Invalid or missing lead questions', 404);
        }

        // Step 1: Get lat/lng of reference postcode
        $ref = DB::table('postcodes')
            ->where('postcode', $refPostcode)
            ->select('latitude', 'longitude')
            ->first();

        if (!$ref) {
            throw new \Exception("Reference postcode not found: $refPostcode");
        }

        $refLat = $ref->latitude;
        $refLng = $ref->longitude;

        // users who have contacted this lead
        $repliesUsers = RecommendedLead::where('lead_id', $lead->id)
            ->where('service_id', $serviceId)
            ->pluck('seller_id')->toArray();

        // Step 2: Preselect user_service_locations using simplified logic
        $rows = DB::table('user_service_locations as usl')
            ->join('users', function ($join) use ($repliesUsers)  {
                $join->on('users.id', '=', 'usl.user_id')
                    ->where('users.form_status', 1)
                    ->whereNotIn('users.id', $repliesUsers);
                    
            })
            ->join('user_details', 'user_details.user_id', '=', 'users.id')
            ->join('postcodes as p', 'p.postcode', '=', 'usl.postcode')
            ->leftJoin('user_response_times as urt', 'urt.seller_id', '=', 'usl.user_id')
            ->where('users.id' ,'<>', $lead->customer_id) //do not include self as seller
            ->where('usl.service_id', $serviceId)
            ->where('users.total_credit', '>=', (int) $leadCreditScore)
            ->select(
                'users.id as id',
                'users.name',
                'users.profile_image',
                'users.total_credit',
                'users.avg_rating',
                'users.form_status',
                'user_details.is_autobid',
                'user_details.autobid_pause',
                'usl.user_id',
                'usl.service_id',
                'usl.miles',
                'usl.nation_wide',
                'usl.postcode',
                'urt.average as response_time',
                'p.latitude as lat',
                'p.longitude as lng'
            );
        //for autobid sellers include below contions
        if($autobid){

            //include seller who have enabled abutobid and autobid not paused for 7 days
            $rows = $rows->where('user_details.is_autobid', 1)
                ->where('user_details.autobid_pause', 0);

            $autobidLimit = CustomHelper::setting_value("autobid_limit", 3);
            $autobidDaysLimit = CustomHelper::setting_value("autobid_days_limit", 7);

            $sellerCompletedAutoBid = RecommendedLead::select('seller_id', DB::raw('COUNT(*) as total_bids'), DB::raw('MIN(created_at) as first_bid_date'))
                ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->where('purchase_type', 'Autobid')
                ->groupBy('seller_id')
                ->havingRaw('COUNT(*) >= ?', [$autobidLimit])
                ->pluck('seller_id')
                ->toArray();

            $rows = $rows->whereNotIn('users.id', $sellerCompletedAutoBid);

                
        }
       
        if(!empty($filters['rating'])){
            if($filters['rating'] === 'no_rating'){
                $rows = $rows->where('users.avg_rating', 0);
                
            }else if($filters['rating'] === 5){
                $rows = $rows->where('users.avg_rating', '=', 5);
            }else{
                $rows = $rows->where('users.avg_rating', '>=', $filters['rating']);
            }
        }

        //response_time filter
        if(!empty($filters['response_time'])){
            $timeThresholds = [
                'Responds within 10 mins' => 10,
                'Responds within 1 hour' => 60,
                'Responds within 6 hours' => 360,
                'Responds within 24 hours' => 1440,
            ];
            $maxAllowed = $timeThresholds[$filters['response_time']] ?? null;

            $rows = $rows->where('urt.average','<>', null)
                ->where('urt.average', '<=', $maxAllowed);
        }    
            
        $rows = $rows->get();

        

        // Step 3: Group by user_id + postcode, keep nation_wide=1 if present, else max miles
        $grouped = $rows->groupBy(fn($row) => $row->user_id . '_' . $row->postcode)
            ->map(function ($items) {
                $nationwide = $items->firstWhere('nation_wide', 1);
                return $nationwide ?: $items->sortByDesc('miles')->first();
            })
            ->map(function ($r) use($serviceName, $leadCreditScore){
                $r->credit_score = $leadCreditScore;
                $r->service_name= $serviceName;
                $r->quicktorespond = ($r->response_time > 0 && $r->response_time <= 720) ? 1 : 0;
                return $r;
            });


        // Step 4: Filter by distance using Haversine Formula
        $filteredUsers = $grouped->filter(function ($row) use ($refLat, $refLng, $refPostcode) {
            $distance = 3958.8 * acos(
                cos(deg2rad($refLat)) * cos(deg2rad($row->lat)) * cos(deg2rad($row->lng) - deg2rad($refLng)) +
                sin(deg2rad($refLat)) * sin(deg2rad($row->lat))
            );

            $row->distance = (double) round($distance, 2); // add distance field
            
            return $row->nation_wide == 1
                || $row->postcode == $refPostcode
                || $row->miles >= $distance;
        });

        
        $final = $this->usersAccordingToPrefs($question, $filteredUsers, $serviceId)->sortBy('distance');
        
        if(!empty($filters['distance_order'])){
            if($filters['distance_order'] === 'farthest to nearest'){
                $final = $final->sortByDesc('distance');
            }
        }

        return [
            'empty' => empty($final) ? true : false,
            'response' => [
                'service_name' => $serviceName,
                'sellers' => $final,
                'displayCount' => $recommendedCount ?? 0,
                'baseurl' => url('/') . Storage::url('app/public/images/users'),
                'w80' => (int) ($recommendedCount * 0.8)
            ]
        ];
    }
    private function usersAccordingToPrefs($arrayed_questions, $filteredUsers, $serviceId){
        $arrayedQuestions = json_decode($arrayed_questions, true);
        
        $userIds = $filteredUsers->pluck('user_id')->all();

        // Load preferences of filtered users
        $rawAnswers = LeadPrefrence::with(['question'])
            ->whereIn('user_id', $userIds)
            ->where('service_id', $serviceId)
            ->get();
        $prefs = [];
        foreach ($rawAnswers as $ra) {
            $temp['user_id'] = $ra->user_id;
            $temp['service_id'] = $ra->service_id;
            $temp['question'] = $ra->question->questions;
            $temp['answers'] = array_map('trim', explode(',', $ra->answers));
            $prefs[] = $temp;
        }

        // Group by user_id
        $groupedPrefs = collect($prefs)->groupBy('user_id')->toArray();

        // Run match logic
        $matchedUserIds = $this->filterMatchingUsers($arrayedQuestions, $groupedPrefs);

        // Final filtered result
        $final = $filteredUsers->filter(function ($row) use ($matchedUserIds) {
            return in_array($row->user_id, $matchedUserIds);
        });

        return $final;
    }
    
    public function filterMatchingUsers(array $arrayedQuestions, array $groupedPrefs): array
    {
        $matchingUserIds = [];

        foreach ($groupedPrefs as $userId => $prefs) {
            $prefMap = [];

            foreach ($prefs as $pref) {
                $question = is_object($pref) ? $pref->question : ($pref['question'] ?? null);
                $answers = is_object($pref) ? $pref->answers : ($pref['answers'] ?? []);

                if (is_string($question)) {
                    $normalizedQ = $this->normalizeQuestion($question);
                    $prefMap[$normalizedQ] = array_map(function ($a) {
                        return strtolower(trim($a));
                    }, $answers);
                }
            }

            $matchedAll = true;

            foreach ($arrayedQuestions as $q) {
                $question = $this->normalizeQuestion($q['ques']);
                $leadAnswers = array_map('strtolower', array_map('trim', $q['ans']));
                $userAnswers = $prefMap[$question] ?? [];

                // Log for debugging
                logger("User ID: $userId | Question: {$q['ques']} => $question");
                logger("Lead Answers: ", $leadAnswers);
                logger("User Prefs: ", $userAnswers);

                // Case 4: match if user pref contains "other"
                if (in_array('other', $userAnswers)) {
                    continue;
                }

                // Case 3: exclude if no overlap
                if (empty(array_intersect($leadAnswers, $userAnswers))) {
                    logger("❌ Mismatch on: {$q['ques']}");
                    logger("Lead Answers: ", $leadAnswers);
                    logger("User Prefs: ", $userAnswers);
                    $matchedAll = false;
                    break;
                }
            }

            if ($matchedAll) {
                logger("✅ Matched user: $userId");
                $matchingUserIds[] = $userId;
            }
        }

        return $matchingUserIds;
    }

    private function normalizeQuestion(string $question): string{
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9 ]/', '', $question)));
    }



    public function getRatingFilter(Request $request)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $ratings = [];

        for ($i = 1; $i <= 5; $i++) {
            // Simulate rating filter exactly like the `ratingFilter` method
            $result = $this->getAllSellers($lead, ['rating' => $i]);

            $ratings[] = [
                'label' => $i == 5 ? 'only' : '& up',
                'value' => $i,
                'count' => count($result['response']['sellers']),
            ];
        }

         // Handle sellers with no rating (avg_rating is null)
        $resultNoRating = $this->getAllSellers($lead, ['rating' => 'no_rating']);

        $ratings[] = [
            'label' => 'No rating',
            'value' => 'no_rating',
            'count' => count($resultNoRating['response']['sellers']),
        ];

        return $this->sendResponse(__('Filtered Data by Rating'), [$ratings]);
    }

    public function ratingFilter(Request $request)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $rating = $request->rating;

        // Accept 1-5 or "no_rating"
        if (!in_array($rating, ['1', '2', '3', '4', '5', 'no_rating'], true)) {
            return $this->sendError(__('Invalid rating value'), 400);
        }

        // Cast numeric strings to int
        $selectedRating = is_numeric($rating) ? (int) $rating : $rating;

        // Pass to your filtering logic
        $result = $this->getAllSellers($lead, ['rating' => $selectedRating]);
        $result['response']['sellers'] = $result['response']['sellers']->values()->toArray();

        return $this->sendResponse(__('Filtered Data by Rating'), [$result['response']]);
    }
    
    public function sortByLocation(Request $request)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $distanceOrderRaw = $request->distance_order;
        
        $result = $this->getAllSellers($lead, [ 'distance_order' => $distanceOrderRaw]);
        $result['response']['sellers'] = $result['response']['sellers']->values()->toArray();

        return $this->sendResponse(__('Sorting by distance'), [$result['response']]);
    }

    public function responseTimeFilter(Request $request)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $responseTimeFilter = $request->response_time; // Expected: '10_min', '1_hour', '6_hour', '24_hour'
        $result = $this->getAllSellers($lead, ['response_time' => $responseTimeFilter]);
        $result['response']['sellers'] = $result['response']['sellers']->values()->toArray();

        return $this->sendResponse(__('Filtered Data by Response Time'), [$result['response']]);
    }

    public function buyerActivities(Request $request)
    {
        $aVals = $request->all();
        $isActivity = ActivityLog::where('lead_id', $aVals['lead_id'])
        ->where(function ($query) use ($aVals) {
            $query->where(function ($q) use ($aVals) {
                $q->where('from_user_id', $aVals['user_id']) // seller viewed buyer
                  ->where('to_user_id', $aVals['buyer_id']);
            })->orWhere(function ($q) use ($aVals) {
                $q->where('from_user_id', $aVals['buyer_id']) // buyer viewed seller
                  ->where('to_user_id', $aVals['user_id']);
            });
        })
        ->get();
        
        return $this->sendResponse(__('Activity log'),$isActivity);     
    }
    


    public function addManualBid(Request $request){
        $aVals = $request->all();
        if(!isset($aVals['bidtype']) || empty($aVals['bidtype'])){
            return $this->sendError(__('Lead request not found'), 404);
        }
        $isDataExists = LeadStatus::where('lead_id',$aVals['lead_id'])->where('status','pending')->first();
        $leadTime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();
        $creditScore = LeadRequest::where('id',$aVals['lead_id'])->value('credit_score');
        
        $leadSlotCount = CustomHelper::setting_value("lead_slot_count", 5);

        $bsId = !empty($aVals['seller_id']) ? $aVals['seller_id'] : $aVals['user_id'];

        $totalCredit = User::where('id', $bsId)->value('total_credit');
        //check if seller has enough credits
        if($creditScore > $totalCredit){
            return $this->sendError(__("Seller don't have sufficient balance"), 404);
        }
        //check if same seller has placed bid or not for this lead
        $bidCheck = RecommendedLead::where('lead_id', $aVals['lead_id'])
            ->where('service_id', $aVals['service_id'])
            ->where('buyer_id', $aVals['user_id'])
            ->where('seller_id',$bsId)
            ->first();
        if(!empty($bidCheck)){
            return $this->sendError('Bid Already Placed for this seller', 404);
        }
        
        // check if N bids has been placed on this lead or not
        $totalBidCount = RecommendedLead::where('lead_id', $aVals['lead_id'])
            ->where('service_id', $aVals['service_id'])
            ->count();
        if($totalBidCount >= $leadSlotCount){
            $word = CustomHelper::numberToWords($leadSlotCount);
            return $this->sendError($word .' slots has been full! No more bids can be placed.', 404);
        }
        $info = "";
        if($aVals['bidtype'] == 'reply'){
            
            $bids = RecommendedLead::create([
                'service_id' => $aVals['service_id'], 
                'seller_id' => $aVals['seller_id'], 
                'buyer_id' => $aVals['user_id'], //buyer
                'lead_id' => $aVals['lead_id'], 
                'bid' => $creditScore, 
                'distance' => $aVals['distance'], 
                'purchase_type' => 'Request Reply'
            ]);
            $logInfo = "Requested a callback";
            $trInfo = $creditScore . " credit deducted for Request Reply";
            self::addActivityLog($aVals['user_id'],$aVals['seller_id'],$aVals['lead_id'],$logInfo, "Request Reply", $leadTime);
            //deduct credit
            DB::table('users')->where('id', $aVals['seller_id'])->decrement('total_credit', $creditScore);
            //create transaction log
            CustomHelper::createTrasactionLog($aVals['seller_id'], 0, $creditScore, $trInfo, 1, 1, $error_response='');            

        }else if($aVals['bidtype'] == 'purchase_leads'){
            $sellerName = User::where('id',$aVals['user_id'])->value('name');
            $buyerName = User::where('id',$aVals['user_id'])->value('name');
            
            $bids = RecommendedLead::create([
                'service_id' => $aVals['service_id'], 
                'seller_id' => $aVals['user_id'], 
                'buyer_id' => $aVals['buyer_id'], //buyer
                'lead_id' => $aVals['lead_id'], 
                'bid' => $creditScore, 
                'distance' => $aVals['distance'], 
                'purchase_type' => "Manual Bid"
            ]);
            $logInfo = 'You Contacted '. $buyerName;            
            $trInfo = $creditScore . " credit deducted for Contacting to Customer";
            self::addActivityLog($aVals['user_id'],$aVals['buyer_id'],$aVals['lead_id'],$logInfo, "Request Reply", $leadTime);
            //deduct credit
            DB::table('users')->where('id', $aVals['user_id'])->decrement('total_credit', $creditScore);
            //create transaction log
            CustomHelper::createTrasactionLog($aVals['user_id'], 0, $creditScore, $trInfo, 1, 1, $error_response='');
        }else{
            
            $bids = RecommendedLead::create([
                'service_id' => $aVals['service_id'], 
                'seller_id' => $aVals['seller_id'], 
                'buyer_id' => $aVals['user_id'], //buyer
                'lead_id' => $aVals['lead_id'], 
                'bid' => $creditScore, 
                'distance' => $aVals['distance'], 
                'purchase_type' => "Autobid"
            ]);
            $trInfo = $creditScore . " credit deducted for Autobid";
            CustomHelper::createTrasactionLog($aVals['seller_id'], 0, $creditScore, $trInfo, 1, 1, $error_response='');
        }

        
        
            
        LeadRequest::where('id',$aVals['lead_id'])->update(['status'=>'pending']);
        //remove from save for later
        SaveForLater::where('seller_id',$aVals['user_id'])
            ->where('user_id',$aVals['user_id'])  
            ->where('lead_id',$aVals['lead_id'])
            ->delete();
        if(empty($isDataExists)){
            LeadStatus::create([
            'lead_id' => $aVals['lead_id'],
                'user_id' => $aVals['user_id'],
                'status' => 'pending',
                'clicked_from' => 2,
            ]);  
        }
        
        return $this->sendResponse(__('Bids placed successfully'),[]);
    }

    public function getActivityLog($from_user_id, $to_user_id, $lead_id, $activity_name){
        $activities = ActivityLog::where('lead_id',$lead_id)
                                          ->where('from_user_id',$from_user_id) 
                                          ->where('to_user_id',$to_user_id) 
                                          ->where('lead_id',$lead_id) 
                                          ->where('activity_name',$activity_name) 
                                          ->first(); 
         return $activities;                                 
   }

    public function addMultipleManualBid(Request $request){
        $aVals = $request->all();
        $request['bidtype'] = 'reply';
        $buyerId = $aVals['user_id'];
        $leadId = $aVals['lead_id'];
        $inserted = 0;
        foreach ($aVals['seller_id'] as $index => $sellerId) {
            $request->replace($request->only(['user_id', 'lead_id','bidtype']));
            $request['service_id'] = $aVals['service_id'][$index];
            $request['distance'] = $aVals['distance'][$index];
            $request['seller_id'] = $sellerId;
            $fResponse =  $this->addManualBid($request);
            $fData = json_decode($fResponse->getContent(), true);
            if (!empty($fData['success'])) {
                $inserted++;
            }
        }
        return $this->sendResponse('Bids placed successfully', [
            'inserted_count' => $inserted,
            'total_now' => RecommendedLead::where('lead_id', $leadId)->count()
        ]);
    }

    
    
    
    
    public function unpauseAutobidAfter7Days()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        // Get all sellers whose auto-bid is paused and last updated more than 7 days ago
        $sellersToUnpause = UserDetail::where('autobid_pause', 1)
            ->where('updated_at', '<=', $sevenDaysAgo)
            ->get();

        foreach ($sellersToUnpause as $seller) {
            $seller->update([
                'autobid_pause' => 0
            ]);
        }
    }

    

    public function leadCloseAfter2Weeks(){
        $leadsToClose = LeadRequest::where('status', 0)
            ->where('closed_status', 0)
            ->where('created_at', '<', Carbon::now()->subDays(14)->toDateString())
            ->get();
        
        foreach ($leadsToClose as $lead) {
            $lead->closed_status = 1; // Mark as closed
            $lead->save();
        }
    }

    

   public function addActivityLog($from_user_id, $to_user_id, $lead_id, $activity_name, $contact_type, $leadtime){
        $activity = ActivityLog::create([
                     'lead_id' => $lead_id,
                     'from_user_id' => $from_user_id,
                     'to_user_id' => $to_user_id,
                     'activity_name' => $activity_name,
                     'contact_type' => $contact_type,
                 ]);     

         // Step 2: Calculate the time difference
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

}   
