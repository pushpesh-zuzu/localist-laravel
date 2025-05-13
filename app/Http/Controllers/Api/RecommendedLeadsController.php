<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Models\UserServiceLocation;
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

    public function getManualLeads345(Request $request)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) {
            return $this->sendError(__('No Lead found'), 404);
        }

        $bidCount = RecommendedLead::where('lead_id', $lead->id)->count();
        $settings = Setting::first();  
        $serviceId = $lead->service_id;
        $leadCreditScore = $lead->credit_score;
        $leadPostcode = $lead->postcode;
        $customerId = $lead->customer_id;
        $questions = json_decode($lead->questions, true);
        $serviceName = Category::find($serviceId)->name ?? '';

        $userServices = UserService::where('service_id', $serviceId)
            ->where('auto_bid', 1)
            ->where('user_id', '!=', $customerId)
            ->join('users', 'user_services.user_id', '=', 'users.id')
            ->orderByRaw('CAST(users.total_credit AS UNSIGNED) DESC')
            ->select('user_services.user_id', 'users.total_credit')
            ->get();

        if ($userServices->isEmpty()) {
            return $this->sendResponse(__('No Leads found'), [[
                'service_name' => $serviceName,
                'sellers' => []
            ]]);
        }

        $sortedUserIds = $userServices->pluck('user_id')->toArray();
        $nearbyPostcodes = $this->getNearbyPostcodes($leadPostcode, $serviceId, $sortedUserIds);

        $locationMatchedUsers = UserServiceLocation::whereIn('user_id', $sortedUserIds)
            ->where('service_id', $serviceId)
            ->whereIn('postcode', $nearbyPostcodes)
            ->get()
            ->groupBy('user_id');

        if ($locationMatchedUsers->isEmpty()) {
            return $this->sendResponse(__('No Leads found'), [[
                'service_name' => $serviceName,
                'sellers' => []
            ]]);
        }

        $matchedUserIds = $locationMatchedUsers->keys()->toArray();

        $questionTextToId = ServiceQuestion::whereIn('questions', collect($questions)->pluck('ques')->toArray())
            ->pluck('id', 'questions')
            ->toArray();

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
            ->with(['question' => function ($q) {
                $q->select('id', 'questions as question_text');
            }])
            ->get();

        $scoredUsers = $matchedPreferences->groupBy('user_id')->map(function ($prefs) {
            return $prefs->count();
        });

        $existingBids = RecommendedLead::where('buyer_id', $customerId)
            ->where('lead_id', $lead->id)
            ->pluck('seller_id')
            ->toArray();

        // ✅ Get sellers who already got bids from 3 different buyers this week
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $sellersWith3Bids = RecommendedLead::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->select('seller_id')
            ->groupBy('seller_id')
            ->havingRaw('COUNT(DISTINCT buyer_id) >= 3')
            ->pluck('seller_id')
            ->toArray();

        $finalUsers = $scoredUsers->filter(function ($score) {
            return $score > 0;
        })->keys()->map(function ($userId) use (
            $locationMatchedUsers,
            $leadPostcode,
            $leadCreditScore,
            $scoredUsers,
            $serviceName,
            $serviceId,
            $existingBids,
            $sellersWith3Bids
        ) {
            if (in_array($userId, $existingBids)) {
                return null;
            }

            if (in_array($userId, $sellersWith3Bids)) {
                return null;
            }

            $user = User::where('id', $userId)
                ->whereHas('details', function ($query) {
                    $query->where('is_autobid', 1)->where('autobid_pause', 0);
                })->first();

            if (!$user) return null;

            $userLocation = $locationMatchedUsers[$userId]->first();

            $distance = $this->getDistance($leadPostcode, $userLocation->postcode);
            $miles = $distance !== "Distance not found"
                ? round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2)
                : null;

            if ($miles === 0) return null;

            return array_merge(
                $user->toArray(),
                [
                    'credit_score' => $leadCreditScore,
                    'service_name' => $serviceName,
                    'service_id' => $serviceId,
                    'distance' => $miles,
                    'score' => $scoredUsers[$userId] ?? 0,
                ]
            );
        })->filter()->sortBy('distance')->values();

        if (count($finalUsers) > 0) {
            return $this->sendResponse(__('AutoBid Data'), [[
                'service_name' => $serviceName,
                'baseurl' => url('/') . Storage::url('app/public/images/users'),
                'sellers' => $finalUsers,
                'bidcount' => $bidCount,
                'totalbid' => $settings->total_bid
            ]]);
        } else {
            return $this->sendResponse(__('No Leads found'), [[
                'service_name' => $serviceName,
                'baseurl' => url('/') . Storage::url('app/public/images/users'),
                'sellers' => [],
                'bidcount' => $bidCount,
                'totalbid' => $settings->total_bid
            ]]);
        }
    }

    public function getManualLeads(Request $request)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $result = $this->FullManualLeadsCode($lead, 'asc', true);

        if ($result['empty']) {
            return $this->sendResponse(__('No Leads found'), [$result['response']]);
        }

        return $this->sendResponse(__('AutoBid Data'), [$result['response']]);
    }

    public function sortByLocation(Request $request)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $distanceOrderRaw = $request->distance_order;
        $distanceOrder = strtolower($distanceOrderRaw) === 'farthest to nearest' ? 'desc' : 'asc';

        $result = $this->FullManualLeadsCode($lead, $distanceOrder, true);

        if ($result['empty']) {
            return $this->sendResponse(__('No Leads found'), [$result['response']]);
        }

        return $this->sendResponse(__('AutoBid Data'), [$result['response']]);
    }

    private function FullManualLeadsCode($lead, $distanceOrder = 'asc', $applySellerLimit = false)
    {
        $bidCount = RecommendedLead::where('lead_id', $lead->id)->count();
        $settings = Setting::first();  
        $serviceId = $lead->service_id;
        $leadCreditScore = $lead->credit_score;
        $leadPostcode = $lead->postcode;
        $customerId = $lead->customer_id;
        $questions = json_decode($lead->questions, true);
        $serviceName = Category::find($serviceId)->name ?? '';

        // Step 2: Get users with is_autobid = 1 from user_details, excluding lead's customer
        $userServices = User::where('id', '!=', $customerId)
                        ->whereHas('details', function ($query) {
                            $query->where('is_autobid', 1)->where('autobid_pause', 0);
                        })
                        ->whereIn('id', function ($query) use ($serviceId) {
                            $query->select('user_id')
                                ->from('user_services')
                                ->where('service_id', $serviceId)
                                ->where('auto_bid', 1);
                        })
                        ->orderByRaw('CAST(total_credit AS UNSIGNED) DESC')
                        ->select('id as user_id', 'total_credit')
                        ->get();


        // $userServices = UserService::where('service_id', $serviceId)
        //     ->where('auto_bid', 1)
        //     ->where('user_id', '!=', $customerId)
        //     ->join('users', 'user_services.user_id', '=', 'users.id')
        //     ->orderByRaw('CAST(users.total_credit AS UNSIGNED) DESC')
        //     ->select('user_services.user_id', 'users.total_credit')
        //     ->get();

        if ($userServices->isEmpty()) {
            return [
                'empty' => true,
                'response' => [
                    'service_name' => $serviceName,
                    'sellers' => [],
                    'bidcount' => $bidCount,
                    'totalbid' => $settings->total_bid ?? 0,
                    'baseurl' => url('/') . Storage::url('app/public/images/users')
                ]
            ];
        }

        $sortedUserIds = $userServices->pluck('user_id')->toArray();
        $nearbyPostcodes = $this->getNearbyPostcodes($leadPostcode, $serviceId, $sortedUserIds);

        $locationMatchedUsers = UserServiceLocation::whereIn('user_id', $sortedUserIds)
            ->where('service_id', $serviceId)
            ->whereIn('postcode', $nearbyPostcodes)
            ->get()
            ->groupBy('user_id');

            if ($locationMatchedUsers->isEmpty()) {
                // Fallback to nation_wide = 1 sellers
                $nationWideLocations = UserServiceLocation::whereIn('user_id', $sortedUserIds)
                    ->where('service_id', $serviceId)
                    ->where('nation_wide', 1)
                    ->get()
                    ->groupBy('user_id');
            
                if ($nationWideLocations->isEmpty()) {
                    return [
                        'empty' => true,
                        'response' => [
                            'service_name' => $serviceName,
                            'sellers' => [],
                            'bidcount' => $bidCount,
                            'totalbid' => $settings->total_bid ?? 0,
                            'baseurl' => url('/') . Storage::url('app/public/images/users')
                        ]
                    ];
                }
            
                $locationMatchedUsers = $nationWideLocations;
            }

        $matchedUserIds = $locationMatchedUsers->keys()->toArray();

        $questionTextToId = ServiceQuestion::whereIn('questions', collect($questions)->pluck('ques')->toArray())
            ->pluck('id', 'questions')->toArray();

        $questionFilters = collect($questions)
            ->filter(fn($q) => is_array($q) && isset($q['ques'], $questionTextToId[$q['ques']]))
            ->map(fn($q) => ['question_id' => $questionTextToId[$q['ques']], 'answer' => $q['ans']]);

        $matchedPreferences = LeadPrefrence::whereIn('user_id', $matchedUserIds)
            ->where('service_id', $serviceId)
            ->where(function ($query) use ($questionFilters) {
                foreach ($questionFilters as $filter) {
                    foreach (array_map('trim', explode(',', $filter['answer'])) as $ans) {
                        $query->orWhere(fn($q2) =>
                            $q2->where('question_id', $filter['question_id'])
                                ->where('answers', 'LIKE', '%' . $ans . '%')
                        );
                    }
                }
            })->with(['question:id,questions as question_text'])->get();

        $scoredUsers = $matchedPreferences->groupBy('user_id')->map->count();

        $existingBids = RecommendedLead::where('buyer_id', $customerId)
            ->where('lead_id', $lead->id)
            ->pluck('seller_id')
            ->toArray();

        $sellersWith3Bids = [];
        if ($applySellerLimit) {
            $sellersWith3Bids = RecommendedLead::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->select('seller_id')
                ->groupBy('seller_id')
                ->havingRaw('COUNT(DISTINCT buyer_id) >= 3')
                ->pluck('seller_id')
                ->toArray();
        }

        $finalUsers = $scoredUsers->filter(fn($score) => $score > 0)->keys()->map(function ($userId) use (
            $locationMatchedUsers,
            $leadPostcode,
            $leadCreditScore,
            $scoredUsers,
            $serviceName,
            $serviceId,
            $existingBids,
            $sellersWith3Bids,
            $applySellerLimit
        ) {
            if (in_array($userId, $existingBids)) return null;
            if ($applySellerLimit && in_array($userId, $sellersWith3Bids)) return null;

            $user = User::where('id', $userId)->whereHas('details', function ($query) {
                $query->where('is_autobid', 1)->where('autobid_pause', 0);
            })->first();

            if (!$user) return null;

            $userLocation = $locationMatchedUsers[$userId]->first();
            $distance = $this->getDistance($leadPostcode, $userLocation->postcode);
            $miles = $distance !== "Distance not found" ? round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2) : null;

            if ($miles === 0) return null;

            return array_merge($user->toArray(), [
                'credit_score' => $leadCreditScore,
                'service_name' => $serviceName,
                'service_id' => $serviceId,
                'distance' => $miles,
                'score' => $scoredUsers[$userId] ?? 0,
            ]);
        })->filter();

        $finalUsers = $distanceOrder === 'desc'
            ? $finalUsers->sortByDesc('distance')->values()
            : $finalUsers->sortBy('distance')->values();

        return [
            'empty' => false,
            'response' => [
                'service_name' => $serviceName,
                'sellers' => $finalUsers,
                'bidcount' => $bidCount,
                'totalbid' => $settings->total_bid ?? 0,
                'baseurl' => url('/') . Storage::url('app/public/images/users')
            ]
        ];
    }


    public function getManualLeads_without_3_Seller_condition(Request $request)
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
            ->where('auto_bid', 1)
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
        // $finalUsers = $scoredUsers->filter(function ($score) {
        //     return $score > 0;
        // })->keys()->map(function ($userId) use (
        //     $locationMatchedUsers,
        //     $leadPostcode,
        //     $leadCreditScore,
        //     $scoredUsers,
        //     $serviceName,
        //     $serviceId,
        //     $existingBids
        // ) {
        //     if (in_array($userId, $existingBids)) {
        //         return null; // skip sellers already bid by buyer
        //     }
        //     $user = User::find($userId);
        //     $userLocation = $locationMatchedUsers[$userId]->first(); // Pick first location
    
        //     $distance = $this->getDistance($leadPostcode, $userLocation->postcode);
        //     $miles = $distance !== "Distance not found"
        //         ? round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2)
        //         : null;

        //         //weighting code starts here
        //         // Normalize distance to get a score (0 - 1 scale)
        //         // $maxDistance = 25; // Max miles for the filter
        //         // $distanceScore = $miles !== null ? max(0, 1 - ($miles / $maxDistance)) : 0; // Closer distance = higher score

        //         // Step 11: Calculate credit score (80% weight)
        //         // $unusedCredit = $user->total_credit; // Assuming 'total_credit' is the unused credit value
        //         // $maxCredit = 1000; // Set max credit score for normalization (adjust as needed)
        //         // $creditScore = min(1, $unusedCredit / $maxCredit); // Normalize credit score (0 - 1 scale)

        //         // Step 12: Calculate final score using weighted average
        //         // $finalScore = (0.2 * $distanceScore) + (0.8 * $creditScore);

        //         //weighting code ends here
    
        //         return array_merge(
        //             $user->toArray(),
        //             [
        //                 'credit_score' => $leadCreditScore,
        //                 'service_name' => $serviceName,
        //                 'service_id' => $serviceId,
        //                 'distance' => $miles,
        //                 'score' => $scoredUsers[$userId] ?? 0,
        //                 // 'final_score' => $finalScore,
        //             ]
        //         );
        // })->filter()->sortBy('distance')->values();
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
            // $user = User::find($userId);
            $user = User::where('id', $userId)
            ->whereHas('details', function ($query) {
                $query->where('is_autobid', 1)->where('autobid_pause', 0);
            })->first();

            if (!$user) return null; // Skip if autobid not allowed

        
            $userLocation = $locationMatchedUsers[$userId]->first(); // Pick first location
        
            $distance = $this->getDistance($leadPostcode, $userLocation->postcode);
            $miles = $distance !== "Distance not found"
                ? round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2)
                : null;
        
            // *** Skip if miles is 0 ***
            if ($miles === 0) {
                return null;
            }
        
            return array_merge(
                $user->toArray(),
                [
                    'credit_score' => $leadCreditScore,
                    'service_name' => $serviceName,
                    'service_id' => $serviceId,
                    'distance' => $miles,
                    'score' => $scoredUsers[$userId] ?? 0,
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
        $apiKey = "AIzaSyDwAeV7juA_VpzLHqmKXACBtcZxR52TwoE"; // Replace with your API key

        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$encodedPostcode1}&destinations={$encodedPostcode2}&key={$apiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] == 'OK' && isset($data['rows'][0]['elements'][0]['distance'])) {
            return $data['rows'][0]['elements'][0]['distance']['text']; // e.g., "12.5 km"
        } else {
            return "Distance not found";
        }
    }

    public function sortByLocation345(Request $request)
    {
        $distanceOrderRaw  = $request->distance_order; 
        $distanceOrder = strtolower($distanceOrderRaw) === 'farthest to nearest' ? 'desc' : 'asc';
        // Step 1: Get lead info
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) {
            return $this->sendError(__('No Lead found'), 404);
        }

        $bidCount = RecommendedLead::where('lead_id', $lead->id)->get()->count();
    
        $serviceId = $lead->service_id;
        $leadCreditScore = $lead->credit_score;
        $leadPostcode = $lead->postcode;
        $customerId = $lead->customer_id;
        $questions = json_decode($lead->questions, true); // e.g. [{"ques":"...","ans":"..."}]
        $serviceName = Category::find($serviceId)->name ?? '';

        // Step 2: Get auto-bid user_services excluding the lead's customer
        $userServices = UserService::where('service_id', $serviceId)
            ->where('auto_bid', 1)
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
            // $user = User::find($userId);
            $user = User::where('id', $userId)
            ->whereHas('details', function ($query) {
                $query->where('is_autobid', 1)->where('autobid_pause', 0);
            })->first();

            if (!$user) return null; // Skip if autobid not allowed

        
            $userLocation = $locationMatchedUsers[$userId]->first(); // Pick first location
        
            $distance = $this->getDistance($leadPostcode, $userLocation->postcode);
            $miles = $distance !== "Distance not found"
                ? round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2)
                : null;
        
            // *** Skip if miles is 0 ***
            if ($miles === 0) {
                return null;
            }
        
            return array_merge(
                $user->toArray(),
                [
                    'credit_score' => $leadCreditScore,
                    'service_name' => $serviceName,
                    'service_id' => $serviceId,
                    'distance' => $miles,
                    'score' => $scoredUsers[$userId] ?? 0,
                ]
            );
        })->filter()->when($distanceOrder === 'desc', function ($collection) {
                    return $collection->sortByDesc('distance');
                }, function ($collection) {
                    return $collection->sortBy('distance');
                })->values();
       
        if(count($finalUsers)>0){
             return $this->sendResponse(__('AutoBid Data'), [
                [
                    'service_name' => $serviceName,
                    'baseurl' => url('/').Storage::url('app/public/images/users'),
                    'sellers' => $finalUsers,
                    'bidcount' => $bidCount
                ]
            ]);
            return $this->sendResponse(__('Location Data'), $finalUsers);
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

    public function addManualBid(Request $request){
        $aVals = $request->all();
        if(!isset($aVals['bidtype']) || empty($aVals['bidtype'])){
            return $this->sendError(__('Lead request not found'), 404);
        }
        
        $isDataExists = LeadStatus::where('lead_id',$aVals['lead_id'])->where('status','pending')->first();
        $leadtime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();
       
        $settings = Setting::first();  
        if($aVals['bidtype'] == 'reply'){
            $bidCheck = RecommendedLead::where('lead_id', $aVals['lead_id'])
                                       ->where('service_id', $aVals['service_id'])
                                       ->where('buyer_id', $aVals['user_id'])
                                       ->where('seller_id',$aVals['seller_id'])
                                       ->first();
            $bidCount = RecommendedLead::where('lead_id', $aVals['lead_id'])
                           ->where('service_id', $aVals['service_id'])
                           ->count();
            $isActivityExists = self::getActivityLog($aVals['user_id'],$aVals['seller_id'],$aVals['lead_id'],"Requested a callback");
            // ActivityLog::where('lead_id',$aVals['lead_id'])
            //                               ->where('from_user_id',$aVals['user_id']) 
            //                               ->where('to_user_id',$aVals['seller_id']) 
            //                               ->where('activity_name',"Requested a callback") 
            //                               ->first(); 
            if($bidCount==$settings->total_bid){
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
                'purchase_type' => "Request Reply"
            ]); 
            if(empty($isDataExists)){
                LeadStatus::create([
                   'lead_id' => $aVals['lead_id'],
                    'user_id' => $aVals['user_id'],
                    'status' => 'pending',
                    'clicked_from' => 2,
                ]);  
            }   
            if(empty($isActivityExists)){
                self::addActivityLog($aVals['user_id'],$aVals['seller_id'],$aVals['lead_id'],"Requested a callback", "Request Reply", $leadtime);
                // ActivityLog::create([
                //      'lead_id' => $aVals['lead_id'],
                //      'from_user_id' => $aVals['user_id'],
                //      'to_user_id' => $aVals['seller_id'],
                //      'activity_name' => "Requested a callback",
                //  ]);  
            }
            DB::table('users')->where('id', $aVals['seller_id'])->decrement('total_credit', $aVals['bid']);
        }
        if($aVals['bidtype'] == 'purchase_leads'){
            $bidsdata = RecommendedLead::where('lead_id', $aVals['lead_id'])
                                       ->where('service_id', $aVals['service_id'])
                                       ->where('seller_id', $aVals['user_id'])
                                       ->where('buyer_id',$aVals['buyer_id'])
                                       ->first();
            $sellers = User::where('id',$aVals['user_id'])->pluck('name')->first();
            $buyer = User::where('id',$aVals['buyer_id'])->pluck('name')->first();
            $activityname = $sellers .' Contacted '. $buyer;
            $bidCount = RecommendedLead::where('lead_id', $aVals['lead_id'])
                           ->where('service_id', $aVals['service_id'])
                           ->count();
            $isActivityExists = self::getActivityLog($aVals['user_id'],$aVals['buyer_id'],$aVals['lead_id'],$activityname);
            // ActivityLog::where('lead_id',$aVals['lead_id'])
            //                               ->where('from_user_id',$aVals['user_id']) 
            //                               ->where('to_user_id',$aVals['buyer_id']) 
            //                               ->where('activity_name',"Seller Contacted Buyer") 
            //                               ->first(); 
            if($bidCount==$settings->total_bid){
                return $this->sendError(__('Bid Limit exceed'), 404);
            }
            if(!empty($bidCheck)){
                return $this->sendError(__('Bid already placed for this Buyer'), 404);
            }
            $bids = RecommendedLead::create([
                'service_id' => $aVals['service_id'], 
                'seller_id' => $aVals['user_id'], //seller
                'buyer_id' => $aVals['buyer_id'], 
                'lead_id' => $aVals['lead_id'], 
                'bid' => $aVals['bid'], 
                'distance' => $aVals['distance'],
                'purchase_type' => "Manual Bid" 
            ]); 
            SaveForLater::where('seller_id',$aVals['user_id'])
                        ->where('user_id',$aVals['buyer_id'])  
                        ->where('lead_id',$aVals['lead_id'])
                        ->delete();

            if(empty($isDataExists)){
                LeadStatus::create([
                    'lead_id' => $aVals['lead_id'],
                    'user_id' => $aVals['user_id'],
                    'status' => 'pending',
                    'clicked_from' => 1,
                ]);  
            }          
            if(empty($isActivityExists)){
                self::addActivityLog($aVals['user_id'], $aVals['buyer_id'], $aVals['lead_id'], $activityname, "Manual Bid", $leadtime);
                // ActivityLog::create([
                //      'lead_id' => $aVals['lead_id'],
                //      'from_user_id' => $aVals['user_id'],
                //      'to_user_id' => $aVals['buyer_id'],
                //      'activity_name' => "Contacted Buyer",
                //  ]);  
            }
            DB::table('users')->where('id', $aVals['user_id'])->decrement('total_credit', $aVals['bid']);
        }
       
        // DB::table('users')->where('id', $aVals['user_id'])->decrement('total_credit', $aVals['bid']);
        return $this->sendResponse(__('Bids inserted successfully'),[]);
    }
    
    
    public function addActivityLog($from_user_id, $to_user_id, $lead_id, $activity_name, $contact_type, $leadtime){
        $activity = ActivityLog::create([
                     'lead_id' => $lead_id,
                     'from_user_id' => $from_user_id,
                     'to_user_id' => $to_user_id,
                     'activity_name' => $activity_name,
                     'contact_type' => $contact_type,
                 ]);  
        // Calculate duration in hours (minimum 1 hour)
        $createdAt = $activity->created_at;
        $durationInHours = round(max(1, $createdAt->diffInMinutes($leadtime) / 60), 2);

        // Update the activity with duration
        $activity->duration = $durationInHours;
        $activity->save();    
          
        return $activity;                                 
    }

    public function addMultipleManualBid(Request $request)
    {
        $aVals = $request->all();
        $buyerId = $aVals['user_id'];
        $leadId = $aVals['lead_id'];
        $inserted = 0;
        $isDataExists = LeadStatus::where('lead_id', $leadId)->where('status', 'pending')->first();
        $settings = Setting::first();

        // Step 1: Insert manual sellers from request first (priority)
        foreach ($aVals['seller_id'] as $index => $sellerId) {
            $alreadyExists = RecommendedLead::where('buyer_id', $buyerId)
                ->where('lead_id', $leadId)
                ->where('seller_id', $sellerId)
                ->exists();

            if (!$alreadyExists) {
                $bidAmount = $aVals['bid'][$index];

                $user = DB::table('users')->where('id', $sellerId)->first();
                if ($user && $user->total_credit >= $bidAmount) {
                    DB::table('users')->where('id', $sellerId)->decrement('total_credit', $bidAmount);

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
                }
            }
        }

        // Step 2: Calculate how many more we need
        $currentCount = RecommendedLead::where('lead_id', $leadId)->count();
        $remainingSlots = $settings->total_bid - $currentCount;

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

                    $user = DB::table('users')->where('id', $buyerId)->first();
                    if ($user && $user->total_credit >= $bidAmount) {
                        DB::table('users')->where('id', $seller->id)->decrement('total_credit', $bidAmount);

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
                    }
                }
            }
        }

        LeadRequest::where('id', $leadId)->update(['should_autobid' => 1]);

        if (empty($isDataExists)) {
            LeadStatus::create([
                'lead_id' => $leadId,
                'user_id' => $buyerId,
                'status' => 'pending',
                'clicked_from' => 2,
            ]);
        }

        return $this->sendResponse(__('Bids inserted successfully'), [
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
        $leads = LeadRequest::where('closed_status', 0)
                ->where('should_autobid', 0)
                ->where('created_at', '<=', $fiveMinutesAgo)
                ->get();
        $settings = Setting::first();  
        $autoBidLeads = [];
            
            foreach ($leads as $lead) {
                $isDataExists = LeadStatus::where('lead_id',$lead->id)->where('status','pending')->first();
                $existingBids = RecommendedLead::where('lead_id', $lead->id)->count();
        
                if ($existingBids >= $settings->total_bid) {
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
        
                $sellers = collect($manualLeadsResponse->data[0]->sellers)->take($settings->total_bid - $existingBids);
                $bidsPlaced = 0;
                foreach ($sellers as $seller) {
                    $userdetails = UserDetail::where('user_id',$seller->id)->first();
                    if(!empty($userdetails) && $userdetails->autobid_pause == 0){
                        $alreadyBid = RecommendedLead::where([
                            ['lead_id', $lead->id],
                            ['buyer_id', $lead->customer_id],
                            ['seller_id', $seller->id],
                        ])->exists();
            
                        if (!$alreadyBid) {
                            // Deduct credit (only if buyer has enough)
                            $bidAmount = $seller->bid ?? $lead->credit_score ?? 0;
            
                            // $user = DB::table('users')->where('id', $lead->customer_id)->first();
                            // if ($user && $user->total_credit >= $bidAmount) {
                                DB::table('users')->where('id', $lead->customer_id)->decrement('total_credit', $bidAmount);
            
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
                            // }
                        }
                    }
                }
                // Mark autobid processed if any bid was placed or no sellers found
                    if ($bidsPlaced > 0) {
                        LeadRequest::where('id', $lead->id)->update(['should_autobid' => 1]);
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
        // return response()->json([
        //     'message' => 'Auto-bid unpaused for sellers paused more than 7 days ago.',
        //     'total_updated' => count($sellersToUnpause)
        // ]);
    }

    public function autoBidLeadsAfter5Min_old($fiveMinutesAgo)
    {
        $leads = LeadRequest::where('closed_status', 0)
        ->where('created_at', '<=', $fiveMinutesAgo)
        ->get();

        $autoBidLeads = [];

        foreach ($leads as $lead) {
            $existingBids = RecommendedLead::where('lead_id', $lead->id)->count();
    
            if ($existingBids >= 5) {
                continue; // Skip if already has 5 bids
            }
    
            // Use your custom function to get top sellers
            $manualLeads = $this->getTop5SellersWithDistance($lead->id);
    
            // If response is structured as data[0]->sellers, extract sellers
            $sellers = collect($manualLeads)->take(5 - $existingBids);

    
            foreach ($sellers as $seller) {
                $alreadyBid = RecommendedLead::where([
                    ['lead_id', $lead->id],
                    ['buyer_id', $lead->customer_id],
                    ['seller_id', $seller['user']->id],
                ])->exists();
    
                if (!$alreadyBid) {
                    RecommendedLead::create([
                        'lead_id'     => $lead->id,
                        'buyer_id'    => $lead->customer_id,
                        'seller_id'   => $seller['user']->id,
                        'service_id'  => $seller['service_id'],
                        'bid'         => $lead->credit_score, // Or default auto-bid value
                        'distance'    => $seller['distance'] ?? null
                    ]);
    
                    $autoBidLeads[] = [
                        'lead_id'   => $lead->id,
                        'seller_id' => $seller['user']->id,
                    ];
                }
            }
        }
            return $autoBidLeads;
        // 
    }

    public function leadCloseAfter2Weeks($twoWeeksAgo){
        $leadsToClose = LeadRequest::where('status', 0)
            ->where('created_at', '<', $twoWeeksAgo)
            ->get();
        $settings = Setting::first();  
        foreach ($leadsToClose as $lead) {
            // Count only unique sellers the buyer has bid on
            $selectedSellerCount = RecommendedLead::where('lead_id', $lead->id)
                ->where('buyer_id', $lead->customer_id)
                ->distinct('seller_id') // ensure unique seller count
                ->count('seller_id');

            if ($selectedSellerCount < $settings->total_bid) {
                $lead->closed_status = 1; // Mark as closed
                $lead->save();
            }
        }
    }

    public function getTop5SellersWithDistance($leadId)
    {
        $lead = LeadRequest::find($leadId);
        if (!$lead) return [];

        $serviceId = $lead->service_id;
        $leadPostcode = $lead->postcode;
        $customerId = $lead->customer_id;

        // Step 1: Get sellers (exclude buyer)
        $userServices = UserService::where('service_id', $serviceId)
            ->where('user_id', '!=', $customerId)
            ->join('users', 'user_services.user_id', '=', 'users.id')
            ->orderByRaw('CAST(users.total_credit AS UNSIGNED) DESC')
            ->select('user_services.user_id', 'users.total_credit')
            ->get();

        $sortedUserIds = $userServices->pluck('user_id')->toArray();

        // Step 2: Get nearby postcodes
        $nearbyPostcodes = $this->getNearbyPostcodes($leadPostcode, $serviceId, $sortedUserIds);

        // Step 3: Filter by location
        $locationMatchedUsers = UserServiceLocation::whereIn('user_id', $sortedUserIds)
            ->where('service_id', $serviceId)
            ->whereIn('postcode', $nearbyPostcodes)
            ->get()
            ->groupBy('user_id');

        if ($locationMatchedUsers->isEmpty()) return [];

        // Step 4: Get distance + seller info
        $existingBids = RecommendedLead::where('buyer_id', $customerId)
            ->where('lead_id', $lead->id)
            ->pluck('seller_id')
            ->toArray();

        $finalSellers = collect($locationMatchedUsers)->map(function ($locations, $userId) use (
            $leadPostcode, $existingBids, $serviceId
        ) {
            if (in_array($userId, $existingBids)) return null;
            // $user = User::find($userId);
            $user = User::where('id', $userId)
            ->whereHas('details', function ($query) {
                $query->where('is_autobid', 1)->where('autobid_pause', 0);
            })->first();

        if (!$user) return null; // Skip if autobid not allowed

            $userLocation = $locations->first();
            $distance = $this->getDistance($leadPostcode, $userLocation->postcode);
            $miles = $distance !== "Distance not found"
                ? round(((float) str_replace([' km', ','], '', $distance)) * 0.621371, 2)
                : null;

            if ($miles === 0 || is_null($miles)) return null;

            return [
                'user' => $user,
                'distance' => $miles,
                'service_id' => $serviceId,
            ];
        })->filter()->sortBy('distance')->take(5)->values();

        return $finalSellers;
    }

    public function buyerViewProfile(Request $request)
    {
        $aVals = $request->all();
        $leadtime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();
        $sellers = User::where('id',$aVals['seller_id'])->pluck('name')->first();
        $buyer = User::where('id',$aVals['user_id'])->pluck('name')->first();
        $activityname = $buyer .' viewed '. $sellers .' profile';
        $isActivity = self::getActivityLog($aVals['user_id'], $aVals['seller_id'], $aVals['lead_id'], $activityname);
        if(empty($isActivity)){
            self::addActivityLog($aVals['user_id'], $aVals['seller_id'], $aVals['lead_id'], $activityname, "Buyer viewed Seller Profile", $leadtime);
        }
        return $this->sendResponse(__('Viewed your Profile'),[]);                      
    }

    public function buyerActivities(Request $request)
    {
        $aVals = $request->all();
        $isActivity = ActivityLog::where('from_user_id', $aVals['user_id']) 
                                 ->whereIn('to_user_id', [$aVals['buyer_id']]) 
                                 ->where('lead_id', $aVals['lead_id']) 
                                 ->get(); 
        return $this->sendResponse(__('Activity log'),$isActivity);     
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

}   
