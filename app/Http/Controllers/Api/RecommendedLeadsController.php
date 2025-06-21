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
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use \Carbon\Carbon;
use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\Log;

class RecommendedLeadsController extends Controller
{
    
    public function getManualLeads(Request $request)
    {
        
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
                ->sortByDesc('credit_score')           
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
    

    private function getAllSellers($lead, $filters = [], $autobid = false){
        // echo "<pre>";print_r($filters);exit;
        $bidCount = RecommendedLead::where('lead_id', $lead->id)->count();
        $recommendedCount = CustomHelper::setting_value("recommended_list_count", 0);
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
            ->where('users.total_credit', '>=', $leadCreditScore)
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
        //user rating filter

        // echo "<pre>";
        // print_r($refPostcode);
        // exit;


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
                // logger("User ID: $userId | Question: {$q['ques']} => $question");
                // logger("Lead Answers: ", $leadAnswers);
                // logger("User Prefs: ", $userAnswers);

                // Case 4: match if user pref contains "other"
                if (in_array('other', $userAnswers)) {
                    continue;
                }

                // Case 3: exclude if no overlap
                if (empty(array_intersect($leadAnswers, $userAnswers))) {
                    // logger("❌ Mismatch on: {$q['ques']}");
                    // logger("Lead Answers: ", $leadAnswers);
                    // logger("User Prefs: ", $userAnswers);
                    $matchedAll = false;
                    break;
                }
            }

            if ($matchedAll) {
                // logger("✅ Matched user: $userId");
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
        $autoBidLimit = CustomHelper::setting_value("autobid_limit", 0);
        $totalCredit = User::where('id',$aVals['seller_id'])->value('total_credit');
        //check if seller has enough credits
        if($creditScore > $totalCredit){
            return $this->sendError(__("Seller don't have sufficient balance"), 404);
        }
        //check if same seller has placed bid or not for this lead
        $bidCheck = RecommendedLead::where('lead_id', $aVals['lead_id'])
            ->where('service_id', $aVals['service_id'])
            ->where('buyer_id', $aVals['user_id'])
            ->where('seller_id',$aVals['seller_id'])
            ->first();
        if(!empty($bidCheck)){
            return $this->sendError('Bid Already Placed for this seller', 404);
        }
        // check if 5 bids has been placed on this lead or not
        $slotCount = RecommendedLead::where('lead_id', $aVals['lead_id'])
            ->where('service_id', $aVals['service_id'])
            ->count();
        if($slotCount >=5){
            return $this->sendError('Five slots has been full! No more bids can be placed.', 404);
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
                'purchase_type' => "Request Reply"
            ]);
            self::addActivityLog($aVals['user_id'],$aVals['seller_id'],$aVals['lead_id'],"Requested a callback", "Request Reply", $leadTime);
            $info = $creditScore . " credit deducted for Request Reply";            
        }else if($aVals['bidtype'] == 'purchase_leads'){
            $sellerName = User::where('id',$aVals['user_id'])->value('name');
            $buyerName = User::where('id',$aVals['user_id'])->value('name');
            $bids = RecommendedLead::create([
                'service_id' => $aVals['service_id'], 
                'seller_id' => $aVals['seller_id'], 
                'buyer_id' => $aVals['user_id'], //buyer
                'lead_id' => $aVals['lead_id'], 
                'bid' => $creditScore, 
                'distance' => $aVals['distance'], 
                'purchase_type' => "Manual Bid"
            ]);
            $activityname = 'You Contacted '. $buyerName;
            self::addActivityLog($aVals['user_id'], $aVals['user_id'], $aVals['lead_id'], $activityname, "Manual Bid", $leadTime);
            $info = $creditScore . " credit deducted for Contacting to Customer";
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
        //deduct credit
        DB::table('users')->where('id', $aVals['seller_id'])->decrement('total_credit', $creditScore);
        //create transaction log
        CustomHelper::createTrasactionLog($aVals['seller_id'], 0, $creditScore, $info, 1, 1, $error_response='');

       
        return $this->sendResponse(__('Bids placed successfully'),[]);
    }
    

    public function addMultipleManualBid(Request $request){
        $aVals = $request->all();
        $buyerId = $aVals['user_id'];
        $leadId = $aVals['lead_id'];
        $inserted = 0;
        echo "<pre>";print_r($aVals);
    }

    public function closeLeads()
    {
        $now = Carbon::now();
        $fiveMinutesAgo = $now->copy()->subMinutes(5);
        $twoWeeksAgo = $now->copy()->subWeeks(2);
        $sevenDaysAgo = $now->copy()->subDays(7);
        // $twoWeeksAgo = Carbon::now()->subWeeks(2);
        // $fiveMinutesAgo = $now->subMinutes(5);
        // --------- Auto-Close Logic (after 2 weeks) ---------
        $twoWeeks = self::leadCloseAfter2Weeks($twoWeeksAgo);
        if($twoWeeks){
            return response()->json(['message' => 'Leads closed successfully.']);
        }
        // --------- Auto-Bid Logic (after 5 minutes) ---------
        $fiveMinutes = self::autoBidLeadsAfter5Min($fiveMinutesAgo);
        if($fiveMinutes){
            return response()->json(['message' => 'Auto-bid completed for leads older than 5 minutes.']);
        }
        // --------- 7 days after reactivate Logic  ---------
        $sevenDays = self::reactivateAutoBidAfter7Days($sevenDaysAgo);
        // $leadsToClose = LeadRequest::where('id', 249)->update(['closed_status'=>1]);
        if($sevenDays){
            return response()->json(['message' => 'Auto-bid unpaused for sellers paused more than 7 days ago.']);
        }
        
    }
    
    public function autoBidLeadsAfter5Min($fiveMinutesAgo)
    {
        $settings = CustomHelper::setting_value("auto_bid_limit", 0);

         
        //$sellersWith3Autobids = RecommendedLead::select('seller_id', DB::raw('MIN(created_at) as first_bid_date'))
        $sellersWith3Autobids =RecommendedLead::select('seller_id', DB::raw('COUNT(*) as total_bids'), DB::raw('MIN(created_at) as first_bid_date'))
                                                ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                                                ->where('purchase_type', 'Autobid')
                                                ->groupBy('seller_id')
                                                ->havingRaw('COUNT(DISTINCT buyer_id) >= 3')
                                                ->get()
                                                ->filter(function ($record) {
                                                    $autobidDaysLimit = CustomHelper::setting_value('autobid_days_limit', 0);
                                                    return Carbon::parse($record->first_bid_date)->diffInDays(Carbon::now()) < $autobidDaysLimit;
                                                })
                                                ->pluck('seller_id')
                                                ->toArray();

        $leads = LeadRequest::where('closed_status', 0)
                ->where('should_autobid', 0)
                ->where('created_at', '<=', $fiveMinutesAgo)
                ->get();
        // $settings = Setting::first();  
        $autoBidLeads = [];
            
            foreach ($leads as $lead) {
                $isDataExists = LeadStatus::where('lead_id',$lead->id)->where('status','pending')->first();
                $existingBids = RecommendedLead::where('lead_id', $lead->id)->count();
        
                if ($existingBids >= $settings) {
                    continue; // Skip if already has 5 bids
                }
        
                // Call getManualLeads with a request object
                $manualLeadRequest = new Request(['lead_id' => $lead->id]);
                $manualLeadsResponse = $this->getManualLeads($manualLeadRequest)->getData();
                //  $manualLeadsResponse = $this->getManualLeads($lead->id)->getData();
        
                if (empty($manualLeadsResponse->data[0]->sellers)) {
                    LeadRequest::where('id', $lead->id)->update(['should_autobid' => 1]);
                    continue;
                }
        
                $sellers = collect($manualLeadsResponse->data[0]->sellers)->take($settings - $existingBids);
                $bidsPlaced = 0;
                foreach ($sellers as $seller) {
                    $userdetails = UserDetail::where('user_id',$seller->id)->first();
                    // if(!empty($userdetails) && $userdetails->autobid_pause == 0){
                    if (!empty($userdetails) && $userdetails->autobid_pause == 0 && $userdetails->is_autobid == 1 && !in_array($seller->id, $sellersWith3Autobids)) 
                    {
                        $alreadyBid = RecommendedLead::where([
                            ['lead_id', $lead->id],
                            ['buyer_id', $lead->customer_id],
                            ['seller_id', $seller->id],
                        ])->exists();
            
                        if (!$alreadyBid) {
                            // Deduct credit (only if buyer has enough)
                            $bidAmount = $seller->bid ?? $lead->credit_score ?? 0;
                            $detail = $bidAmount . " credit deducted for Autobid";
                            $user = DB::table('users')->where('id', $seller->id)->first();
                            if ($user && $user->total_credit >= $bidAmount) {
                                DB::table('users')->where('id', $seller->id)->decrement('total_credit', $bidAmount);
                                CustomHelper::createTrasactionLog($seller->id, 0, $bidAmount, $detail, 0, 1, $error_response='');
            
                                RecommendedLead::create([
                                    'lead_id'     => $lead->id,
                                    'buyer_id'    => $lead->customer_id,
                                    'seller_id'   => $seller->id,
                                    'service_id'  => $seller->service_id,
                                    'bid'         => $bidAmount,
                                    'distance'    => $seller->distance ?? 0,
                                    'purchase_type' => "Autobid"
                                ]);
                                
            
                                $autoBidLeads[] = [
                                    'lead_id'   => $lead->id,
                                    'seller_id' => $seller->id,
                                ];

                                if(empty($isDataExists)){
                                    LeadStatus::create([
                                        'lead_id' => $lead->id,
                                        'user_id' => $lead->customer_id,
                                        'status' => 'pending',
                                        'clicked_from' => 2,
                                    ]);  
                                }
                                
                                $bidsPlaced++;
                            }
                        }
                    }
                }
                // Mark autobid processed if any bid was placed or no sellers found
                    if ($bidsPlaced > 0) {
                        LeadRequest::where('id', $lead->id)->update(['should_autobid' => 1,'status'=>'pending']);
                    }
                    
            }
        
        return $autoBidLeads;
    }
    
    public function reactivateAutoBidAfter7Days($sevenDaysAgo)
    {
        
        // Get all sellers whose auto-bid is paused and last updated more than 7 days ago
        $sellersToUnpause = UserDetail::where('autobid_pause', 1)
            ->where('updated_at', '<=', $sevenDaysAgo)
            ->get();

        foreach ($sellersToUnpause as $seller) {
            $seller->update([
                'autobid_pause' => 0
            ]);
        }

        return $sellersToUnpause;
    }

    public function leadCloseAfter2Weeks($twoWeeksAgo){
        $leadsToClose = LeadRequest::where('status', 0)
            ->where('created_at', '<', $twoWeeksAgo)
            ->get();
        $settings = CustomHelper::setting_value("auto_bid_limit", 0);    
        // $settings = Setting::first();  
        foreach ($leadsToClose as $lead) {
            // Count only unique sellers the buyer has bid on
            $selectedSellerCount = RecommendedLead::where('lead_id', $lead->id)
                ->where('buyer_id', $lead->customer_id)
                ->distinct('seller_id') // ensure unique seller count
                ->count('seller_id');

            if ($selectedSellerCount < $settings) {
                $lead->closed_status = 1; // Mark as closed
                $lead->save();
            }
        }
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
