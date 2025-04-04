<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\Category;
use App\Models\Bid;
use App\Models\LeadPrefrence;
use App\Models\UserDetail;
use App\Models\UserAccreditation;
use App\Models\UserServiceDetail;
use App\Models\ProfileQuestion;
use App\Models\ProfileQA;
use App\Models\UserCardDetail;
use App\Models\UserService;
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
use App\Helpers\CustomHelper;

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
            $bids = Bid::where('seller_id',$seller_id)->where('lead_id',$leadid)->with(['sellers','buyers'])->orderBy('id','DESC')->get();
        }else{
            $bids = Bid::where('seller_id',$seller_id)->with(['sellers','buyers'])->orderBy('id','DESC')->get();
        }
        return $this->sendResponse(__('AutoBid Data'), $bids);
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

    public function getleadpreferences(Request $request): JsonResponse
    {
        $user_id = $request->user_id; 
        $service_id = $request->service_id; 
        $leadPreference = LeadPrefrence::where('service_id', $service_id)
                                        ->where('user_id', $user_id)
                                        ->get();
        if(count($leadPreference)>0){
            foreach($leadPreference as $value){
                $questions = ServiceQuestion::where('category', $value->question_id)->first();
                $value['questions'] = $questions->questions;
                $value['answer'] = $questions->answer;
            }
            $leadPreferences = $leadPreference;
        }else{
            $leadPreferences = ServiceQuestion::where('category', $service_id)->get();
            
        }                          
        return $this->sendResponse(__('Lead Preferences Data'), $leadPreferences);                              
    }

    public function switchAutobid(Request $request): JsonResponse
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

    public function sellerMyprofile(Request $request): JsonResponse
    {
        $user_id = $request->user_id; 
        $aValues = $request->all();
        $users = User::where('id',$user_id)->first();
        $userdetails = UserDetail::where('user_id',$user_id)->first();
        $accreditations = UserAccreditation::where('user_id',$user_id)->where('id',$aValues['accreditation_id'])->first();
        $serviceDetails = UserServiceDetail::where('user_id',$user_id)->where('id',$aValues['user_service_id'])->first();
        if($aValues['type'] == 'about'){
                if ($request->hasFile('company_logo')) {
                    $imagePath =  CustomHelper::fileUpload($aValues['company_logo'],'users');
                    $company_logo = $imagePath; 
                }else{
                    $company_logo = "";
                }
                if ($request->hasFile('profile_image')) {
                    $profileimagePath =  CustomHelper::fileUpload($aValues['profile_image'],'users');
                    $profile_image = $profileimagePath; 
                }else{
                    $profile_image = "";
                }
                $userData = self::aboutData($aValues,$company_logo,$profile_image,$users,$user_id);
        }

        if($aValues['type'] == 'user_details'){
            if ($request->hasFile('company_photos')) {
                $companyimgPaths = []; // Store multiple image names
                foreach ($request->file('company_photos') as $image) {
                    $companyimagePath = CustomHelper::fileUpload($image, 'users');
                    if ($companyimagePath) {
                        $companyimgPaths[] = $companyimagePath; // Add filename to the array
                    }
                }
                
                $company_photos = implode(',', $companyimgPaths); // Convert array to a comma-separated string
            }else{
                $company_photos = "";
            }
            $userData = self::userdetailData($aValues,$company_photos,$userdetails,$user_id);
        }
        if($aValues['type'] == 'accreditations'){
                if ($request->hasFile('accre_image')) {
                    $imagePath =  CustomHelper::accfileUpload($aValues['accre_image'],'accreditations');
                    $accre_image = $imagePath; 
                }else{
                    $accre_image = "";
                }
                  // Handle delete condition
                if (isset($aValues['deleteData']) && $aValues['deleteData'] == 1) {
                    $accrDeleteIds = explode(',', $aValues['accr_delete_id']);
                    $accDatas = UserAccreditation::whereIn('id', $accrDeleteIds)
                                                 ->where('user_id', $user_id)
                                                 ->get();
                    if (count($accDatas)>0) {
                        UserAccreditation::whereIn('id', $accrDeleteIds)
                            ->where('user_id', $user_id)
                            ->delete();
                            return $this->sendResponse(__('Accreditations deleted successfully'), []);
                    } else {
                        return $this->sendError("No Data found");
                        // Delete a single record
                        // UserAccreditation::where('id', $aValues['accr_delete_id'])
                        //     ->where('user_id', $user_id)
                        //     ->delete();
                    }

                    
                }

                $userData = self::accreditationData($aValues,$accreditations,$user_id,$accre_image,$userdetails);
                $userData->is_accreditations =  $aValues['is_accreditations']; 
        }

        if($aValues['type'] == 'userservices'){
               // Handle delete condition
               if (isset($aValues['deleteData']) && $aValues['deleteData'] == 1) {
                $serviceDeleteIds = explode(',', $aValues['service_delete_id']);
                $serDatas = UserServiceDetail::whereIn('id', $serviceDeleteIds)
                                             ->where('user_id', $user_id)
                                             ->get();
                if (count($serDatas)>0) {
                    UserServiceDetail::whereIn('id', $serviceDeleteIds)
                                     ->where('user_id', $user_id)
                                     ->delete();
                } else {
                    return $this->sendError("No Data found");
                }

                return $this->sendResponse(__('Services deleted successfully'), []);
            }
            $userData = self::serviceData($aValues,$serviceDetails,$user_id);
        }
        return $this->sendResponse(__('MyProfile updated successfully'),$userData );
    }

    public function userdetailData($aValues,$company_photos,$userdetails,$user_id){
        if(isset($userdetails) && $userdetails != ''){
            $userdetails->update([
                'company_photos' => $company_photos,
                // 'user_emails_reviews' => $aValues['user_emails_reviews'],
                'is_youtube_video' => $aValues['is_youtube_video'],
                'company_youtube_link' => $aValues['company_youtube_link'],
                'is_fb' => $aValues['is_fb'],
                'fb_link' => $aValues['fb_link'],
                'is_twitter' => $aValues['is_twitter'],
                'twitter_link' => $aValues['twitter_link'],
                'is_link_desc' => $aValues['is_link_desc'],
                'link_desc' => $aValues['link_desc'],
            ]);  
        }else{
            $userdetails = UserDetail::create([
                'user_id'  => $user_id,
                'company_photos' => $company_photos,
                // 'user_emails_reviews' => $aValues['user_emails_reviews'],
                'is_youtube_video' => $aValues['is_youtube_video'],
                'company_youtube_link' => $aValues['company_youtube_link'],
                'is_fb' => $aValues['is_fb'],
                'fb_link' => $aValues['fb_link'],
                'is_twitter' => $aValues['is_twitter'],
                'twitter_link' => $aValues['twitter_link'],
                'is_link_desc' => $aValues['is_link_desc'],
                'link_desc' => $aValues['link_desc'],
                'is_autobid' => 1
            ]);
        }
        return $userdetails;
    }

    public function aboutData($aValues,$company_logo,$profile_image,$users,$user_id){
        // if(isset($users) && $users != ''){
            $users->update([
                'company_name' => $aValues['company_name'],
                'company_logo' => $company_logo,
                'name' => $aValues['name'],
                'profile_image' => $profile_image,
                'company_email' => $aValues['company_email'],
                'company_phone' => $aValues['company_phone'],
                'company_website' => $aValues['company_website'],
                'company_location' => $aValues['company_location'],
                'company_locaion_reason' => $aValues['company_locaion_reason'],
                'company_size' => $aValues['company_size'],
                'company_total_years' => $aValues['company_total_years'],
                'about_company' => $aValues['about_company'],
            ]);
        // }
        return $users;
    }

    public function accreditationData($aValues,$accreditations,$user_id,$image,$userdetails){
        if(isset($accreditations) && $accreditations != ''){
            $accreditations->update([
                'name' => $aValues['accre_name'],
                'image' => $image
            ]);
            $userdetails->update([
                'is_accreditations' => $aValues['is_accreditations'],
            ]);
        }else{
            $accreditations = UserAccreditation::create([
                'user_id'  => $user_id,
                'name' => $aValues['accre_name'],
                'image' => $image
            ]);
            UserDetail::create([
                'user_id'  => $user_id,
                'is_accreditations' => $aValues['is_accreditations'],
            ]);
        }
        return $accreditations;
    }

    public function serviceData($aValues,$serviceDetails,$user_id){
        if(isset($serviceDetails) && $serviceDetails != ''){
            $serviceDetails->update([
                'title' => $aValues['service_title'],
                'description' => $aValues['service_desc']
            ]);
        }else{
            $serviceDetails = UserServiceDetail::create([
                'user_id'  => $user_id,
                'title' => $aValues['service_title'],
                'description' => $aValues['service_desc']
            ]);
        }
        return $serviceDetails;
    }

    public function sellerProfileQues(){
        $questions = ProfileQuestion::where('status', 1)->orderBy('id', 'DESC')->get();
        return $this->sendResponse(__('Profile Questions Data'), $questions);
    }

    public function sellerMyprofileqa(Request $request): JsonResponse
    {
        $user_id = $request->user_id;
        $questions = $request->input('questions', []); // Get array of questions
        $answers = $request->input('answers', []); // Get array of answers
    
        if (!is_array($questions) || !is_array($answers)) {
            return $this->sendError("Invalid data format");
        }
    
        $data = [];
        foreach ($questions as $index => $question) {
            $answer = $answers[$index] ?? null;
    
            if (empty($question) || empty($answer)) {
                continue; // Skip if question or answer is empty
            }
    
            // Check if the question already exists for this user
            $profileQues = ProfileQA::where('user_id', $user_id)
                ->where('questions', $question)
                ->first();
    
            if ($profileQues) {
                // Update existing record
                $profileQues->update([
                    'answer' => $answer
                ]);
                $data[] = $profileQues;
            } else {
                // Insert new record
                $newQnA = ProfileQA::create([
                    'user_id' => $user_id,
                    'questions' => $question,
                    'answer' => $answer
                ]);
                $data[] = $newQnA;
            }
        }
    
        if (empty($data)) {
            return $this->sendError("No valid data submitted");
        }
    
        return $this->sendResponse(__('Data Submitted successfully'), $data);
    }

    public function sellerBillingDetails(Request $request){
        $user_id = $request->user_id; 
        $aValues = $request->all();
        $userdetails = UserDetail::where('user_id',$user_id)->first();
        if(isset($userdetails) && $userdetails != ''){
            $userdetails->update([
                'billing_contact_name' => $aValues['billing_contact_name'],
                'billing_address1' => $aValues['billing_address1'],
                'billing_address2' => $aValues['billing_address2'],
                'billing_city' => $aValues['billing_city'],
                'billing_postcode' => $aValues['billing_postcode'],
                'billing_phone' => $aValues['billing_phone'],
                'billing_vat_register' => $aValues['billing_vat_register'],
            ]);  
        }else{
            $userdetails = UserDetail::create([
                'user_id'  => $user_id,
                'billing_contact_name' => $aValues['billing_contact_name'],
                'billing_address1' => $aValues['billing_address1'],
                'billing_address2' => $aValues['billing_address2'],
                'billing_city' => $aValues['billing_city'],
                'billing_postcode' => $aValues['billing_postcode'],
                'billing_phone' => $aValues['billing_phone'],
                'billing_vat_register' => $aValues['billing_vat_register'],
            ]);
        }
        return $userdetails;
    }

    public function sellerCardDetails(Request $request){
        $user_id = $request->user_id; 
        $aValues = $request->all();
        $userdetails = UserCardDetail::where('user_id',$user_id)->first();
        if(isset($userdetails) && $userdetails != ''){
            $userdetails->update([
                'card_number' => $aValues['card_number'],
                'expiry_date' => $aValues['expiry_date'],
                'cvc' => $aValues['cvc']
            ]);  
        }else{
            $userdetails = UserCardDetail::create([
                'user_id'  => $user_id,
                'card_number' => $aValues['card_number'],
                'expiry_date' => $aValues['expiry_date'],
                'cvc' => $aValues['cvc']
            ]);
        }
        return $userdetails;
    }

    public function getservices(Request $request){
        $user_id = $request->user_id; 
        $questions = UserService::whereIn('user_id',[$user_id])->with('userServices')->get();
        return $this->sendResponse(__('Profile Questions Data'), $questions);
    }

    // public function (){

    // }
    
    
    // public function sellerMyprofileqa(Request $request): JsonResponse
    // {
    //     $user_id = $request->user_id; 
    //     $aValues = $request->all();
    //     $profileQues = ProfileQA::where('user_id',$user_id)->where('questions',$aValues['questions'])->first();
    //     if(isset($profileQues) && $profileQues != ''){
    //         $profileQues->update(['is_autobid' => $autobid]);
    //     }else{
    //         $userdetails = UserDetail::create([
    //             'user_id'  => $user_id,
    //             'is_autobid' => $autobid
    //         ]);
    //     }
    //     $data = $userdetails;
    //     return $this->sendResponse(__('Autobid switched successfully'),$data );   
    // }

}
