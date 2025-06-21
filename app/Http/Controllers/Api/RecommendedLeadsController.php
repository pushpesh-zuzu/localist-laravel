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
    public function addRecommendedLeads(Request $request)
    {
        $leadId = $request->lead_id;

        // Fetch Lead Request Data
        $leadRequest = DB::table('lead_requests')
            ->where('id', $leadId)
            ->first();

        if (!$leadRequest) {
            return $this->sendError(__('Lead request not found'), 404);
        }

        // Step 1: Create Temporary Table with Nationwide Column
        DB::statement("CREATE TEMPORARY TABLE temp_sellers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            postcode VARCHAR(10),
            total_credit INT,
            distance VARCHAR(255),
            service_id INT,
            buyer_id INT,
            lead_id INT,
            credit_scores INT,
            nation_wide TINYINT(1) DEFAULT 0
        )");
        
        // Step 2: Insert Auto-Bid Sellers into Temporary Table
        // Subquery: Count how many users have each total_credit
        $creditCounts = DB::table('users')
        ->select('total_credit', DB::raw('COUNT(*) as credit_count'))
        ->groupBy('total_credit');

        DB::table('temp_sellers')->insertUsing(
        ['user_id', 'postcode', 'total_credit', 'service_id', 'buyer_id', 'lead_id', 'credit_scores', 'nation_wide'],
        DB::table('user_services as us')
            ->join('users as u', 'us.user_id', '=', 'u.id')
            ->join('user_service_locations as usl', function ($join) {
                $join->on('us.user_id', '=', 'usl.user_id')
                    ->on('us.service_id', '=', 'usl.service_id');
            })
            ->joinSub($creditCounts, 'cc', function ($join) {
                $join->on('u.total_credit', '=', 'cc.total_credit');
            })
            ->where('us.service_id', $leadRequest->service_id)
            ->orderByRaw('cc.credit_count = 1 DESC') // Unique credits come first
            ->orderByDesc('u.total_credit')  
            ->limit(5)       // Then by total_credit descending
            ->select(
                'u.id as user_id',
                'usl.postcode',
                'u.total_credit',
                'us.service_id',
                DB::raw($leadRequest->customer_id . ' as buyer_id'),
                DB::raw($leadId . ' as lead_id'),
                DB::raw($leadRequest->credit_score . ' as credit_scores'),
                'usl.nation_wide'
            )
        );
      

        // Step 3: Fetch Sellers from Temp Table
        $sellers = DB::table('temp_sellers')->get();
        $leadpostcode = $leadRequest->postcode; // Always use lead's actual location

        if ($sellers->isEmpty()) {
            DB::statement("DROP TEMPORARY TABLE IF EXISTS temp_sellers");
            return $this->sendError(__('No auto-bid sellers found'), 404);
        }

        // Step 4: Calculate Distance for Each Seller & Update Table
        foreach ($sellers as $seller) {
            if ($seller->nation_wide == 1) {
                // If seller is nationwide, set distance to infinity
                DB::table('temp_sellers')
                    ->where('user_id', $seller->user_id)
                    ->update(['distance' => 0]);
            } else {
                // Seller has specific postcode, calculate real distance
                $distance = $this->getDistance($leadpostcode, $seller->postcode);
                if ($distance !== "Distance not found") {
                    // $cleanDistance = (float) str_replace([' km', ','], '', $distance);
                    $miles = round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2);
                    DB::table('temp_sellers')
                        ->where('user_id', $seller->user_id)
                        ->update(['distance' => $miles]);
                }
            }
        }

        // Step 5: Fetch Sorted Sellers (By Distance & Highest Credit)
        $sortedSellers = DB::table('temp_sellers')
            ->orderByRaw('CASE WHEN distance IS NULL THEN 1 ELSE 0 END, distance ASC')
            ->orderByDesc('total_credit')
            ->get();

        // Step 6: Insert into `bids` Table
        if (!$sortedSellers->isEmpty()) {
            $insertData = [];
            $existingCombos = [];

            foreach ($sortedSellers as $seller) {
                // Unique key for this combo
                $uniqueKey = $seller->user_id . '-' . $seller->lead_id . '-' . $seller->service_id;

                // Only insert if this combo hasn't been added yet
                if (!isset($existingCombos[$uniqueKey]) && $seller->buyer_id != $seller->user_id) {
                    $insertData[] = [
                        'service_id'   => $seller->service_id,
                        'seller_id'    => $seller->user_id,
                        'buyer_id'     => $seller->buyer_id,
                        'lead_id'      => $seller->lead_id,
                        'bid'          => $seller->credit_scores,
                        'distance'     => $seller->distance,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];

                    $existingCombos[$uniqueKey] = true;

                    // Deduct credit only once per unique seller-lead-service combo
                    DB::table('users')
                        ->where('id', $seller->user_id)
                        ->decrement('total_credit', $seller->credit_scores);
                }
            }

            // foreach ($sortedSellers as $seller) {
            //     if($seller->buyer_id != $seller->user_id){
            //         $insertData[] = [
            //             'service_id'   => $seller->service_id,
            //             'seller_id'    => $seller->user_id,
            //             'buyer_id'     => $seller->buyer_id,
            //             'lead_id'      => $seller->lead_id,
            //             'bid'          => $seller->credit_scores, // Fixed bid amount
            //             'created_at'   => now(),
            //             'updated_at'   => now(),
            //         ];
            //     }
             

            //     // $usersdet = DB::table('users')
            //     //     ->where('id', $seller->user_id)->get();dd($usersdet);
            //     // Deduct 20 credits from seller's total_credit
            //     DB::table('users')
            //         ->where('id', $seller->user_id)
            //         ->decrement('total_credit', $seller->credit_scores);
            // }

            // Bulk Insert Bids
            DB::table('recommended_leads')->insert($insertData);
        }

        // Step 7: Drop Temporary Table
        DB::statement("DROP TEMPORARY TABLE IF EXISTS temp_sellers");

        return $this->sendResponse(__('Bids placed successfully'),[]);
    }

    private function getDistance1($postcode1, $postcode2)
    {
        $encodedPostcode1  = urlencode($postcode1);
        $encodedPostcode2 = urlencode($postcode2);
        $apiKey = "AIzaSyB29PyyFmCsm_nw8ELavLskRzMPd3XEIac"; // Replace with your API key
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$encodedPostcode1}&destinations={$encodedPostcode2}&key={$apiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] == 'OK' && isset($data['rows'][0]['elements'][0]['distance'])) {
            return $data['rows'][0]['elements'][0]['distance']['text']; // Distance in km
        } else {
            return "Distance not found";
        }
    }

    public function getRecommendedLeads(Request $request) 
    {
        $seller_id = $request->user_id; 
        $leadid = $request->lead_id; 
        $settings = Setting::first();  
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

            // Check count
            if ($bids->count() < $settings->total_bid) {
                // Fetch other seller bids on the same lead_id (exclude already recommended sellers)
                $otherBids = RecommendedLead::where('lead_id', $leadid)
                    ->whereNotIn('seller_id', $bids->pluck('seller_id')->toArray())
                    ->orderBy('distance', 'ASC')
                    ->get();

                // Merge both
                $bids = $bids->merge($otherBids);
            }

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

    public function getRecommendedLeads_16_5_25(Request $request)
    {
        $seller_id = $request->user_id; 
        $leadid = $request->lead_id; 
        $settings = Setting::first();  
        $result = [];

        if (!empty($leadid)) {
            // Fetch all matching bids
            $bids = RecommendedLead::where('buyer_id', $seller_id)
                ->where('lead_id', $leadid)
                ->where('distance','!=' ,0)
                ->orderBy('distance','ASC')
                ->get();

                // Check count
            if ($bids->count() < $settings->total_bid) {
                // Fetch other seller bids on the same lead_id (exclude already recommended sellers)
                $otherBids = RecommendedLead::where('lead_id', $leadid)
                    ->whereNotIn('seller_id', $bids->pluck('seller_id')->toArray())
                    // ->where('distance', '!=', 0)
                    ->orderBy('distance', 'ASC')
                    ->get();

                // Merge both
                $bids = $bids->merge($otherBids);
            }

            // Get seller IDs and unique service IDs
            $sellerIds = $bids->pluck('seller_id')->toArray();
            $serviceIds = $bids->pluck('service_id')->unique()->toArray();

            // Get users and categories
            $users = User::whereIn('id', $sellerIds)->get()->keyBy('id'); // index by seller_id
            $services = Category::whereIn('id', $serviceIds)->pluck('name', 'id'); // id => name

            foreach ($bids as $bid) {
                $seller = $users[$bid->seller_id] ?? null;
                if ($seller) {
                    $sellerData = $seller->toArray();
                    $sellerData['service_name'] = $services[$bid->service_id] ?? 'Unknown Service';
                    $sellerData['bid'] = $bid->bid; // Optionally include bid amount
                    $sellerData['distance'] = @$bid->distance;
                    $result[] = $sellerData;
                }
            }
            // $bids->groupBy('distance');
        }

        return $this->sendResponse(__('AutoBid Data'), $result);
    }

    
    public function switchRecommendedLeads(Request $request): JsonResponse
    {
        $user_id = $request->user_id; 
        $autobid = $request->is_autobid;
        $userdetails = UserDetail::where('user_id',$user_id)->first();
        if(isset($userdetails) && $userdetails != ''){
            $userdetails->update(['is_autobid' => $autobid]);
        }else{
            $userdetails = UserDetail::create([
                'user_id'  => $user_id,
                'is_autobid' => $autobid
            ]);
        }
        $data = $userdetails;
        return $this->sendResponse(__('Autobid switched successfully'),$data );   
    }

    public function getSwitchAutobid(Request $request){ 
        $aVals = $request->all();
        $isDataExists = UserDetail::where('user_id',$aVals['user_id'])->first();
        if(!empty($isDataExists)){
            return $this->sendResponse(__('Autobid Switch Data'), [
                'isautobid' => $isDataExists->is_autobid
            ]);
        }      
        return $this->sendError('User not found');                                              
    }

    
    

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
            
            // Step 1: Sort all by total_credit DESC
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

        // Step 2: Preselect user_service_locations using simplified logic
        $rows = DB::table('user_service_locations as usl')
            ->join('users', function ($join) use ($leadCreditScore) {
                $join->on('users.id', '=', 'usl.user_id')
                    ->where('users.form_status', 1);
                    
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
            ->map(function ($r) use($serviceName){
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
            'empty' => false,
            'response' => [
                'service_name' => $serviceName,
                'sellers' => $final,
                'bidcount' => $bidCount,
                'totalbid' => $recommendedCount ?? 0,
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
    

    public function addMultipleManualBid(Request $request)
    {
        $aVals = $request->all();
        $buyerId = $aVals['user_id'];
        $leadId = $aVals['lead_id'];
        $inserted = 0;
        $isDataExists = LeadStatus::where('lead_id', $leadId)->where('status', 'pending')->first();
        $settings = CustomHelper::setting_value("auto_bid_limit", 0);
        $currentCount = RecommendedLead::where('lead_id', $leadId)->count();
        // $settings = Setting::first();

        // Step 1: Insert manual sellers from request first (priority)
        foreach ($aVals['seller_id'] as $index => $sellerId) {
             if ($currentCount >= $settings) {
                break; // Stop if limit reached
            }   
            $alreadyExists = RecommendedLead::where('buyer_id', $buyerId)
                ->where('lead_id', $leadId)
                ->where('seller_id', $sellerId)
                ->exists();

            if (!$alreadyExists) {
                $bidAmount = $aVals['bid'][$index];

                $user = DB::table('users')->where('id', $sellerId)->first();
                if ($user && $user->total_credit >= $bidAmount) {
                    $detail = $bidAmount . " credit deducted for Your Matches";
                    DB::table('users')->where('id', $sellerId)->decrement('total_credit', $bidAmount);
                    CustomHelper::createTrasactionLog($sellerId, 0, $bidAmount, $detail, 0, 1, $error_response='');
                    RecommendedLead::create([
                        'buyer_id' => $buyerId,
                        'lead_id' => $leadId,
                        'seller_id' => $sellerId,
                        'service_id' => $aVals['service_id'][$index],
                        'bid' => $bidAmount,
                        'distance' => $aVals['distance'][$index],
                        'purchase_type' => "Best Matches"
                    ]);
                    $inserted++;
                    $currentCount++; // Track how many have been inserted
                }
            }
        }

        // Step 2: Calculate how many more we need
        $remainingSlots = $settings - $currentCount;

        if ($remainingSlots > 0) {
            // Step 3: Fetch and sort remaining sellers by bid
            $response = $this->getManualLeads($request)->getData();

            if (!empty($response->data[0]->sellers)) {
                $remainingSellers = collect($response->data[0]->sellers)
                    ->reject(function ($seller) use ($buyerId, $leadId) {
                        return RecommendedLead::where('buyer_id', $buyerId)
                            ->where('lead_id', $leadId)
                            ->where('seller_id', $seller->id)
                            ->exists();
                    })
                    ->sortBy('bid') // prioritize lower bids
                    ->take($remainingSlots);

                foreach ($remainingSellers as $seller) {
                    $bidAmount = $seller->bid ?? 0;

                    $user = DB::table('users')->where('id', $seller->id)->first();
                    if ($user && $user->total_credit >= $bidAmount) {
                        $detail = $bidAmount . " credit deducted for Your Matches";
                        DB::table('users')->where('id', $seller->id)->decrement('total_credit', $bidAmount);
                        CustomHelper::createTrasactionLog($seller->id, 0, $bidAmount, $detail, 0, 1, $error_response='');
                        RecommendedLead::create([
                            'buyer_id' => $buyerId,
                            'lead_id' => $leadId,
                            'seller_id' => $seller->id,
                            'service_id' => $seller->service_id,
                            'bid' => $bidAmount,
                            'distance' => $seller->distance ?? 0,
                            'purchase_type' => "Autobid"
                        ]);
                        $inserted++;
                        $currentCount++; // Track how many have been inserted
                    }
                        if ($currentCount >= $settings) {
                            break; // stop if limit reached during auto-bids
                        }
                }
            }
        }

        LeadRequest::where('id', $leadId)->update(['should_autobid' => 1, 'status' => "pending"]);

        if (empty($isDataExists)) {
            LeadStatus::create([
                'lead_id' => $leadId,
                'user_id' => $buyerId,
                'status' => 'pending',
                'clicked_from' => 2,
            ]);
        }

        return $this->sendResponse(__('Bids placed successfully'), [
            'inserted_count' => $inserted,
            'total_now' => RecommendedLead::where('lead_id', $leadId)->count()
        ]);
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
