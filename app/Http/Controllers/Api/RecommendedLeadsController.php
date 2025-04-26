<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Models\UserServiceLocation;
use App\Models\LeadPrefrence;
use App\Models\SaveForLater;
use App\Models\ServiceQuestion;
use App\Models\LeadRequest;
use App\Models\UserService;
use App\Models\UserDetail;
use App\Models\Category;
use App\Models\User;
use App\Models\RecommendedLead;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;

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

        return $this->sendResponse(__('Bids inserted successfully'),[]);
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
        $result = [];

        if (!empty($leadid)) {
            // Fetch all matching bids
            $bids = RecommendedLead::where('buyer_id', $seller_id)
                ->where('lead_id', $leadid)
                ->orderBy('distance','ASC')
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

    public function autobid123(Request $request)
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
            distance FLOAT DEFAULT NULL,
            service_id INT,
            buyer_id INT,
            lead_id INT,
            credit_scores INT,
            nation_wide TINYINT(1) DEFAULT 0
        )");
        
        // Step 2: Insert Auto-Bid Sellers into Temporary Table
        DB::table('temp_sellers')->insertUsing(
            ['user_id', 'postcode', 'total_credit', 'service_id', 'buyer_id', 'lead_id','credit_scores', 'nation_wide'],
            DB::table('user_services AS us')
                ->join('user_service_locations AS usl', 'us.user_id', '=', 'usl.user_id')
                ->join('users AS u', 'u.id', '=', 'us.user_id')
                ->select([
                    'us.user_id',
                    DB::raw("CASE WHEN usl.postcode IS NULL OR usl.postcode = '' THEN '000000' ELSE usl.postcode END AS postcode"),
                    'u.total_credit',
                    DB::raw($leadRequest->service_id . ' AS service_id'),
                    DB::raw($leadRequest->customer_id . ' AS buyer_id'),
                    DB::raw($leadId . ' AS lead_id'),
                    DB::raw($leadRequest->credit_score  . ' AS credit_scores'),
                    'usl.nation_wide' // Fetch nationwide from user_service_locations table
                ])
                ->where('us.service_id', $leadRequest->service_id)
                ->where('us.auto_bid', 1)
                ->where('usl.status', 1)
                ->where('u.total_credit', '>=', 20)
                ->limit(5)
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
                    ->update(['distance' => INF]);
            } else {
                // Seller has specific postcode, calculate real distance
                $distance = $this->getDistance($leadpostcode, $seller->postcode);
                if ($distance !== "Distance not found") {
                    $cleanDistance = (float) str_replace([' km', ','], '', $distance);
                    DB::table('temp_sellers')
                        ->where('user_id', $seller->user_id)
                        ->update(['distance' => $cleanDistance]);
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
            DB::table('bids')->insert($insertData);
        }

        // Step 7: Drop Temporary Table
        DB::statement("DROP TEMPORARY TABLE IF EXISTS temp_sellers");

        return $this->sendResponse(__('Bids inserted successfully'),[]);
    }

    // public function getManualLeads(Request $request)
    // {
    //     $leadId = $request->lead_id;

    //     // Fetch Lead Request Data
    //     $leadRequest = DB::table('lead_requests')
    //         ->where('id', $leadId)
    //         ->first();

    //     if (!$leadRequest) {
    //         return $this->sendError(__('Lead request not found'), 404);
    //     }
    // }

    // public function getManualLeads(Request $request)
    // {
    //     // Step 1: Get lead info
    //     $lead = LeadRequest::find($request->lead_id);
    //     if (!$lead) return [];

    //     $serviceId = $lead->service_id;
    //     $leadPostcode = $lead->postcode;
    //     $customerId = $lead->customer_id;
    //     $questions = json_decode($lead->questions, true); // assuming JSON format

    //     // Step 2: Get auto-bid user_services excluding the lead's customer
    //     $userServices = UserService::where('service_id', $serviceId)
    //         ->where('auto_bid', 1)
    //         ->where('user_id', '!=', $customerId)
    //         ->join('users', 'user_services.user_id', '=', 'users.id')
    //         ->orderByRaw('CAST(users.total_credit AS UNSIGNED) DESC')
    //         ->select('user_services.user_id', 'users.total_credit')
    //         ->get();

    //     if ($userServices->isEmpty()) return [];

    //     // Step 3: Get list of user_ids ordered by total_credit
    //     $sortedUserIds = $userServices->pluck('user_id')->toArray();

    //     // Step 4: Get nearby postcodes
    //     // $nearbyPostcodes = getNearbyPostcodes($leadPostcode); // Implement this helper
    //     $nearbyPostcodes = self::getNearbyPostcodes($leadPostcode, $serviceId, $sortedUserIds);
    //     // Step 5: Filter users by service location match
    //     $locationMatchedUsers = UserServiceLocation::whereIn('user_id', $sortedUserIds)
    //         ->where('service_id', $serviceId)
    //         ->whereIn('postcode', $nearbyPostcodes)
    //         ->pluck('user_id')
    //         ->unique()
    //         ->toArray();

    //     if (empty($locationMatchedUsers)) return [];

    //     // Step 6: Match preferences with lead questions
    //     $matchedPreferences = LeadPrefrence::whereIn('user_id', $locationMatchedUsers)
    //         ->where('service_id', $serviceId)
    //         ->where(function($query) use ($questions) {
    //             foreach ($questions as $questionId => $answer) {
    //                 $query->orWhere(function ($q) use ($questionId, $answer) {
    //                     $q->where('question_id', $questionId)
    //                     ->where('answers', $answer);
    //                 });
    //             }
    //         })->get();

    //     // Step 7: Score users based on preference match count
    //     $scoredUsers = $matchedPreferences->groupBy('user_id')->map(function ($prefs) {
    //         return $prefs->count();
    //     });

    //     // Step 8: Sort users by preference score, then by total_credit as fallback
    //     $finalUsers = collect($sortedUserIds)->mapWithKeys(function ($userId) use ($scoredUsers) {
    //         return [$userId => $scoredUsers[$userId] ?? 0];
    //     })->sortDesc();

    //     return $finalUsers;
    // }

    // public function getNearbyPostcodes($leadPostcode, $serviceId, $sortedUserIds, $maxMiles = 25)
    // {
    //     $sellerLocations = UserServiceLocation::whereIn('user_id', $sortedUserIds)
    //                                             ->where('service_id', $serviceId)
    //                                             ->whereNotNull('postcode')
    //                                             ->get();

    //     $nearbyPostcodes = [];

    //     foreach ($sellerLocations as $location) {
    //         $distance = self::getDistance($leadPostcode, $location->postcode);
            
    //         if ($distance !== "Distance not found") {
    //             $miles = round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2);

    //             if ($miles <= $maxMiles) {
    //                 $nearbyPostcodes[] = $location->postcode;
    //             }
    //         }
    //     }

    //     return array_unique($nearbyPostcodes);
    // }
    // function getDistance($postcode1, $postcode2)
    // {
    //     $encodedPostcode1 = urlencode($postcode1);
    //     $encodedPostcode2 = urlencode($postcode2);
    //     $apiKey = "AIzaSyB29PyyFmCsm_nw8ELavLskRzMPd3XEIac"; // Replace with your API key

    //     $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$encodedPostcode1}&destinations={$encodedPostcode2}&key={$apiKey}";

    //     $response = file_get_contents($url);
    //     $data = json_decode($response, true);

    //     if ($data['status'] == 'OK' && isset($data['rows'][0]['elements'][0]['distance'])) {
    //         return $data['rows'][0]['elements'][0]['distance']['text']; // e.g., "12.5 km"
    //     } else {
    //         return "Distance not found";
    //     }
    // }
   
    // public function getManualLeads(Request $request)
    // {
    //     // Step 1: Get lead info
    //     $lead = LeadRequest::find($request->lead_id);
    //     if (!$lead) {
    //         return $this->sendError(__('No Lead found'), 404);
    //     }
    //     $bidCount = RecommendedLead::where('buyer_id', $lead->customer_id)
    //     ->where('lead_id', $lead->id)
    //     ->get()->count();
        
    
    //     $serviceId = $lead->service_id;
    //     $leadCreditScore = $lead->credit_score;
    //     $leadPostcode = $lead->postcode;
    //     $customerId = $lead->customer_id;
    //     $questions = json_decode($lead->questions, true); // e.g. [{"ques":"...","ans":"..."}]
    //     $serviceName = Category::find($serviceId)->name ?? '';

    //     // Step 2: Get auto-bid user_services excluding the lead's customer
    //     $userServices = UserService::where('service_id', $serviceId)
    //         ->where('auto_bid', 1)
    //         ->where('user_id', '!=', $customerId)
    //         ->join('users', 'user_services.user_id', '=', 'users.id')
    //         ->orderByRaw('CAST(users.total_credit AS UNSIGNED) DESC')
    //         ->select('user_services.user_id', 'users.total_credit')
    //         ->get();
            
    //     if ($userServices->isEmpty()) {
    //         return $this->sendResponse(__('No Leads found'), [
    //             [
    //                 'service_name' => $serviceName,
    //                 'sellers' => []
    //             ]
    //         ]);
    //     }
       
    
    //     $sortedUserIds = $userServices->pluck('user_id')->toArray();
    
    //     // Step 3: Get nearby postcodes
    //     $nearbyPostcodes = $this->getNearbyPostcodes($leadPostcode, $serviceId, $sortedUserIds);
    
    //     // Step 4: Get users with matching service locations
    //     $locationMatchedUsers = UserServiceLocation::whereIn('user_id', $sortedUserIds)
    //         ->where('service_id', $serviceId)
    //         ->whereIn('postcode', $nearbyPostcodes)
    //         ->get()
    //         ->groupBy('user_id');
    
    //     if ($locationMatchedUsers->isEmpty()) {
    //         return $this->sendResponse(__('No Leads found'), [
    //             [
    //                 'service_name' => $serviceName,
    //                 'sellers' => []
    //             ]
    //         ]);
    //     }
    
    //     $matchedUserIds = $locationMatchedUsers->keys()->toArray();

    //     // Step 5: Get question text → ID map
    //     $questionTextToId = ServiceQuestion::whereIn('questions', collect($questions)->pluck('ques')->toArray())
    //     ->pluck('id', 'questions')
    //     ->toArray();

    //     // Step 6: Replace question text in $questions array with their IDs
    //     $questionFilters = collect($questions)
    //     ->filter(function ($q) use ($questionTextToId) {
    //         return is_array($q) && isset($q['ques']) && isset($questionTextToId[$q['ques']]);
    //     })
    //     ->map(function ($q) use ($questionTextToId) {
    //         return [
    //             'question_id' => $questionTextToId[$q['ques']],
    //             'answer' => $q['ans'],
    //         ];
    //     });
    
    //     // Step 7: Match preferences and include question_text
    //     $leadQuestionCount = $questionFilters->count();

    //     // Group preferences by user and filter full matches only
    //     $matchedPreferences = LeadPrefrence::whereIn('user_id', $matchedUserIds)
    //         ->where('service_id', $serviceId)
    //         ->get()
    //         ->groupBy('user_id')
    //         ->filter(function ($prefs, $userId) use ($questionFilters, $leadQuestionCount) {
    //             $matchCount = 0;

    //             foreach ($questionFilters as $filter) {
    //                 $answers = explode(',', $filter['answer']);
    //                 $found = $prefs->first(function ($pref) use ($filter, $answers) {
    //                     return $pref->question_id == $filter['question_id'] &&
    //                         in_array(trim($pref->answers), array_map('trim', $answers));
    //                 });

    //                 if ($found) {
    //                     $matchCount++;
    //                 }
    //             }

    //             return $matchCount === $leadQuestionCount;
    //         });
    
    //     // Step 8: Score users
    //     $scoredUsers = $matchedPreferences->mapWithKeys(function ($prefs, $userId) {
    //         return [$userId => $prefs->count()];
    //     });
    
    //     $existingBids = RecommendedLead::where('buyer_id', $customerId)
    //                                     ->where('lead_id', $lead->id)
    //                                     ->pluck('seller_id')
    //                                     ->toArray();
    //     // Step 9: Build final list with user info, service name, and distance
    //     $finalUsers = $scoredUsers->filter(function ($score) {
    //         return $score > 0;
    //     })->keys()->map(function ($userId) use (
    //         $locationMatchedUsers,
    //         $leadPostcode,
    //         $leadCreditScore,
    //         $scoredUsers,
    //         $serviceName,
    //         $serviceId,
    //         $existingBids
    //     ) {
    //         if (in_array($userId, $existingBids)) {
    //             return null; // skip sellers already bid by buyer
    //         }
    //         $user = User::find($userId);
    //         $userLocation = $locationMatchedUsers[$userId]->first(); // Pick first location
    
    //         $distance = $this->getDistance($leadPostcode, $userLocation->postcode);
    //         $miles = $distance !== "Distance not found"
    //             ? round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2)
    //             : null;
    
    //             return array_merge(
    //                 $user->toArray(),
    //                 [
    //                     'credit_score' => $leadCreditScore,
    //                     'service_name' => $serviceName,
    //                     'service_id' => $serviceId,
    //                     'distance' => $miles,
    //                     'score' => $scoredUsers[$userId] ?? 0,
    //                 ]
    //             );
    //     })->filter()->sortByDesc('score')->values();
       
       
    //     if(count($finalUsers)>0){
    //          return $this->sendResponse(__('No Leads found'), [
    //             [
    //                 'service_name' => $serviceName,
    //                 'baseurl' => url('/').Storage::url('app/public/images/users'),
    //                 'sellers' => $finalUsers
    //             ]
    //         ]);
    //         return $this->sendResponse(__('AutoBid Data'), $finalUsers);
    //     }else{
    //         return $this->sendResponse(__('No Leads found'), [
    //             [
    //                 'service_name' => $serviceName,
    //                 'baseurl' => url('/').Storage::url('app/public/images/users'),
    //                 'sellers' => []
    //             ]
    //         ]);
    //     }
        
        
    // }
    public function getManualLeads(Request $request)
    {
        // Step 1: Get lead info
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) {
            return $this->sendError(__('No Lead found'), 404);
        }
        // $bidCount = RecommendedLead::where('buyer_id', $lead->customer_id)
        // ->where('lead_id', $lead->id)
        // ->get()->count();
        $bidCount = RecommendedLead::where('lead_id', $lead->id)->get()->count();
    
        $serviceId = $lead->service_id;
        $leadCreditScore = $lead->credit_score;
        $leadPostcode = $lead->postcode;
        $customerId = $lead->customer_id;
        $questions = json_decode($lead->questions, true); // e.g. [{"ques":"...","ans":"..."}]
        $serviceName = Category::find($serviceId)->name ?? '';

        // Step 2: Get auto-bid user_services excluding the lead's customer
        $userServices = UserService::where('service_id', $serviceId)
            // ->where('auto_bid', 1)
            ->where('user_id', '!=', $customerId)
            ->join('users', 'user_services.user_id', '=', 'users.id')
            ->orderByRaw('CAST(users.total_credit AS UNSIGNED) DESC')
            ->select('user_services.user_id', 'users.total_credit')
            ->get();
            
        if ($userServices->isEmpty()) {
            return $this->sendResponse(__('No Leads found'), [
                [
                    'service_name' => $serviceName,
                    'sellers' => []
                ]
            ]);
        }
       
    
        $sortedUserIds = $userServices->pluck('user_id')->toArray();
    
        // Step 3: Get nearby postcodes
        $nearbyPostcodes = $this->getNearbyPostcodes($leadPostcode, $serviceId, $sortedUserIds);
    
        // Step 4: Get users with matching service locations
        $locationMatchedUsers = UserServiceLocation::whereIn('user_id', $sortedUserIds)
            ->where('service_id', $serviceId)
            ->whereIn('postcode', $nearbyPostcodes)
            ->get()
            ->groupBy('user_id');
    
        if ($locationMatchedUsers->isEmpty()) {
            return $this->sendResponse(__('No Leads found'), [
                [
                    'service_name' => $serviceName,
                    'sellers' => []
                ]
            ]);
        }
    
        $matchedUserIds = $locationMatchedUsers->keys()->toArray();

        // Step 5: Get question text → ID map
        $questionTextToId = ServiceQuestion::whereIn('questions', collect($questions)->pluck('ques')->toArray())
        ->pluck('id', 'questions')
        ->toArray();

        // Step 6: Replace question text in $questions array with their IDs
        $questionFilters = collect($questions)
        ->filter(function ($q) use ($questionTextToId) {
            return is_array($q) && isset($q['ques']) && isset($questionTextToId[$q['ques']]);
        })
        ->map(function ($q) use ($questionTextToId) {
            return [
                'question_id' => $questionTextToId[$q['ques']],
                'answer' => $q['ans'],
            ];
        });
        
    
        // Step 7: Match preferences and include question_text
        $matchedPreferences = LeadPrefrence::whereIn('user_id', $matchedUserIds)
            ->where('service_id', $serviceId)
            ->where(function ($query) use ($questionFilters) {
                foreach ($questionFilters as $filter) {
                    $answers = array_map('trim', explode(',', $filter['answer']));
                    
                    foreach ($answers as $ans) {
                        $query->orWhere(function ($q2) use ($filter, $ans) {
                            $q2->where('question_id', $filter['question_id'])
                                ->where('answers', 'LIKE', '%' . $ans . '%');
                            });
                    }
                }
            })
            // ->where(function ($query) use ($questionFilters) {
            //     foreach ($questionFilters as $filter) {
            //         $answers = explode(',', $filter['answer']); // Handle multiple answers from lead
            //         foreach ($answers as $ans) {
            //             $query->orWhere(function ($q2) use ($filter, $ans) {
            //                 $q2->where('question_id', $filter['question_id'])
            //                 ->whereRaw("JSON_TYPE(answers) IS NOT NULL AND JSON_SEARCH(answers, 'one', ?) IS NOT NULL", [trim($ans)]);
            //                         // ->whereRaw("JSON_SEARCH(answers, 'one', ?) IS NOT NULL", [trim($ans)]);
            //                 //   ->where('answers', trim($ans)); // Match individual answer
            //             });
            //         }
            //     }
            // })
            ->with(['question' => function ($q) {
                $q->select('id', 'questions as question_text');
            }])
            ->get();
    
        // Step 8: Score users
        $scoredUsers = $matchedPreferences->groupBy('user_id')->map(function ($prefs) {
            return $prefs->count();
        });
    
        $existingBids = RecommendedLead::where('buyer_id', $customerId)
                                        ->where('lead_id', $lead->id)
                                        ->pluck('seller_id')
                                        ->toArray();
        // Step 9: Build final list with user info, service name, and distance
        $finalUsers = $scoredUsers->filter(function ($score) {
            return $score > 0;
        })->keys()->map(function ($userId) use (
            $locationMatchedUsers,
            $leadPostcode,
            $leadCreditScore,
            $scoredUsers,
            $serviceName,
            $serviceId,
            $existingBids
        ) {
            if (in_array($userId, $existingBids)) {
                return null; // skip sellers already bid by buyer
            }
            $user = User::find($userId);
            $userLocation = $locationMatchedUsers[$userId]->first(); // Pick first location
    
            $distance = $this->getDistance($leadPostcode, $userLocation->postcode);
            $miles = $distance !== "Distance not found"
                ? round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2)
                : null;

                //weighting code starts here
                // Normalize distance to get a score (0 - 1 scale)
                // $maxDistance = 25; // Max miles for the filter
                // $distanceScore = $miles !== null ? max(0, 1 - ($miles / $maxDistance)) : 0; // Closer distance = higher score

                // Step 11: Calculate credit score (80% weight)
                // $unusedCredit = $user->total_credit; // Assuming 'total_credit' is the unused credit value
                // $maxCredit = 1000; // Set max credit score for normalization (adjust as needed)
                // $creditScore = min(1, $unusedCredit / $maxCredit); // Normalize credit score (0 - 1 scale)

                // Step 12: Calculate final score using weighted average
                // $finalScore = (0.2 * $distanceScore) + (0.8 * $creditScore);

                //weighting code ends here
    
                return array_merge(
                    $user->toArray(),
                    [
                        'credit_score' => $leadCreditScore,
                        'service_name' => $serviceName,
                        'service_id' => $serviceId,
                        'distance' => $miles,
                        'score' => $scoredUsers[$userId] ?? 0,
                        // 'final_score' => $finalScore,
                    ]
                );
        })->filter()->sortBy('distance')->values();
       
       
        if(count($finalUsers)>0){
             return $this->sendResponse(__('AutoBid Data'), [
                [
                    'service_name' => $serviceName,
                    'baseurl' => url('/').Storage::url('app/public/images/users'),
                    'sellers' => $finalUsers,
                    'bidcount' => $bidCount
                ]
            ]);
            return $this->sendResponse(__('AutoBid Data'), $finalUsers);
        }else{
            return $this->sendResponse(__('No Leads found'), [
                [
                    'service_name' => $serviceName,
                    'baseurl' => url('/').Storage::url('app/public/images/users'),
                    'sellers' => [],
                    'bidcount' => $bidCount
                ]
            ]);
        }
        
        
    }
    public function getNearbyPostcodes($leadPostcode, $serviceId, $sortedUserIds, $maxMiles = 25)
    {
        $sellerLocations = UserServiceLocation::whereIn('user_id', $sortedUserIds)
            ->where('service_id', $serviceId)
            ->whereNotNull('postcode')
            ->get();

        $nearbyPostcodes = [];

        foreach ($sellerLocations as $location) {
            $distance = $this->getDistance($leadPostcode, $location->postcode);

            if ($distance !== "Distance not found") {
                $miles = round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2);

                if ($miles <= $maxMiles) {
                    $nearbyPostcodes[] = $location->postcode;
                }
            }
        }

        return array_unique($nearbyPostcodes);
    }

    public function getDistance($postcode1, $postcode2)
    {
        $encodedPostcode1 = urlencode($postcode1);
        $encodedPostcode2 = urlencode($postcode2);
        $apiKey = "AIzaSyB29PyyFmCsm_nw8ELavLskRzMPd3XEIac"; // Replace with your API key

        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$encodedPostcode1}&destinations={$encodedPostcode2}&key={$apiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] == 'OK' && isset($data['rows'][0]['elements'][0]['distance'])) {
            return $data['rows'][0]['elements'][0]['distance']['text']; // e.g., "12.5 km"
        } else {
            return "Distance not found";
        }
    }


    public function addManualBid(Request $request){
        $aVals = $request->all();
        if(!isset($aVals['bidtype']) || empty($aVals['bidtype'])){
            return $this->sendError(__('Lead request not found'), 404);
        }
        $bidsdata = RecommendedLead::where('lead_id', $aVals['lead_id'])->where('service_id', $aVals['service_id']);
        if($aVals['bidtype'] == 'reply'){
            $bidsUser = $bidsdata->where('buyer_id', $aVals['user_id']);
            $bidCount = $bidsUser->get()->count();
            $bidCheck = $bidsUser->where('seller_id',$aVals['seller_id'])->first();
            if($bidCount==5){
                return $this->sendError(__('Bid Limit exceed'), 404);
            }
            if(!empty($bidCheck)){
                return $this->sendError(__('Bid already placed for this seller'), 404);
            }
            $bids = RecommendedLead::create([
                'service_id' => $aVals['service_id'], 
                'seller_id' => $aVals['seller_id'], 
                'buyer_id' => $aVals['user_id'], //buyer
                'lead_id' => $aVals['lead_id'], 
                'bid' => $aVals['bid'], 
                'distance' => $aVals['distance'], 
            ]); 
            // DB::table('users')->where('id', $aVals['user_id'])->decrement('total_credit', $aVals['bid']);
        }
        if($aVals['bidtype'] == 'purchase_leads'){
            $bidsUser = $bidsdata->where('seller_id', $aVals['user_id']);
            $bidCount = $bidsUser->get()->count();
            $bidCheck = $bidsUser->where('buyer_id',$aVals['buyer_id'])->first();
            if($bidCount==5){
            return $this->sendError(__('Bid Limit exceed'), 404);
            }
            if(!empty($bidCheck)){
                return $this->sendError(__('Bid already placed for this seller'), 404);
            }
            $bids = RecommendedLead::create([
                'service_id' => $aVals['service_id'], 
                'seller_id' => $aVals['user_id'], //seller
                'buyer_id' => $aVals['buyer_id'], 
                'lead_id' => $aVals['lead_id'], 
                'bid' => $aVals['bid'], 
                'distance' => $aVals['distance'], 
            ]); 
            SaveForLater::where('seller_id',$aVals['user_id'])
                        ->where('user_id',$aVals['buyer_id'])  
                        ->where('lead_id',$aVals['lead_id'])
                        ->delete();
            // DB::table('users')->where('id', $aVals['user_id'])->decrement('total_credit', $aVals['bid']);
        }
        DB::table('users')->where('id', $aVals['user_id'])->decrement('total_credit', $aVals['bid']);
        return $this->sendResponse(__('Bids inserted successfully'),[]);
    }

    public function addMultipleManualBid(Request $request){
        $aVals = $request->all();
        $bidsdata = RecommendedLead::where('lead_id', $aVals['lead_id'])->where('service_id', $aVals['service_id']);
        $sellerIds = $aVals['seller_id']; // array
        $serviceIds = $aVals['service_id']; // array
        $bids = $aVals['bid']; // array
        $distances = $aVals['distance']; // array
        $anyInserted = false;
        foreach ($sellerIds as $index => $sellerId) {
            $serviceId = $serviceIds[$index];
            $bid = $bids[$index];
            $distance = $distances[$index];
    
            $bidsUser = RecommendedLead::where('lead_id', $aVals['lead_id'])
                ->where('service_id', $serviceId)
                ->where('buyer_id', $aVals['user_id']);
    
            $bidCount = $bidsUser->count();
            $bidCheck = $bidsUser->where('seller_id', $sellerId)->first();
    
            if ($bidCount >= 5) {
                return $this->sendError(__('Bid Limit exceeded for service ID ' . $serviceId), 404);
            }
    
            if (!empty($bidCheck)) {
                continue; // Skip if already placed for this seller
            }
    
            RecommendedLead::create([
                'service_id' => $serviceId,
                'seller_id' => $sellerId,
                'buyer_id' => $aVals['user_id'],
                'lead_id' => $aVals['lead_id'],
                'bid' => $bid,
                'distance' => $distance,
            ]);
    
            DB::table('users')->where('id', $aVals['user_id'])->decrement('total_credit', $bid);

            $anyInserted = true; //  Mark that at least one entry was inserted
        }
        if ($anyInserted) {
            return $this->sendResponse(__('Bids inserted successfully'), []);
        } else {
            return $this->sendError(__('Bids already placed for all selected sellers'), 404);
        }  
    } 

    public function closeLeads()
    {
        $twoWeeksAgo = Carbon::now()->subWeeks(2);

        $leadsToClose = LeadRequest::where('status', 0)
            ->where('created_at', '<', $twoWeeksAgo)
            ->get();

        foreach ($leadsToClose as $lead) {
            // Count only unique sellers the buyer has bid on
            $selectedSellerCount = RecommendedLead::where('lead_id', $lead->id)
                ->where('buyer_id', $lead->customer_id)
                ->distinct('seller_id') // ensure unique seller count
                ->count('seller_id');

            if ($selectedSellerCount < 5) {
                $lead->closed_status = 1; // Mark as closed
                $lead->save();
            }
        }
        
        
        // $leadsToClose = LeadRequest::where('id', 249)->update(['closed_status'=>1]);

        return response()->json(['message' => 'Leads closed successfully.']);
    }

}
