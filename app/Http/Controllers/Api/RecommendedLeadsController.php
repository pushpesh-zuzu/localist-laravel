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
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Models\Invoice;
use App\Models\RecommendedLead;
use App\Models\AutobidStatusLog;
use App\Models\NotificationSetting;
use App\Notifications\BrowserNotification;
use App\Models\Postcode;

use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use \Carbon\Carbon;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoEmails;
use App\Helpers\Zoho\ZohoFinance;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoLeads;
use App\Helpers\Zoho\ZohoPurchasedLeads;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Log;
use App\Services\LeadService;
use App\Helpers\Zoho\ZohoQuoteRequest;
class RecommendedLeadsController extends Controller
{

    public function switchAutobid(Request $request)
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
        if(empty($isDataExists)){
            $user = User::where('id',$request->user_id)->first();
            UserDetail::create([
                'user_id'  => $user->id,
                'is_autobid'  =>1,
                'billing_contact_name' => $user->name,
                'billing_phone' => $user->phone,
                'billing_vat_register' => 1,
            ]);

            $dataAb['user_id'] = $user->id;
            $dataAb['action'] = 'enabled';
            AutobidStatusLog::insertGetId($dataAb);
            $isDataExists = UserDetail::where('user_id',$request->user_id)->first();
        }

        if(!empty($isDataExists)){
            return $this->sendResponse(__('Autobid Data'), [
                'isautobid' => $isDataExists->is_autobid
            ]);
        }
        return $this->sendError('User not found');
    }

    public function getRepliesList(Request $request)
    {

        $buyerId = $request->user_id;
        $leadid = $request->lead_id;
        
        $result = [];

        if (!empty($leadid)) {
            $lead = LeadRequest::find($leadid); // get lead created_at
            if (!$lead) {
                return $this->sendResponse('Lead not found', []);
            }

            // Fetch all matching bids
            $bids = RecommendedLead::where('buyer_id', $buyerId)
                ->where('lead_id', $leadid)
                // ->where('distance','!=', 0)
                ->orderBy('distance', 'ASC')
                ->get();
            // print_r($bids->toArray());exit;


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
                        ->where(function ($query) use ($buyerId, $bid){
                            $query->where(function ($q) use ($buyerId, $bid){
                                $q->where('from_user_id', $bid->seller_id)
                                ->where('to_user_id', $buyerId);
                            })->orWhere(function ($q) use ($buyerId, $bid){
                                $q->where('from_user_id', $buyerId)
                                ->where('to_user_id', $bid->seller_id);
                            });
                        })
                        ->whereIn('contact_type', $contactTypes)
                        ->orderBy('created_at', 'asc')
                        ->first();
                    $quickToRespond = 0;
                    if ($firstResponse) {
                        $leadTime = Carbon::parse($lead->created_at)->setTimezone('Europe/London');
                        $createdAt = $firstResponse->created_at->copy()->setTimezone('Europe/London');

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
                    $sellerData['lead_id'] = $bid->lead_id;

                    $sellerData['activty_log'] = [
                        'log' => !empty($firstResponse->activity_name) ? $firstResponse->activity_name : 'Requested a Quote',
                        'date_time' => !empty($firstResponse->created_at) ? date('d M Y, H:i', strtotime($bid->created_at)) : date('d M Y, H:i')
                    ];
                    unset($firstResponse);
                    $result[] = $sellerData;
                }
            }
        }

        return $this->sendResponse(__('AutoBid Data'), $result);
    }

    public function getManualLeads(Request $request, LeadService $leadService){
        
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);
        $responseTimeFilter = $request->responseTimeFilter ?? [];
        $ratingFilter = $request->rating ?? [];

        // check if request postcode exists in postcode table, if not then get coordinates and save
        $reqPostcode = CustomHelper::normalizeInUKPostcodeFormate($lead->postcode);
        if(!empty($reqPostcode)){
            $dbPostcode = Postcode::where('postcode', $reqPostcode)->first();
            if(empty($dbPostcode)){
                $tempCord = CustomHelper::getCoordinates($reqPostcode);
                if(!empty($tempCord)){
                    $cordArr = json_decode($tempCord, true);
                    if(!empty($cordArr['lat']) && !empty($cordArr['lng'])){
                        Postcode::insertGetId([
                            'postcode' => $reqPostcode,
                            'latitude' => $cordArr['lat'],
                            'longitude' => $cordArr['lng'],
                        ]);
                    }
                }
            }
        }

        $result = $leadService->getAllSellers($lead);
        $result['response']['repliesListCount'] = RecommendedLead::where('buyer_id', $request->user_id)->where('lead_id', $lead->id)->count();
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

            $finalSorted = $finalSorted->map(function ($item){ 
                return CustomHelper::maskLead((object)$item); 
                
            });

            $result['response']['sellers'] = $finalSorted->values()->toArray();
        }else{
            return $this->sendResponse('No Seller Found!', [$result['response']]);
        }

        return $this->sendResponse('Your Matches List', [$result['response']]);
    }

    public function closeLeads(Request $request, LeadService $leadService){
        // unpause auto bid after 7 days
        $this->unpauseAutobidAfter7Days();
        //close leads after n days
        $this->leadCloseAfter21Days();

        $isSiteLive = CustomHelper::setting_value("is_site_live", 'no');
        
        // if($isSiteLive == 'yes'){
        //     // when site is in live
        //     print_r("old autobid system as site is live");
        //     $this->placeAutobidOld($request, $leadService);
        // }else{
        //     // when site is in development
        //     print_r("new autobid system as site is in development phase");
        //     $this->placeAutobid($request, $leadService);
        // }
    }

    public function placeAutobid($request, $leadService){
        // -----------------------------------------------------------------------------
        // AUTOBID ENGINE
        // -----------------------------------------------------------------------------
        // This logic places AUTO bids only (manual bids are NOT restricted by this code)
        //
        // Client rules enforced here:
        // 1) Max X AUTO bids per lead (quote request)
        // 2) Max Y AUTO bids per seller per day (implemented as rolling N-hour batch)
        // 3) Limits are configurable globally and overridable per seller
        // -----------------------------------------------------------------------------

        
        // Delay autobid execution until lead is at least N minutes old
        $startBidAfter = CustomHelper::setting_value("start_autobid_after", 5);

        // Number of days after plan purchase before autobidding is allowed for a seller
        $autoBidAfterPlanPurchseDays = (int) CustomHelper::setting_value("autobid_after_plan_purchase_days", 7);
        
        // Fetch leads eligible for autobidding:
        // - Lead is open
        // - Autobid is enabled for the lead
        // - Lead owner form is completed
        // - Lead is older than the autobid delay window
        $leads = LeadRequest::join('users', 'users.id', '=', 'lead_requests.customer_id')
            ->where('lead_requests.closed_status', 0)
            ->where('lead_requests.should_autobid', 1)
            ->where('users.form_status', 1)
            ->where('lead_requests.created_at', '<=', Carbon::now()->subMinutes($startBidAfter))
            ->select('lead_requests.*') // important so you only get lead fields back
            ->get();

        // Maximum AUTO bids allowed per lead (quote request)
        $autobidPerLeadLimit = CustomHelper::setting_value("autobid_per_lead_limit", 3);
        
        // Maximum AUTO bids allowed per Lead Buyer per batch (default per day)
        $autobidPerLeadBuyerLimit = CustomHelper::setting_value("autobid_per_lead_buyer_limit", 2);
        foreach($leads as $lead){
            // Get all sellers eligible to bid for this lead
            $sellers = $leadService->getAllSellers($lead);            

            if(!empty($sellers['response']['sellers'])){
                foreach($sellers['response']['sellers'] as $s){
                    
                    // -----------------------------------------------------------------
                    // PER-LEAD AUTOBID LIMIT CHECK
                    // -----------------------------------------------------------------
                    // Count AUTO bids already placed for this lead.
                    // Manual bids and request replies are intentionally excluded.
                    //
                    // NOTE:
                    // This count is recalculated for EACH seller to ensure that
                    // once the lead-level autobid limit is reached, no further
                    // autobids are placed during the same cron run.
                    // -----------------------------------------------------------------
                    $leadAutobidCount = $count = \DB::table('recommended_leads')
                        ->where('lead_id', $lead->id)
                        ->where('purchase_type', 'Autobid')
                        ->count();
                    if($leadAutobidCount < $autobidPerLeadLimit){

                        $leadCreatedAt = Carbon::parse($lead->created_at);
                        $sellerRegisteredAt = Carbon::parse($s->user_created_time);

                        // Seller must purchase a plan to be eligible for autobidding
                        $invoice = Invoice::where('user_id', $s->id)->first();
                        if(!empty($invoice)){

                            // -----------------------------------------------------------------
                            // Seller eligibility conditions:
                            // 1) Seller account must predate the lead
                            // 2) Seller must have an active plan older than the configured
                            //    autobid-after-purchase window
                            // -----------------------------------------------------------------
                            
                            $planPurchaseDate = Carbon::parse($invoice->created_at);

                            //start autobid process only if current date id more than autoBidAfterPlanPurchseDays of plan purchase date and lead created date is greater than seller registered date
                            if(
                                $leadCreatedAt->greaterThan($sellerRegisteredAt) 
                                &&
                                Carbon::now()->greaterThanOrEqualTo($planPurchaseDate->copy()->addDays($autoBidAfterPlanPurchseDays))
                            ){
                                // Default per-seller autobid limit and batch duration
                                $autobidLimit = $autobidPerLeadBuyerLimit;
                                $batchHourLimit = CustomHelper::setting_value("autobid_batch_hour_limit", 24);                           
                                
                                // Load seller-specific overrides, if present
                                $userDetail = UserDetail::where('user_id', $s->id)->first();
                                
                                if(!empty($userDetail)){

                                    //get seller specific autobid limit, if seller specific autobid limit is not there use general autobid limit
                                    $sellerAutobidLimit = $userDetail->autobid_limit;
                                    if(!empty($sellerAutobidLimit) && $sellerAutobidLimit > 0){
                                        $autobidLimit = $sellerAutobidLimit;
                                    }

                                    //check per seller wise bactch hour, if seller batch hour is presenet then use general batch hour limit setting
                                    $sellerBatchHourLimit = $userDetail->autobid_batch_hour_limit;
                                    if(!empty($sellerBatchHourLimit) && $sellerBatchHourLimit > 0){
                                        $batchHourLimit = $sellerBatchHourLimit;
                                    }
                                }
                                
                                // -----------------------------------------------------------------
                                // PER-SELLER PER-BATCH AUTOBID LIMIT CHECK
                                // -----------------------------------------------------------------
                                // Batch is a rolling time window (not calendar day).
                                // Default: 24 hours, configurable globally and per seller.
                                // -----------------------------------------------------------------        
                                $batch = CustomHelper::getCurrentAutobidBatch($s->id, $batchHourLimit);
                                if(!empty($batch)){
                                    $dateStart = Carbon::parse($batch['start']);
                                    $dateEnd   = Carbon::parse($batch['end']);

                                    // Count AUTO bids placed by this seller in the current batch
                                    $sellerAutobidCount = \DB::table('recommended_leads')
                                        ->where('seller_id', $s->id)
                                        ->where('purchase_type', 'Autobid')
                                        ->whereBetween('created_at', [$dateStart, $dateEnd])
                                        ->count();

                                    if($sellerAutobidCount < $autobidLimit){
                                        // Place AUTO bid
                                        $request->replace($request->only(['abc']));
                                        $request['bidtype'] = 'autobid';
                                        $request['lead_id'] = $lead->id;
                                        $request['service_id'] = $lead->service_id;
                                        $request['distance'] = $s->distance;
                                        $request['seller_id'] = $s->id;
                                        $request['user_id'] = $lead->customer_id;
                                        $this->addManualBid($request, $leadService);
                                    }
                                }
                            }
                        }
                    }

                    
                }
            }
        }
    }


    private function placeAutobidOld($request, $leadService){
        //start getting auto bid leads
        //get Leads which are N minutes older
        $startBidAfter = CustomHelper::setting_value("start_autobid_after", 5);
        //variable to atke count after how manys of plan purchase leads autobid should start
        $afterPlanPurchseDays = CustomHelper::setting_value("after_plan_purchase_days", 7);
        //get Leads which are created N munites before and not closed and autobid is open for that lead
        $leads = LeadRequest::join('users', 'users.id', '=', 'lead_requests.customer_id')
            ->where('lead_requests.closed_status', 0)
            ->where('lead_requests.should_autobid', 1)
            ->where('users.form_status', 1)
            ->where('lead_requests.created_at', '<=', Carbon::now()->subMinutes($startBidAfter))
            ->select('lead_requests.*') // important so you only get lead fields back
            ->get();

        $autobidLimit = CustomHelper::setting_value("autobid_limit", 3);
        foreach($leads as $lead){
            $sellerInserted = 0;

            $sellers = $leadService->getAllSellers($lead);

            if(!empty($sellers['response']['sellers'])){
                foreach($sellers['response']['sellers'] as $s){
                    $leadCreatedAt = Carbon::parse($lead->created_at);
                    $sellerRegisteredAt = Carbon::parse($s->user_created_time);

                    //check plan purchase days
                    $invoice = Invoice::where('user_id', $s->id)->first();
                    if(!empty($invoice)){
                        $planPurchaseDate = Carbon::parse($invoice->created_at);

                        //start autobid process only if current date id more than afterPlanPurchseDays of plan purchase date and lead created date is greater than seller registered date
                        if(
                            $leadCreatedAt->greaterThan($sellerRegisteredAt) 
                            &&
                            Carbon::now()->greaterThanOrEqualTo($planPurchaseDate->copy()->addDays($afterPlanPurchseDays))
                        ){
                            $batch = CustomHelper::getCurrentAutobidBatchSevenDayWise($s->id);

                            if(!empty($batch)){
                                $dateStart = Carbon::parse($batch['start'])->startOfDay();
                                $dateEnd   = Carbon::parse($batch['end'])->endOfDay();
                                $count = \DB::table('recommended_leads')
                                    ->where('seller_id', $s->id)
                                    ->where('purchase_type', 'Autobid')
                                    ->whereBetween('created_at', [$dateStart, $dateEnd])
                                    ->count();

                                if($count < $autobidLimit){
                                    $request->replace($request->only(['abc']));
                                    $request['bidtype'] = 'autobid';
                                    $request['lead_id'] = $lead->id;
                                    $request['service_id'] = $lead->service_id;
                                    $request['distance'] = $s->distance;
                                    $request['seller_id'] = $s->id;
                                    $request['user_id'] = $lead->customer_id;
                                    $this->addManualBid($request, $leadService);
                                }
                            }
                        }
                    }
                    
                }
            }
        }
    }



    public function unpauseAutobidAfter7Days()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $sellers = UserDetail::where('is_autobid','1')
            ->where('autobid_pause','1')
            ->pluck('id')
            ->toArray();
        foreach($sellers as $s){
            $latestPaused = AutobidStatusLog::where('user_id', $s)
                ->where('action', 'paused')
                ->latest('id')
                ->first();
            if ($latestPaused) {
                $pDate = Carbon::parse($latestPaused->created_at)->toDateString();
                $ok = $isSixDaysBefore = Carbon::parse($pDate)->equalTo(Carbon::today()->subDays(6));
                if($ok){
                    UserDetail::where('id', $s)->update([
                        'autobid_pause' => 0
                    ]);
                    $data['user_id'] = $s;
                    $data['action'] = 'resumed';
                    AutobidStatusLog::insertGetId($data);
                }
            }
        }
    }



    public function leadCloseAfter21Days(){
        $closeLeadsAfterDays = CustomHelper::setting_value("close_leads_after_days", 14);
        $leadsToClose = LeadRequest::where('status', 0)
            ->where('closed_status', 0)
            ->where('created_at', '<', Carbon::now()->subDays($closeLeadsAfterDays)->toDateString())
            ->get();

        foreach ($leadsToClose as $lead) {
            $lead->closed_status = 1; // Mark as closed
            // $lead->status = 'expired'; // Update status to expired
            $lead->save();
        }
    }

    public function getRatingFilter(Request $request, LeadService $leadService)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $ratings = [];

        for ($i = 1; $i <= 5; $i++) {
            // Simulate rating filter exactly like the `ratingFilter` method
            // $result = $leadService->getAllSellers($lead, ['rating' => $i]);

            $ratings[] = [
                'label' => $i == 5 ? 'only' : '& up',
                'value' => $i,
                // 'count' => count($result['response']['sellers']),
            ];
        }

         // Handle sellers with no rating (avg_rating is null)
        // $resultNoRating = $leadService->getAllSellers($lead, ['rating' => 'no_rating']);

        $ratings[] = [
            'label' => 'No rating',
            'value' => 'no_rating',
            // 'count' => count($resultNoRating['response']['sellers']),
        ];

        return $this->sendResponse(__('Filtered Data by Rating'), [$ratings]);
    }

    public function ratingFilter(Request $request, LeadService $leadService)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $rating = $request->rating;

        // Accept 1-5 or "no_rating" or "all"
        if (!in_array($rating, ['1', '2', '3', '4', '5', 'no_rating', 'all'], true)) {
            return $this->sendError(__('Invalid rating value'), 400);
        }

        // Cast numeric strings to int
        $selectedRating = is_numeric($rating) ? (int) $rating : $rating;

        // Pass to your filtering logic
        $result = $leadService->getAllSellers($lead, ['rating' => $selectedRating]);
        $result['response']['sellers'] = $result['response']['sellers']->values()->toArray();

        return $this->sendResponse(__('Filtered Data by Rating'), [$result['response']]);
    }

    public function sortByLocation(Request $request, LeadService $leadService)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $distanceOrderRaw = $request->distance_order;

        $result = $leadService->getAllSellers($lead, [ 'distance_order' => $distanceOrderRaw]);
        $result['response']['sellers'] = $result['response']['sellers']->values()->toArray();

        return $this->sendResponse(__('Sorting by distance'), [$result['response']]);
    }

    public function responseTimeFilter(Request $request, LeadService $leadService)
    {
        $lead = LeadRequest::find($request->lead_id);
        if (!$lead) return $this->sendError(__('No Lead found'), 404);

        $responseTimeFilter = $request->response_time; // Expected: '10_min', '1_hour', '6_hour', '24_hour'
        $result = $leadService->getAllSellers($lead, ['response_time' => $responseTimeFilter]);
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
        $isLeadAlreadyExclusive = (int) LeadRequest::where('id', $aVals['lead_id'])->value('is_exclusive');
        if($isLeadAlreadyExclusive){
            return $this->sendError("This is already a exclusive lead", 404);
        }

        $leadTime = LeadRequest::where('id',$aVals['lead_id'])->pluck('created_at')->first();
        $isExclusive = (isset($aVals['is_exclusive']) && (int)$aVals['is_exclusive'] === 1) ? 1 : 0;
        $creditScore = (int) LeadRequest::where('id', $aVals['lead_id'])->value('credit_score');
        if ($isExclusive) {
            $creditScore = (int) ceil($creditScore * 2.5);
        }

        $categoryId = LeadRequest::where('id',$aVals['lead_id'])->value('service_id');

        $leadSlotCountGeneral = (int) CustomHelper::setting_value("lead_slot_count", 5);
        $leadSlotCount = $leadSlotCountGeneral;

        // if category has lead_slot_count > 0 then use category slot count else use general slot count
        $leadSlotCountSector = Category::where('id', $categoryId)->value('lead_slot_count');
        if ($leadSlotCountSector !== null && (int)$leadSlotCountSector > 0) {
            $leadSlotCount = (int) $leadSlotCountSector;
        }

        $sellerId = ($aVals['bidtype'] == 'purchase_leads') ? $aVals['user_id'] : $aVals['seller_id'];
        $buyerId = ($aVals['bidtype'] == 'purchase_leads') ? $aVals['buyer_id'] : $aVals['user_id'];

        $totalCredit = User::where('id', $sellerId)->value('total_credit');
        //check if seller has enough credits
        if($creditScore > $totalCredit){
            return $this->sendError(__("Seller don't have sufficient balance"), 404);
        }
        //check if same seller has placed bid or not for this lead
        $bidCheck = RecommendedLead::where('lead_id', $aVals['lead_id'])
            ->where('service_id', $aVals['service_id'])
            ->where('buyer_id', $buyerId)
            ->where('seller_id',$sellerId)
            ->first();
        if(!empty($bidCheck)){
            return $this->sendError('Contact already exists for this seller', 404);
        }

        // check if N bids has been placed on this lead or not
        $totalBidCount = RecommendedLead::where('lead_id', $aVals['lead_id'])
            ->where('service_id', $aVals['service_id'])
            ->count();
        if($totalBidCount >= $leadSlotCount){
            LeadRequest::where('id', $aVals['lead_id'])->update([
                'should_autobid'  => 0,
                'closed_status' => 1
            ]);
            $word = CustomHelper::numberToWords($leadSlotCount);
            return $this->sendError($word .' slots has been full! No more contacts can be made.', 404);
        }
        $logInfo = "";
        $trInfo = "";
        $pType = "";
        $seller = User::where('id', $sellerId)->first();
        $sellerName = $seller->name;
        // $sellerName = User::where('id',$sellerId)->value('name');
        $buyerName = User::where('id',$buyerId)->value('name');
        if($aVals['bidtype'] == 'reply'){

            $pType = "Request Reply";
            $trInfo = $creditScore . " credit deducted for Request Reply";
            self::addActivityLog($buyerId, $sellerId,$aVals['lead_id'], $buyerName ." contacted " .$sellerName, "Request Reply", $leadTime);

        }else if($aVals['bidtype'] == 'purchase_leads'){
            $pType = "Manual Bid";
            $trInfo = $creditScore . " credit deducted for Contacting to Customer";
            self::addActivityLog($aVals['user_id'],$aVals['buyer_id'],$aVals['lead_id'],$sellerName .' contacted '. $buyerName, "Manual Bid", $leadTime);
        }else{
            // for autobid
            $pType = "Autobid";
            $trInfo = $creditScore . " credit deducted for Autobid";
            self::addActivityLog($buyerId, $sellerId,$aVals['lead_id'], "Autobid placed for " .$sellerName, "Autobid", $leadTime);
        }
        $bids = RecommendedLead::create([
            'service_id' => $aVals['service_id'],
            'seller_id' => $sellerId,
            'buyer_id' => $buyerId, //buyer
            'lead_id' => $aVals['lead_id'],
            'bid' => $creditScore,
            'distance' => $aVals['distance'],
            'purchase_type' => $pType
        ]);



        //deduct credit
        DB::table('users')->where('id', $sellerId)->decrement('total_credit', $creditScore);
        //create transaction log
        $tId =CustomHelper::createTrasactionLog($sellerId, 0, $creditScore, $trInfo, 1, 1, $error_response='');

        if($isExclusive){
            LeadRequest::where('id',$aVals['lead_id'])->update([
                'is_exclusive' => 1,
                'status'=>'pending'
            ]);
        }else{
            LeadRequest::where('id',$aVals['lead_id'])->update(['status'=>'pending']);
        }

        

        //remove from save for later
        SaveForLater::where('seller_id',$sellerId)
            ->where('user_id',$buyerId)
            ->where('lead_id',$aVals['lead_id'])
            ->delete();


        $bidId = $bids->id;
        if($bidId){
            CustomHelper::runInBackground(function() use ($sellerId,$bidId,$tId) {	
                app(ZohoPurchasedLeads::class)->integratePurchaseLeads($sellerId, $bidId);
                app(ZohoFinance::class)->integratePurchaseHistory($sellerId, $tId);
            });
        }

          $requestLeadId=$aVals['lead_id'] ?? null;
            if (!empty($requestLeadId)) {
                CustomHelper::runInBackground(function() use ($requestLeadId) {
                    app(ZohoQuoteRequest::class)->updateZohoQuoteStatus($requestLeadId);
                });
            }

        if($aVals['bidtype'] == 'reply'){
            CustomHelper::runInBackground(function() {
                app(self::class)->sendLeadRequestReply();
            });            
        }

        return $this->sendResponse('Your request has been sent.');
    }

    public function addMultipleManualBid(Request $request){
        $aVals = $request->all();
        $request['bidtype'] = 'reply';
        $buyerId = $aVals['user_id'];
        $leadId = $aVals['lead_id'];
        $inserted = 0;

        // echo "<pre>";
        // print_r($aVals);
        // exit;

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
        return $this->sendResponse('Your requests have been sent.', [
            'inserted_count' => $inserted,
            'total_now' => RecommendedLead::where('lead_id', $leadId)->count()
        ]);
    }


public function sendLeadRequestReply()
{
    $totalUnsentLeadEmails = 0;
    $leadPref = new LeadService();

    User::whereNotNull('zoho_record_id')
        ->where('form_status', 1)
        ->where('user_type', 1)
        ->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
        ->where('recommended_leads.purchase_type', 'Request Reply')
        ->select('users.id', 'users.total_credit')
        ->distinct() // Important to avoid duplicate sellers
        ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {

            foreach ($sellersChunk as $seller) {

                $leadIds = RecommendedLead::where('seller_id', $seller->id)
                    ->where('purchase_type', 'Request Reply')
                    ->join('lead_requests', 'recommended_leads.lead_id', '=', 'lead_requests.id')
                    ->orderBy('lead_requests.id', 'desc')
                    ->pluck('lead_requests.id')
                    ->toArray();

                if (empty($leadIds)) {
                    continue;
                }

                // Filter out already emailed leads
                $unsentLeadIds = array_filter($leadIds, function ($leadId) use ($seller) {
                    return !EmailLog::where('user_id', $seller->id)
                        ->where('lead_id', $leadId)
                        ->where('setting_name', 'New Lead - Request Reply')
                        ->exists();
                });



                if (!empty($unsentLeadIds)) {
                    // Send all leads in one email
                    $result = ZohoEmails::sendGroupedRequestReplyLeads($seller->id, $unsentLeadIds);

                     Log::info('Zoho Email for request reply', [
                                'user_id' => $seller->id,
                                'response' => $result,
                            ]);
                    $totalUnsentLeadEmails++;
                }
            }
        });

    unset($leadPref);

    return response()->json([
        'status' => 'success',
        'unsent_lead_emails' => $totalUnsentLeadEmails,
        'timestamp' => now()->toDateTimeString(),
    ]);
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
        $leadtime = Carbon::parse($leadtime)->setTimezone('Europe/London');
        $createdAt = $activity->created_at->copy()->setTimezone('Europe/London');

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
