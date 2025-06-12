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

        $result = $this->FullManualLeadsCode($lead, 'asc', true, $responseTimeFilter, $ratingFilter);

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
        $responseTimeFilter = $request->responseTimeFilter ?? [];
        $ratingFilter = $request->rating ?? [];
        $result = $this->FullManualLeadsCode($lead, $distanceOrder, true, $responseTimeFilter, $ratingFilter);

        if ($result['empty']) {
            return $this->sendResponse(__('No Leads found'), [$result['response']]);
        }

        return $this->sendResponse(__('AutoBid Data'), [$result['response']]);
    }

    public function responseTimeFilter(Request $request)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $responseTimeFilter = $request->response_time; // Expected: '10_min', '1_hour', '6_hour', '24_hour'
        $ratingFilter = $request->rating ?? [];
        $result = $this->FullManualLeadsCode($lead, 'asc', true, $responseTimeFilter, $ratingFilter);

        if ($result['empty']) {
            return $this->sendResponse(__('No Leads found'), [$result['response']]);
        }

        return $this->sendResponse(__('Filtered Data by Response Time'), [$result['response']]);
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
        $result = $this->FullManualLeadsCode($lead, 'asc', true, [], $selectedRating);

        if ($result['empty']) {
            return $this->sendResponse(__('No Leads found'), [$result['response']]);
        }

        return $this->sendResponse(__('Filtered Data by Rating'), [$result['response']]);
    }


    private function FullManualLeadsCode($lead, $distanceOrder = 'asc', $applySellerLimit = false, $responseTimeFilter = [], $ratingFilter = null)
    {
        $bidCount = RecommendedLead::where('lead_id', $lead->id)->count();
        $settings = CustomHelper::setting_value("recommended_list_show_limit", 0);
        $autobid_limit = CustomHelper::setting_value("auto_bid_limit", 0);
        $serviceId = $lead->service_id;
        $leadCreditScore = $lead->credit_score;
        $leadPostcode = $lead->postcode;
        $customerId = $lead->customer_id;
        $questions = json_decode($lead->questions, true);
        $serviceName = Category::find($serviceId)->name ?? '';
    
        $filteredUserIds = null;
        if (!empty($responseTimeFilter)) {
            $timeThresholds = [
                'Responds within 10 mins' => 10,
                'Responds within 1 hour' => 60,
                'Responds within 6 hours' => 360,
                'Responds within 24 hours' => 1440,
            ];
            $maxAllowed = $timeThresholds[$responseTimeFilter] ?? null;
    
            if ($maxAllowed !== null) {
                $filteredUserIds = DB::table('user_response_times')
                    ->where('average', '<=', $maxAllowed)
                    ->pluck('seller_id')
                    ->toArray();
            }
            if (is_array($filteredUserIds) && count($filteredUserIds) === 0) {
                return [
                    'empty' => true,
                    'response' => [
                        'service_name' => $serviceName,
                        'sellers' => [],
                        'bidcount' => $bidCount,
                        'totalbid' => $settings ?? 0,
                        'baseurl' => url('/') . Storage::url('app/public/images/users')
                    ]
                ];
            }
        }
    
        $userServices = User::where('id', '!=', $customerId)
        ->whereRaw("CAST(COALESCE(TRIM(total_credit), '0') AS UNSIGNED) > 0")
            ->when($filteredUserIds, function ($query) use ($filteredUserIds) {
                $query->whereIn('id', $filteredUserIds);
            })
            //rating filter
            ->when(!is_null($ratingFilter), function ($query) use ($ratingFilter) {
                if ($ratingFilter == 'no_rating') {
                    $query->where('avg_rating',0);
                } elseif ($ratingFilter == 5) {
                    $query->where('avg_rating', '=', 5);
                } else {
                    $query->where('avg_rating', '>=', $ratingFilter);
                }
            })
            ->whereIn('id', function ($query) use ($serviceId) {
                $query->select('user_id')
                    ->from('user_services')
                    ->where('service_id', $serviceId);
                    // ->where('auto_bid', 1);
            })
            ->orderByRaw('CAST(total_credit AS UNSIGNED) DESC')
            ->select('id as user_id', 'total_credit')
            ->get();
        Log::debug('userServices:', $userServices->toArray());
        if ($userServices->isEmpty()) {
            return [
                'empty' => true,
                'response' => [
                    'service_name' => $serviceName,
                    'sellers' => [],
                    'bidcount' => $bidCount,
                    'totalbid' => $settings ?? 0,
                    'baseurl' => url('/') . Storage::url('app/public/images/users')
                ]
            ];
        }
    
        $sortedUserIds = $userServices->pluck('user_id')->toArray();
    
        // Get lead coordinates
        $leadCoordinates = $this->getCoordinatesFromPostcode($leadPostcode);
        if (!isset($leadCoordinates['lat'], $leadCoordinates['lng'])) {
            return [
                'empty' => true,
                'response' => [
                    'service_name' => $serviceName,
                    'sellers' => [],
                    'bidcount' => $bidCount,
                    'totalbid' => $settings ?? 0,
                    'baseurl' => url('/') . Storage::url('app/public/images/users')
                ]
            ];
        }
    
        // Get all user service locations and calculate distance using Haversine
        $userLocations = UserServiceLocation::whereIn('user_id', $sortedUserIds)
            ->where('service_id', $serviceId)
            ->get()
            ->groupBy('user_id');

            Log::debug('userLocations:', $userLocations->toArray());
    
        // Filter based on Haversine distance or nation_wide = 1
        $locationMatchedUsers = collect();
        foreach ($userLocations as $userId => $locations) {
            foreach ($locations as $location) {
                $sellerCoordinates = json_decode($location->coordinates, true);
                if (isset($sellerCoordinates['lat'], $sellerCoordinates['lng'])) {
                    $distance = $this->calculateHaversineDistance(
                        $leadCoordinates['lat'],
                        $leadCoordinates['lng'],
                        $sellerCoordinates['lat'],
                        $sellerCoordinates['lng']
                    );
                    if ($distance !== null && $distance > 0) {
                        $location->distance = $distance;
                        $locationMatchedUsers[$userId] = $location;
                        break; // Take the first valid match
                    }
                }
            }
        }
    
        // If no nearby match, fallback to nation_wide
        // if ($locationMatchedUsers->isEmpty()) {
         //Always include nationwide users, no distance calculation for them
            $nationWide = UserServiceLocation::whereIn('user_id', $sortedUserIds)
                ->where('service_id', $serviceId)
                ->where('nation_wide', 1)
                ->get()
                ->groupBy('user_id');
    
            foreach ($nationWide as $userId => $locations) {
                if (!$locationMatchedUsers->has($userId)) {
                    $location = $locations->first();
                    $location->distance = 0;
                    $locationMatchedUsers[$userId] = $location;
                }
            }
            Log::debug('locationMatcheduser:', $locationMatchedUsers->toArray());
            if ($locationMatchedUsers->isEmpty()) {
                return [
                    'empty' => true,
                    'response' => [
                        'service_name' => $serviceName,
                        'sellers' => [],
                        'bidcount' => $bidCount,
                        'totalbid' => $settings ?? 0,
                        'baseurl' => url('/') . Storage::url('app/public/images/users')
                    ]
                ];
            }
        // }
    
        $matchedUserIds = $locationMatchedUsers->keys()->toArray();
    
        $questionTextToId = ServiceQuestion::whereIn('questions', collect($questions)->pluck('ques')->toArray())
            ->pluck('id', 'questions')->toArray();
    
        $questionFilters = collect($questions)
            ->filter(fn($q) => is_array($q) && isset($q['ques'], $questionTextToId[$q['ques']]))
            ->map(fn($q) => ['question_id' => $questionTextToId[$q['ques']], 'answer' => $q['ans']]);
    
        // $matchedPreferences = LeadPrefrence::whereIn('user_id', $matchedUserIds)
        //     ->where('service_id', $serviceId)
        //     ->where(function ($query) use ($questionFilters) {
        //         foreach ($questionFilters as $filter) {
        //             foreach (array_map('trim', explode(',', $filter['answer'])) as $ans) {
        //                 $query->orWhere(fn($q2) =>
        //                     $q2->where('question_id', $filter['question_id'])
        //                         ->where('answers', 'LIKE', '%' . $ans . '%')
        //                 );
        //             }
        //         }
        //     })->get();
        $matchedPreferences = collect();

            foreach ($matchedUserIds as $userId) {
                $allMatch = true;

                foreach ($questionFilters as $filter) {
                    $preference = LeadPrefrence::where('user_id', $userId)
                        ->where('service_id', $serviceId)
                        ->where('question_id', $filter['question_id'])
                        ->first();

                    if (!$preference) {
                        $allMatch = false;
                        break;
                    }

                    $sellerAnswers = array_map('trim', explode(',', $preference->answers ?? ''));
                    $buyerAnswers = array_map('trim', explode(',', $filter['answer'] ?? ''));

                    // ❌ If any buyer-selected answer is not in seller's preferences, exclude seller
                    if (count(array_intersect($buyerAnswers, $sellerAnswers)) !== count($buyerAnswers)) {
                        $allMatch = false;
                        break;
                    }
                }

                if ($allMatch) {
                    $matchedPreferences->push(['user_id' => $userId]);
                }
            }



        // Log::debug('Matched Question answer:', $matchedPreferences->toArray());
        Log::debug('Matched Question answer:' . PHP_EOL . print_r($matchedPreferences->toArray(), true));
        $scoredUsers = collect($matchedPreferences)->pluck('user_id')->flip()->map(fn() => 1);
        // $scoredUsers = $matchedPreferences->groupBy('user_id')->map->count();
    
        $existingBids = RecommendedLead::where('buyer_id', $customerId)
            ->where('lead_id', $lead->id)
            ->pluck('seller_id')
            ->toArray();
    
        $sellersWith3Bids = [];
        if ($applySellerLimit) {
            $autobidDaysLimit = CustomHelper::setting_value('autobid_days_limit', 0); // e.g., 7 days
             $sellersWith3Bids = RecommendedLead::select('seller_id', DB::raw('MIN(created_at) as first_bid_date'))
                                                ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                                                ->where('purchase_type', 'Autobid')
                                                ->groupBy('seller_id')
                                                ->havingRaw('COUNT(DISTINCT buyer_id) >= 3')
                                                ->get()
                                                ->filter(function ($record) {
                                                    $autobidDaysLimit = CustomHelper::setting_value('autobid_days_limit', 0); // Use your config
                                                    return Carbon::parse($record->first_bid_date)->diffInDays(Carbon::now()) < $autobidDaysLimit;
                                                })
                                                ->pluck('seller_id')
                                                ->toArray();

            // $sellersWith3Bids = RecommendedLead::select('seller_id', 'service_id', DB::raw('MIN(created_at) as first_bid_date'))
            //     ->where('purchase_type', 'Autobid') // Only consider auto bids
            //     ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            //     ->groupBy('seller_id', 'service_id')
            //     ->havingRaw('COUNT(DISTINCT recommended_leads.buyer_id) >= ?', [$autobid_limit])
            //     // ->havingRaw('COUNT(DISTINCT buyer_id) >= 3')
            //     ->get()
            //     ->filter(function ($record) use ($autobidDaysLimit) {
            //         return Carbon::parse($record->first_bid_date)->diffInDays(Carbon::now()) < $autobidDaysLimit;
            //     })
            //     ->map(function ($record) {
            //         return $record->seller_id . '_' . $record->service_id; // Make a unique key
            //     })
            //     ->toArray();
        }
        // Log::debug('sellersWith3Bids:', $sellersWith3Bids);
        Log::debug('sellersWith3Bids:' . PHP_EOL . print_r($sellersWith3Bids, true));
        $responseTimesMap = DB::table('user_response_times')
            ->whereIn('seller_id', $scoredUsers->keys()->toArray())
            ->pluck('average', 'seller_id')
            ->toArray();
        
        $finalUsers = $scoredUsers->filter(fn($score) => $score > 0)->keys()->map(function ($userId) use (
            $locationMatchedUsers,
            $leadCreditScore,
            $scoredUsers,
            $serviceName,
            $serviceId,
            $existingBids,
            $sellersWith3Bids,
            $applySellerLimit,
            $lead,
            $responseTimesMap
        ) {
            if (in_array($userId, $existingBids)) return null;
            if ($applySellerLimit && in_array($userId, $sellersWith3Bids)) return null;
    
            $user = User::where('id', $userId)->first();
    
            if (!$user) return null;
    
            $userLocation = $locationMatchedUsers[$userId];
            $miles = $userLocation->distance ?? 0;
            
            if ($miles < 0) return null;
            // if ($miles === 0) return null;
    
            return array_merge($user->toArray(), [
                'credit_score' => $leadCreditScore,
                'service_name' => $serviceName,
                'service_id' => $serviceId,
                'distance' => $miles,
                'score' => $scoredUsers[$userId] ?? 0,
                'quicktorespond' => isset($responseTimesMap[$userId]) && $responseTimesMap[$userId] <= 720 ? 1 : 0,
            ]);
        })->filter();
        // Log::debug('finalUsers:', $finalUsers->toArray());
        $finalUsers = $distanceOrder === 'desc'
            ? $finalUsers->sortByDesc('distance')->values()
            : $finalUsers->sortBy('distance')->values();
        // Log::debug('finalUsers distance:', $finalUsers->toArray());
        Log::debug('finalUsers distance:' . PHP_EOL . print_r($finalUsers->toArray(), true));

        // Split into recommended and general sellers
        $recommendedLimit = $settings > 0 ? $settings : 0;
        // Log::debug('finalUsers distance:', $finalUsers->toArray());
        Log::debug('recommendedLimit:' . PHP_EOL . print_r($recommendedLimit, true));

        // Dynamically calculate top credit sellers (80%) and nearest sellers (20%)
        $topCreditCount = ceil($recommendedLimit * 0.8);
        $nearestCount = $recommendedLimit - $topCreditCount;
        Log::debug('nearestCount:' . PHP_EOL . print_r($nearestCount, true));


        // Get top credit sellers excluding sellers who already have 3 autobids
        $topCreditSellers = $finalUsers->sortByDesc('total_credit')
            ->filter(fn($u) => !in_array($u['id'] . '_' . $serviceId, $sellersWith3Bids))
            ->take($topCreditCount);
        Log::debug('topCreditSellers:' . PHP_EOL . print_r($topCreditSellers, true));    

        // Remove already selected top credit sellers from the pool
        $remainingUsers = $finalUsers->reject(fn($u) => $topCreditSellers->contains('id', $u['id']));

        // Adjust nearest count in case fewer top credit sellers were found
        $adjustedNearestCount = $recommendedLimit - $topCreditSellers->count();

        // Get nearest sellers from the remaining pool
        $nearestSellers = $remainingUsers->sortBy('distance')->take($adjustedNearestCount);

        // Merge top credit + nearest sellers into final recommended list
        $recommendedUsers = $topCreditSellers->merge($nearestSellers)->values();

        // Sort all sellers by distance for fallback/general listing
        $sortedAll = $finalUsers->sortBy('distance')->values();

        // Merge recommended users first, then others not in recommended list
        $mergedSellers = $recommendedUsers->concat(
            $sortedAll->reject(fn($seller) => $recommendedUsers->contains('id', $seller['id']))
        )->values();

        return [
            'empty' => false,
            'response' => [
                'service_name' => $serviceName,
                'sellers' => $mergedSellers,
                'bidcount' => $bidCount,
                'totalbid' => $settings ?? 0,
                'baseurl' => url('/') . Storage::url('app/public/images/users')
            ]
        ];
    }

    public function getRatingFilter(Request $request)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $ratings = [];

        for ($i = 1; $i <= 5; $i++) {
            // Simulate rating filter exactly like the `ratingFilter` method
            $result = $this->FullManualLeadsCode($lead, 'asc', true, [], $i);

            $ratings[] = [
                'label' => $i == 5 ? 'only' : '& up',
                'value' => $i,
                'count' => count($result['response']['sellers']),
            ];
        }

         // Handle sellers with no rating (avg_rating is null)
        $resultNoRating = $this->FullManualLeadsCode($lead, 'asc', true, [], 'no_rating');

        $ratings[] = [
            'label' => 'No rating',
            'value' => 'no_rating',
            'count' => count($resultNoRating['response']['sellers']),
        ];

        return $this->sendResponse(__('Filtered Data by Rating'), [$ratings]);
    }

    // Get coordinates for a given postcode using Google Geocoding API
    public function getCoordinatesFromPostcode($postcode)
    {
        $encodedPostcode = urlencode($postcode);
        $apiKey = "AIzaSyDwAeV7juA_VpzLHqmKXACBtcZxR52TwoE"; // Replace with your API key
    
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$encodedPostcode}&key={$apiKey}";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
    
        if ($data['status'] === 'OK' && isset($data['results'][0]['geometry']['location'])) {
            return $data['results'][0]['geometry']['location']; // ['lat' => ..., 'lng' => ...]
        }
    
        return null;
    }
    // Calculates distance between two lat/lng pairs using Haversine formula
    private function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius of the earth in km
    
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
    
        $latDiff = $lat2 - $lat1;
        $lonDiff = $lon2 - $lon1;
    
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos($lat1) * cos($lat2) *
             sin($lonDiff / 2) * sin($lonDiff / 2);
    
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distanceKm = $earthRadius * $c;
    
        return round($distanceKm * 0.621371, 2); // convert to miles
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

    public function addManualBid(Request $request){
        $aVals = $request->all();
        if(!isset($aVals['bidtype']) || empty($aVals['bidtype'])){
            return $this->sendError(__('Lead request not found'), 404);
        }
        
        $isDataExists = LeadStatus::where('lead_id',$aVals['lead_id'])->where('status','pending')->first();
        $leadtime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();
        $creditscore = LeadRequest::where('id',$aVals['lead_id'])->pluck('credit_score')->first();
        
        // $settings = Setting::first();  
        $settings = CustomHelper::setting_value("auto_bid_limit", 0);
        if($aVals['bidtype'] == 'reply'){
            $totalcredit = User::where('id',$aVals['seller_id'])->pluck('total_credit')->first();
            $bidCheck = RecommendedLead::where('lead_id', $aVals['lead_id'])
                                        ->where('service_id', $aVals['service_id'])
                                        ->where('buyer_id', $aVals['user_id'])
                                        ->where('seller_id',$aVals['seller_id'])
                                        ->first();
            if($totalcredit >= $creditscore){
                    // $bidCount = RecommendedLead::where('lead_id', $aVals['lead_id'])
                //                ->where('service_id', $aVals['service_id'])
                //                ->count();
                $isActivityExists = self::getActivityLog($aVals['user_id'],$aVals['seller_id'],$aVals['lead_id'],"Requested a callback");
                // ActivityLog::where('lead_id',$aVals['lead_id'])
                //                               ->where('from_user_id',$aVals['user_id']) 
                //                               ->where('to_user_id',$aVals['seller_id']) 
                //                               ->where('activity_name',"Requested a callback") 
                //                               ->first(); 
                // if($bidCount==$settings->total_bid){
                //     return $this->sendError(__('Bid Limit exceed'), 404);
                // }
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
                LeadRequest::where('id',$aVals['lead_id'])->update(['status'=>'pending']);
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
                $detail = $aVals['bid'] . " credit deducted for Request Reply";
                DB::table('users')->where('id', $aVals['seller_id'])->decrement('total_credit', $aVals['bid']);
                CustomHelper::createTrasactionLog($aVals['seller_id'], 0, $aVals['bid'], $detail, 0, 1, $error_response='');
            }else{
                return $this->sendError(__("Seller don't have sufficient balance"), 404);
            }                           
           
        }
        if($aVals['bidtype'] == 'purchase_leads'){
            $bidsdata = RecommendedLead::where('lead_id', $aVals['lead_id'])
                                       ->where('service_id', $aVals['service_id'])
                                       ->where('seller_id', $aVals['user_id'])
                                       ->where('buyer_id',$aVals['buyer_id'])
                                       ->first();
            $sellers = User::where('id',$aVals['user_id'])->pluck('name')->first();
            $buyer = User::where('id',$aVals['buyer_id'])->pluck('name')->first();
            $activityname = 'You Contacted '. $buyer;
            // $bidCount = RecommendedLead::where('lead_id', $aVals['lead_id'])
            //                ->where('service_id', $aVals['service_id'])
            //                ->count();
            $isActivityExists = self::getActivityLog($aVals['user_id'],$aVals['buyer_id'],$aVals['lead_id'],$activityname);
            // if($bidCount==$settings->total_bid){
            //     return $this->sendError(__('Bid Limit exceed'), 404);
            // }
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
            LeadRequest::where('id',$aVals['lead_id'])->update(['status'=>'pending']);
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
            $detail = $aVals['bid'] . " credit deducted for Contacting to Customer";
            DB::table('users')->where('id', $aVals['user_id'])->decrement('total_credit', $aVals['bid']);
            CustomHelper::createTrasactionLog($aVals['user_id'], 0, $aVals['bid'], $detail, 0, 1, $error_response='');
        }
       
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
        $fiveMinutesAgo = $now->copy()->subMinutes(1);
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

        $sellersWith3Autobids = RecommendedLead::select('seller_id', DB::raw('MIN(created_at) as first_bid_date'))
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
                    if (!empty($userdetails) && $userdetails->autobid_pause == 0 && !in_array($seller->id, $sellersWith3Autobids)) 
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
    // public function autoBidLeadsAfter5Min($fiveMinutesAgo)
    // {
    //     $settings = CustomHelper::setting_value("auto_bid_limit", 0);
    //     $autobidDaysLimit = CustomHelper::setting_value('autobid_days_limit', 0); // e.g., 7 days
    //     $sellersWith3Autobids = RecommendedLead::select(
    //                                                     'recommended_leads.seller_id', 
    //                                                     'recommended_leads.service_id', 
    //                                                     DB::raw('MIN(recommended_leads.created_at) as first_bid_date')
    //                                                     )
    //                     ->join('user_details', 'recommended_leads.seller_id', '=', 'user_details.user_id')
    //                     ->where('user_details.is_autobid', 1)
    //                     ->where('user_details.autobid_pause', 1)
    //                     ->where('recommended_leads.purchase_type', 'Autobid')
    //                     ->whereBetween('recommended_leads.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
    //                     ->groupBy('recommended_leads.seller_id', 'recommended_leads.service_id')
    //                     ->havingRaw('COUNT(DISTINCT recommended_leads.buyer_id) >= ?', [$settings])
    //                     // ->havingRaw('COUNT(DISTINCT recommended_leads.buyer_id) >= 3')
    //                     ->get()
    //                     ->filter(function ($record) use ($autobidDaysLimit) {
    //                         return Carbon::parse($record->first_bid_date)->diffInDays(Carbon::now()) < $autobidDaysLimit;
    //                     })
    //                     ->map(function ($record) {
    //                         return $record->seller_id . '_' . $record->service_id;
    //                     })
    //                     ->toArray();

       

    //     $leads = LeadRequest::where('closed_status', 0)
    //             ->where('should_autobid', 0)
    //             ->where('created_at', '<=', $fiveMinutesAgo)
    //             ->get();
        
    //     $autoBidLeads = [];
            
    //         foreach ($leads as $lead) {
    //             $isDataExists = LeadStatus::where('lead_id',$lead->id)->where('status','pending')->first();
    //             $existingBids = RecommendedLead::where('lead_id', $lead->id)->count();
        
    //             if ($existingBids >= $settings) {
    //                 continue; // Skip if already has 5 bids
    //             }
        
    //             // Call getManualLeads with a request object
    //             $manualLeadRequest = new Request(['lead_id' => $lead->id]);
    //             $manualLeadsResponse = $this->getManualLeads($manualLeadRequest)->getData();
    //             //  $manualLeadsResponse = $this->getManualLeads($lead->id)->getData();
        
    //             if (empty($manualLeadsResponse->data[0]->sellers)) {
    //                 LeadRequest::where('id', $lead->id)->update(['should_autobid' => 1]);
    //                 continue;
    //             }
        
    //             $sellers = collect($manualLeadsResponse->data[0]->sellers)->take($settings - $existingBids);
    //             $bidsPlaced = 0;
    //             foreach ($sellers as $seller) {
    //                 $userdetails = UserDetail::where('user_id',$seller->id)->first();
    //                 $compositeKey = $seller->id . '_' . $seller->service_id;

    //                   // ✅ Skip if no user details or autobid paused
    //                 if (empty($userdetails) || $userdetails->autobid_pause != 0) {
    //                     continue;
    //                 }

    //                 //If is_autobid = 1, apply 3 autobid limit
    //                 if ($userdetails->is_autobid == 1 && in_array($compositeKey, $sellersWith3Autobids)) {
    //                     continue;
    //                 }
    //                 // if (!empty($userdetails) && $userdetails->autobid_pause == 0 && !in_array($compositeKey, $sellersWith3Autobids)) 
    //                 // {
    //                     $alreadyBid = RecommendedLead::where([
    //                         ['lead_id', $lead->id],
    //                         ['buyer_id', $lead->customer_id],
    //                         ['seller_id', $seller->id],
    //                     ])->exists();
            
    //                     if (!$alreadyBid) {
    //                         // Deduct credit (only if buyer has enough)
    //                         $bidAmount = $seller->bid ?? $lead->credit_score ?? 0;
    //                         $detail = $bidAmount . " credit deducted for Autobid";
    //                         $user = DB::table('users')->where('id', $seller->id)->first();
                            
    //                         if ($user && $user->total_credit >= $bidAmount) {
    //                             DB::table('users')->where('id', $seller->id)->decrement('total_credit', $bidAmount);
    //                             CustomHelper::createTrasactionLog($seller->id, 0, $bidAmount, $detail, 0, 1, $error_response='');
            
    //                             RecommendedLead::create([
    //                                 'lead_id'     => $lead->id,
    //                                 'buyer_id'    => $lead->customer_id,
    //                                 'seller_id'   => $seller->id,
    //                                 'service_id'  => $seller->service_id,
    //                                 'bid'         => $bidAmount,
    //                                 'distance'    => $seller->distance ?? 0,
    //                                 'purchase_type' => "Autobid"
    //                             ]);
                                
            
    //                             $autoBidLeads[] = [
    //                                 'lead_id'   => $lead->id,
    //                                 'seller_id' => $seller->id,
    //                             ];

    //                             if(empty($isDataExists)){
    //                                 LeadStatus::create([
    //                                     'lead_id' => $lead->id,
    //                                     'user_id' => $lead->customer_id,
    //                                     'status' => 'pending',
    //                                     'clicked_from' => 2,
    //                                 ]);  
    //                             }
                                
    //                             $bidsPlaced++;
    //                         }
    //                     }
    //                 // }
    //             }
    //             // Mark autobid processed if any bid was placed or no sellers found
    //                 if ($bidsPlaced > 0) {
    //                     LeadRequest::where('id', $lead->id)->update(['should_autobid' => 1,'status'=>'pending']);
    //                 }
                    
    //         }
        
    //     return $autoBidLeads;
    // }

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
        $activityname = $buyer .' viewed your profile';
        
        $isActivity = self::getActivityLog($aVals['user_id'], $aVals['seller_id'], $aVals['lead_id'], $activityname);
        if(empty($isActivity)){
            self::addActivityLog($aVals['user_id'], $aVals['seller_id'], $aVals['lead_id'], $activityname, "Buyer viewed Seller Profile", $leadtime);
        }
        return $this->sendResponse(__('Viewed your Profile'),[]);                      
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
