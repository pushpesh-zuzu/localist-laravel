<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\Category;
use App\Models\Bid;
use App\Models\LeadPrefrence;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceQuestion;
use App\Models\UserServiceLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use Log;

class ApiController extends Controller
{
    public function getCategories()
    {
        $aRows = Category::where('status',1)->get();
        return $this->sendResponse(__('Category Data'),$aRows);
    }

    public function popularServices()
    {

        $aRows = Category::where('is_home',1)->orderBy('id','DESC')->where('status',1)->get();
        foreach($aRows as $value){
            $value['baseurl'] = url('/').Storage::url('app/public/images/category');
        }
        
        return $this->sendResponse(__('Category Data'),$aRows);
    }

    public function searchServices(Request $request)
    {
        $search = $request->search; // Get search keyword from request
        $serviceid = $request->serviceid; // Get search keyword from request
    
        // Check if search keyword is provided; otherwise, return empty
        if (empty($search)) {
            $categories = [];
            return $this->sendResponse(__('Category Data'), $categories);
        }
        if(!empty($serviceid)){
            $categories = Category::where('status', 1)
            ->where('id', '!=', $serviceid)
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

    public function autobid(Request $request)
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
            nation_wide TINYINT(1) DEFAULT 0
        )");

        // Step 2: Insert Auto-Bid Sellers into Temporary Table
        DB::table('temp_sellers')->insertUsing(
            ['user_id', 'postcode', 'total_credit', 'service_id', 'buyer_id', 'lead_id', 'nation_wide'],
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
                    'usl.nation_wide' // Fetch nationwide from user_service_locations table
                ])
                ->where('us.service_id', $leadRequest->service_id)
                ->where('us.auto_bid', 1)
                ->where('usl.status', 1)
                ->where('u.total_credit', '>=', 20)
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

            foreach ($sortedSellers as $seller) {
                $insertData[] = [
                    'service_id'   => $seller->service_id,
                    'seller_id'    => $seller->user_id,
                    'buyer_id'     => $seller->buyer_id,
                    'lead_id'      => $seller->lead_id,
                    'bid'          => 20, // Fixed bid amount
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];

                // Deduct 20 credits from seller's total_credit
                DB::table('users')
                    ->where('id', $seller->user_id)
                    ->decrement('total_credit', 20);
            }

            // Bulk Insert Bids
            DB::table('bids')->insert($insertData);
        }

        // Step 7: Drop Temporary Table
        DB::statement("DROP TEMPORARY TABLE IF EXISTS temp_sellers");

        return $this->sendResponse(__('Bids inserted successfully'),[]);
    }

    private function getDistance($postcode1, $postcode2)
    {
        
        $encodedPostcode1  = urlencode($postcode1);
        $encodedPostcode2 = urlencode($postcode2);
        $apiKey = "AIzaSyB29PyyFmCsm_nw8ELavLskRzMPd3XEIac"; // Replace with your API key
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$encodedPostcode1}&destinations={$encodedPostcode2}&key={$apiKey}";
        // dd($url);

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] == 'OK' && isset($data['rows'][0]['elements'][0]['distance'])) {
            return $data['rows'][0]['elements'][0]['distance']['text']; // Distance in km
        } else {
            return "Distance not found";
        }
    }

    public function autobidList(Request $request){
        $seller_id = $request->user_id; 
        $leadid = $request->lead_id; 
        $bids = [];
        if (!empty($leadid)) {
            $bids = Bid::where('seller_id',$seller_id)->where('lead_id',$leadid)->orderBy('id','DESC')->get();
        }else{
            $bids = Bid::where('seller_id',$seller_id)->orderBy('id','DESC')->get();
        }
        return $this->sendResponse(__('AutoBid Data'), $bids);
    }

    public function leadpreferences(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $request->validate([
            'service_id' => 'required',
            'question_id' => 'required',
            'user_id' => 'required',
            'answers' => 'required',
        ]);

        $cleanedAnswer = preg_replace('/\s*,\s*/', ',', $aVals['answers']);
    
        // Remove trailing comma if it exists
        $cleanedAnswer = rtrim($cleanedAnswer, ',');

        // Filter out any empty values after splitting by comma
        $answerArray = array_filter(explode(',', $cleanedAnswer), function($value) {
            return trim($value) !== ''; // Ensure no empty entries
        });
        // Rebuild the answer string
        $aVals['answers'] = implode(',', $answerArray);
        $leadPreference = LeadPrefrence::create([
            'service_id'  => $request->service_id,
            'question_id' => $request->question_id,
            'user_id'     => $request->user_id,
            'answers'     => $aVals['answers'], 
        ]);
        return $this->sendResponse(__('Data added sucessfully'), $leadPreference);
    }
}
