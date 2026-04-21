<?php

namespace App\Http\Controllers;

use App\Exports\LoginHistoryListExport;
use Illuminate\Http\Request;
use App\Models\UserServiceLocation;
use App\Models\UserAccreditation;
use App\Models\UserServiceDetail;
use App\Models\SuggestedQuestion;
use App\Models\PurchaseHistory;
use App\Models\LeadPrefrence;
use App\Models\LoginHistory;
use App\Models\UserService;
use App\Models\LeadRequest;
use App\Models\UserDetail;
use App\Models\Category;
use App\Models\User;
use App\Models\Plan;
use App\Models\RecommendedLead;
use Illuminate\Support\Facades\DB;
use App\Models\AbandonedUser;
use App\Models\ContactUs;
use App\Models\PlanHistory;
use App\Models\Review;
use App\Models\CustomReview;
use App\Exports\SellerCompleteListExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoServiceLocations;
use App\Helpers\Zoho\ZohoLeadBuyers;
use App\Helpers\Zoho\ZohoQuestionAnswer;
use App\Helpers\Zoho\ZohoService;
use App\Helpers\Zoho\ZohoFinance;
use App\Helpers\Zoho\ZohoPurchasedLeads;
use App\Helpers\Zoho\ZohoQuoteRequest;
use App\Models\Invoice;
use Yajra\Datatables\Datatables;
use Carbon\Carbon;

class SellerController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        abort_if(!auth()->user()->can('leadbuyers.viewlist'), 403, __('User does not have the right permissions.'));

        $query = User::whereIn('user_type', [1, 3])
            ->where('form_status', 1)
            ->with('lastLogin'); // eager load latest login
        // ->orderBy('id', 'DESC');

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $aRows = $query->get();

        $plans = Plan::where('status', 1)
            ->where('plan_type', 'normal')
            ->orderBy('name', 'ASC')
            ->distinct()
            ->pluck('name');


        return view('seller.complete', compact('aRows', 'plans'));
    }


    public function contactForm(Request $request)
    {
        abort_if(!auth()->user()->can('leadbuyerscontact.viewlist'), 403, __('User does not have the right permissions.'));

        $query = ContactUs::where('user_type', 2);
        // ->orderBy('id', 'DESC');

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $aRows = $query->get();
        return view('seller.contact_form_list', compact('aRows'));
    }

    public function viewContactForm(string $id)
    {
        abort_if(!auth()->user()->can('leadbuyerscontact.view-details'), 403, __('User does not have the right permissions.'));
        DB::table('contact_us')
            ->where('id', $id)
            ->update(['status' => 1]);

        $aRows = DB::table('contact_us')->where('user_type', 2)->where('id', $id)->first();
        return view('seller.contact_view', compact('aRows'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $type, string $id)
    {
        if ($type === 'abandoned') {
            abort_if(!auth()->user()->canAny(['leadbuyers.incomplete-view-details']), 403, __('User does not have the right permissions.'));
            $aRows = AbandonedUser::where('id', $id)->with(['details'])->first();
        } else {
            abort_if(!auth()->user()->canAny(['leadbuyers.view-details']), 403, __('User does not have the right permissions.'));
            $aRows = User::where('id', $id)->with(['details'])->first();
        }
        return view('seller.view', compact('aRows'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        if ($request->type == 'abandoned') {
            \DB::table('abandoned_users')->where('id', $id)->delete();
            return redirect()->route('seller.incomplete')
                ->with('success', 'Abandoned Seller deleted successfully.');
        }

        \DB::table('users')->where('id', $id)->delete();
        \DB::table('user_accreditations')->where('user_id', $id)->delete();
        \DB::table('user_card_details')->where('user_id', $id)->delete();
        \DB::table('user_details')->where('user_id', $id)->delete();
        \DB::table('user_response_times')->where('seller_id', $id)->delete();
        \DB::table('user_services')->where('user_id', $id)->delete();
        \DB::table('user_service_locations')->where('user_id', $id)->delete();
        \DB::table('activity_logs')->where('from_user_id', $id)->orWhere('to_user_id', $id)->delete();
        \DB::table('invoices')->where('user_id', $id)->delete();
        \DB::table('lead_prefrences')->where('user_id', $id)->delete();
        \DB::table('lead_requests')->where('customer_id', $id)->delete();
        \DB::table('login_histories')->where('user_id', $id)->delete();
        \DB::table('plan_histories')->where('user_id', $id)->delete();
        \DB::table('profile_q_a_s')->where('user_id', $id)->delete();
        \DB::table('purchase_histories')->where('user_id', $id)->delete();
        \DB::table('recommended_leads')->where('seller_id', $id)->orWhere('buyer_id', $id)->delete();
        \DB::table('reviews')->where('user_id', $id)->delete();
        \DB::table('save_for_laters')->where('seller_id', $id)->orWhere('user_id', $id)->delete();
        \DB::table('seller_notes')->where('seller_id', $id)->orWhere('buyer_id', $id)->delete();
        \DB::table('suggested_questions')->where('user_id', $id)->delete();
        \DB::table('unique_visitors')->where('seller_id', $id)->orWhere('buyer_id', $id)->delete();
        \DB::table('autobid_status_logs')->where('user_id', $id)->delete();
        \DB::table('email_logs')->where('user_id', $id)->delete();

        return redirect()->route('seller.index')
            ->with('success', 'Seller deleted successfully.');
    }

    public function incompletelist(Request $request)
    {
        abort_if(!auth()->user()->can('leadbuyers.incomplete-viewlist'), 403, __('User does not have the right permissions.'));

        $query = AbandonedUser::whereIn('user_type', [1, 3])
            ->where('form_status', 0);
        // ->orderBy('id', 'DESC');

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $aRows = $query->get();

        return view('seller.incomplete', compact('aRows'));
    }

    public function sellerServices($userid)
    {
        abort_if(!auth()->user()->can('leadbuyers.services'), 403, __('User does not have the right permissions.'));
        $user = User::where('id', $userid)->pluck('name')->first();
        $serviceId = UserService::where('user_id', $userid)->pluck('service_id')->toArray();
        $aRows = Category::whereIn('id', $serviceId)->get();
        foreach ($aRows as $key => $value) {
            $value['locations'] = UserServiceLocation::whereIn('user_id', [$userid])->whereIn('service_id', [$value->id])->select(['miles', 'postcode', 'nation_wide'])->get()->toArray();
            $value['leadpref'] = LeadPrefrence::whereIn('service_id', [$value->id])
                ->where('user_id', $userid)
                ->with('serquestions')
                ->get();
            $value['autobid'] = UserDetail::where('user_id', $userid)->pluck('is_autobid')->first();
        }
        return view('seller.services', get_defined_vars());
    }

    public function creditPlans($userid)
    {
        abort_if(!auth()->user()->can('leadbuyers.creditplans'), 403, __('User does not have the right permissions.'));
        $user = User::where('id', $userid)->pluck('name')->first();
        $aRows = PlanHistory::where('user_id', $userid)->get();
        return view('seller.credit_plans', get_defined_vars());
    }

    public function sellerBids($userid)
    {
        abort_if(!auth()->user()->can('leadbuyers.bids'), 403, __('User does not have the right permissions.'));

        // Get recommended leads for the seller
        $recommendedLeads = RecommendedLead::where('seller_id', $userid)->get();

        // Extract buyer and lead IDs
        $buyerIds = $recommendedLeads->pluck('buyer_id')->unique()->toArray();
        $leadIds = $recommendedLeads->pluck('lead_id')->unique()->toArray();

        // Fetch only those leads that are in recommended_leads for this seller
        $leads = LeadRequest::whereIn('id', $leadIds)->orderBy('id', 'DESC')->get();

        // Group by customer_id
        $groupedLeads = $leads->groupBy('customer_id');

        $aRows = [];

        foreach ($groupedLeads as $customerId => $customerLeads) {
            $user = User::find($customerId);

            $aRows[] = [
                'buyer_name' => $user ? $user->name : '',
                'customer_id' => $customerId,
                'leads' => $customerLeads->map(function ($lead) use ($userid) {
                    $lead->service_name = Category::where('id', $lead->service_id)->pluck('name')->first();

                    $lead->purchase_type = RecommendedLead::where('lead_id', $lead->id)
                        ->where('seller_id', $userid)
                        ->pluck('purchase_type')
                        ->first();

                    return $lead;
                })
            ];
        }

        return view('seller.autobid_leads', compact('aRows'));
    }

    public function sellerBids_10_06_25($userid)
    {
        $buyerIds = RecommendedLead::where('seller_id', $userid)->pluck('buyer_id')->unique()->toArray();
        $leads = LeadRequest::whereIn('customer_id', $buyerIds)->orderBy('id', 'DESC')->get();
        // Group all leads by customer_id
        $groupedLeads = $leads->groupBy('customer_id');

        $aRows = [];

        foreach ($groupedLeads as $customerId => $customerLeads) {
            $user = User::find($customerId);

            $aRows[] = [
                'buyer_name' => $user ? $user->name : '',
                'customer_id' => $customerId,
                'leads' => $customerLeads->map(function ($lead)  use ($userid) {
                    $lead->service_name = Category::where('id', $lead->service_id)->pluck('name')->first();

                    // Fetch purchase_type from recommended_leads for this lead and seller
                    $lead->purchase_type = RecommendedLead::where('lead_id', $lead->id)
                        ->where('seller_id', $userid)
                        ->pluck('purchase_type')
                        ->first();
                    return $lead;
                })
            ];
        }

        return view('seller.autobid_leads', compact('aRows'));
    }

    public function sellerAccreditations($userid)
    {
        $aRows = UserAccreditation::where('user_id', $userid)->orderBy('id', 'DESC')->get();
        $user = User::where('id', $userid)->pluck('name')->first();
        return view('seller.seller_accreditations', get_defined_vars());
    }

    public function sellerProfileServices($userid)
    {
        $aRows = UserServiceDetail::where('user_id', $userid)->orderBy('id', 'DESC')->get();
        $user = User::where('id', $userid)->pluck('name')->first();
        return view('seller.seller_services', get_defined_vars());
    }

    public function suggestedQuestions($userid)
    {
        abort_if(!auth()->user()->can('leadbuyers.suggested-questions'), 403, __('User does not have the right permissions.'));
        // $categoryId = SuggestedQuestion::distinct()->pluck('service_id')->toArray();

        // // Fetch only those categories which have questions
        // $aRows = Category::whereIn('id', $categoryId)
        //                 ->where('status', 1)
        //                 ->get();

        // // Attach service questions to each category
        // foreach ($aRows as $key => $value) {
        //     $value['questions'] = SuggestedQuestion::where('service_id', $value->id)->get();
        // }
        $aRows = SuggestedQuestion::where('user_id', $userid)->with('services')->orderBy('service_id')->get();
        $user = User::where('id', $userid)->pluck('name')->first();
        return view('seller.suggested_questions', get_defined_vars());
    }

    public function sellerLogin($userid)
    {
        abort_if(!auth()->user()->can('leadbuyers.loginhistory'), 403, __('User does not have the right permissions.'));

        $aRows =  LoginHistory::where('user_id', $userid)->orderBy('id', 'DESC')->get();
        $user = User::where('id', $userid)->pluck('name')->first();
        return view('seller.login_history', get_defined_vars());
    }





    // public function sellerBids($userid){
    //     $buyerId = RecommendedLead::whereIn('seller_id', [$userid])->pluck('buyer_id')->toArray();
    //     // $leadId = RecommendedLead::whereIn('seller_id', $userid)->pluck(['lead_id'])->toArray();
    //     $aRows = LeadRequest::whereIn('customer_id', $buyerId)->get();
    //     foreach ($aRows as $key => $value) {
    //         $value['buyer_name'] = User::where('id', $value->customer_id)->pluck('name')->first();
    //         $value['service_name'] = Category::where('id', $value->service_id)->pluck('name')->first();
    //     }
    //     return view('seller.autobid_leads', compact('aRows'));
    // }


    public function getCredit($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_credit' => $user->total_credit
            ]
        ]);
    }




    public function sellerSaveCustomReview(Request $request)
    {
        $user_id = $request->user_id;
        $localists_reviews = Review::where('user_id', $user_id)->where('source', 'localists')->count();
        $facebook_reviews = $request->facebook_reviews;
        $google_reviews = $request->google_reviews;
        $trustpilot_reviews = $request->trustpilot_reviews;

        $localists_score = Review::where('user_id', $user_id)->where('source', 'localists')->avg('ratings');
        $facebook_score = $request->facebook_score;
        $google_score = $request->google_score;
        $trustpilot_score = $request->trustpilot_score;

        $average_rating = 0;
        $avgCount = 0;

        $data['user_id'] = $user_id;
        $data['created_at'] = date('y-m-d H:i:s');

        if (!empty($facebook_reviews) && !empty($facebook_score)) {
            CustomReview::where('user_id', $user_id)->where('review_platform', 'facebook')->delete();
            $data['review_platform'] = 'facebook';
            $data['review_count'] = $facebook_reviews;
            $data['ratings'] = $facebook_score;
            CustomReview::insert($data);
            $average_rating += $facebook_score;
            $avgCount++;
        }

        if (!empty($google_reviews) && !empty($google_score)) {
            CustomReview::where('user_id', $user_id)->where('review_platform', 'google')->delete();
            $data['review_platform'] = 'google';
            $data['review_count'] = $google_reviews;
            $data['ratings'] = $google_score;
            CustomReview::insert($data);
            $average_rating += $google_score;
            $avgCount++;
        }

        if (!empty($trustpilot_reviews) && !empty($trustpilot_score)) {
            CustomReview::where('user_id', $user_id)->where('review_platform', 'trustpilot')->delete();
            $data['review_platform'] = 'trustpilot';
            $data['review_count'] = $trustpilot_reviews;
            $data['ratings'] = $trustpilot_score;
            CustomReview::insert($data);
            $average_rating += $trustpilot_score;
            $avgCount++;
        }

        if (!empty($localists_reviews) && !empty($localists_score)) {
            $average_rating += $localists_score;
            $avgCount++;
        }

        if ($avgCount > 0  && $average_rating > 0) {
            $final_avg_rating = $average_rating / $avgCount;
            $data2['avg_rating'] = number_format($final_avg_rating, 1);
            $data2['updated_at'] = date('y-m-d H:i:s');
            User::where('id', $user_id)->update($data2);
        }

        return response()->json(['success' => true, 'message' => 'Custom review saved successfully']);
    }

    public function addCredit(Request $request)
    {
        if (!auth()->user()->can('leadbuyers.add-credit')) {
            return response()->json([
                'success' => false,
                'message' => __('User does not have the right permissions.'),
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'add_credit' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction(); 

        try {

            $user = User::find($request->user_id);
            $user->total_credit += $request->add_credit;
            $user->save();

            // return $request->credit_amount;

            if (!empty($request->creditAmount)) {

                if ($request->includeVat == 'Y') {
                    $vat = $request->creditAmount * 20 / 100;
                    $total_amount = $request->creditAmount + $vat;
                } else {
                    $vat = 0;
                    $total_amount = $request->creditAmount;
                }

                $dataPh['user_id'] = $request->user_id;
                $dataPh['is_topup'] = 0;
                $dataPh['credits'] = $request->add_credit;
                $dataPh['plan_name'] = $request->planName;
                $dataPh['price'] = number_format($request->creditAmount, 2);
                $dataPh['vat'] = number_format($vat, 2);
                $dataPh['total_amount'] = number_format($total_amount, 2);
                $dataPh['created_at'] = date('Y-m-d H:i:s');
                PlanHistory::insertGetId($dataPh);
            }




            $detail = "Manual " . $request->add_credit . " credit added";
            $data['user_id'] = $request->user_id;
            $data['purchase_date'] = date('Y-m-d');
            if (!empty($request->creditAmount)) {
                $data['price'] =  number_format($total_amount, 2);
            } else {
                $data['price'] = 0;
            }

            $data['credits'] = $request->add_credit;
            $data['details'] = $detail;
            $data['payment_type'] = 0;
            $data['error_response'] = null;
            $data['status'] = 1;
            $data['created_at'] = date('Y-m-d H:i:s');

            $purchase = PurchaseHistory::create($data);

            $transactionId = $purchase->id;
            $user_id = $user->id;

            if (!empty($request->creditAmount)) {
                $invoicePrefix = "4152SX7I";
                $invoiceNumber = $invoicePrefix . "-" . $transactionId;
                //Create invoice
                $dataInv['user_id'] = $user_id;
                $dataInv['invoice_number'] = $invoiceNumber;
                $dataInv['details'] = $request->planName;
                $dataInv['period'] = 'One off charge';
                $dataInv['amount'] = number_format($request->creditAmount, 2);
                $dataInv['vat'] = number_format($vat, 2);
                $dataInv['total_amount'] = number_format($total_amount, 2);

                $userDetails = UserDetail::where('user_id', $user_id)->first();
                if (!empty($userDetails->billing_contact_name)) {
                    $dataInv['name'] = $userDetails->billing_contact_name;
                    $dataInv['address'] = $userDetails->billing_address1 . ', ' . $userDetails->billing_address2 . ', ' . $userDetails->billing_city . ' - ';
                    $dataInv['address'] .= $userDetails->billing_postcode;
                    $dataInv['phone'] = $userDetails->billing_phone;
                } else {
                    $dataInv['name'] = $user->name;
                    $dataInv['address'] = ($user->apartment ?? '') . ', ' . (optional($userDetails)->address ?? '') . ', ' . (optional($userDetails)->city ?? '') . ' - ';
                    $dataInv['address'] .= $user->zipcode;
                    $dataInv['phone'] = $user->phone;
                }
                $dataInv['created_at'] = date('Y-m-d H:i:s');
                $invId = Invoice::insertGetId($dataInv);
            }

            DB::commit();

            if ($transactionId) {
                CustomHelper::runInBackground(function () use ($user_id, $transactionId) {
                    app(ZohoFinance::class)->integratePurchaseHistory($user_id, $transactionId);
                });
            }


            return response()->json([
                'success' => true,
                'message' => 'Credit updated successfully!',
                'new_credit' => $user->total_credit
            ]);

        } catch (\Exception $e) {

            DB::rollBack(); 

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getAutobidSettings($userId)
    {
        $user = UserDetail::where('user_id', $userId)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'autobid_limit' => $user->autobid_limit,
                'autobid_batch_hour_limit' => $user->autobid_batch_hour_limit
            ]
        ]);
    }

    public function updateAutobidSettings(Request $request)
    {
        if (!auth()->user()->can('leadbuyers.add-credit')) {
            return response()->json([
                'success' => false,
                'message' => __('User does not have the right permissions.'),
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'autobid_limit' => 'required|numeric|min:0',
            'autobid_batch_hour_limit' => 'required|numeric|min:0',
        ]);

        $user = UserDetail::where('user_id', $request->user_id)->first();
        $user->autobid_limit = $request->autobid_limit;
        $user->autobid_batch_hour_limit = $request->autobid_batch_hour_limit;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Autobid settings updated successfully!',
            'data' => [
                'autobid_limit' => $user->autobid_limit,
                'autobid_batch_hour_limit' => $user->autobid_batch_hour_limit
            ]
        ]);
    }



    public function exportCompleteSellerExcel(Request $request)
    {

        return Excel::download(
            new SellerCompleteListExport(
                $request->start_date,
                $request->end_date,
                $request->search
            ),
            'seller_complete_list.xlsx'
        );
    }

    public function exportCompleteSellerCsv(Request $request)
    {
        return Excel::download(
            new SellerCompleteListExport(
                $request->start_date,
                $request->end_date,
                $request->search
            ),
            'seller_complete_list.csv'
        );
    }




    public function sellerSendToZoho($type = null, $userId)
    {
        try {

            if ($type == 'abandoned') {
                // 🔥 Run abandoned integration in background
                CustomHelper::runInBackground(function () use ($userId) {
                    app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($userId, 'abandon');
                });
            } else {

                // 🔥 Main integration (non-abandoned)
                app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($userId);


                $services = UserService::where('user_id', $userId)->get();
                if ($services->isNotEmpty()) {
                    CustomHelper::runInBackground(function () use ($userId, $services) {
                        $zohoService = app(ZohoService::class);

                        foreach ($services as $service) {
                            if ($service->zoho_service_id === null || $service->zoho_service_id === '') {
                                // Create new Zoho record
                                $zohoService->integrateService($userId, [$service->id]);
                            } else {

                                // Update Zoho record
                                $zohoService->updateZohoServiceAssign(
                                    $userId,
                                    $service->id,
                                    $service->zoho_service_id
                                );
                            }
                        }
                    });
                }

                /**
                 * -------------------------------------
                 *  SERVICE LOCATION INTEGRATION
                 * -------------------------------------
                 */
                $serviceLocations = UserServiceLocation::where('user_id', $userId)->get();

                if ($serviceLocations->isNotEmpty()) {
                    CustomHelper::runInBackground(function () use ($userId, $serviceLocations) {
                        $zohoService = app(ZohoServiceLocations::class);

                        foreach ($serviceLocations as $location) {
                            if ($location->zoho_location_id === null || $location->zoho_location_id === '') {
                                // Create new Zoho record
                                $zohoService->integrateServiceLocations($userId, [$location->id]);
                            } else {

                                // Update existing Zoho record
                                $zohoService->updateZohoAssignServiceLocation(
                                    $userId,
                                    $location->id,
                                    $location->zoho_location_id
                                );
                            }
                        }
                    });
                }

                /**
                 * -------------------------------------
                 *  SERVICE QUESTION ANSWER (QA)
                 * -------------------------------------
                 */
                $serviceIds = LeadPrefrence::where('user_id', $userId)
                    ->get(['service_id', 'zoho_question_id'])
                    ->unique('service_id')
                    ->toArray();

                if (!empty($serviceIds)) {
                    CustomHelper::runInBackground(function () use ($userId, $serviceIds) {

                        $zohoQA = app(ZohoQuestionAnswer::class);

                        foreach ($serviceIds as $item) {

                            \Log::info("Processing QA Service", [
                                'user_id' => $userId,
                                'service_id' => $item['service_id'],
                                'zoho_question_id' => $item['zoho_question_id']
                            ]);

                            if ($item['zoho_question_id'] === null || $item['zoho_question_id'] === '') {

                                // For integrate, pass ARRAY exactly as function expects
                                $zohoQA->integrateServiceQa($userId, [$item['service_id']]);
                            } else {

                                $zohoQA->updateZohoAssignServiceQa(
                                    $userId,
                                    $item['service_id'],
                                    $item['zoho_question_id']
                                );
                            }
                        }

                        \Log::info("QA Background Sync Finished", [
                            'user_id' => $userId
                        ]);
                    });
                }

                /**
                 * -------------------------------------
                 *  SERVICE LOCATION INTEGRATION
                 * -------------------------------------
                 */
                $recommendedLeads = RecommendedLead::where('seller_id', $userId)->get();

                if ($recommendedLeads->isNotEmpty()) {
                    CustomHelper::runInBackground(function () use ($userId, $recommendedLeads) {
                        $zohoService = app(ZohoPurchasedLeads::class);

                        foreach ($recommendedLeads as $recm) {
                            $zohoService->integratePurchaseLeads($userId, $recm->id);
                            $requestLeadId = $recm->lead_id ?? null;
                            if (!empty($requestLeadId)) {
                                CustomHelper::runInBackground(function () use ($requestLeadId) {
                                    app(ZohoQuoteRequest::class)->updateZohoQuoteStatus($requestLeadId);
                                });
                            }
                        }
                    });
                }



                $purchaseHistory = PurchaseHistory::where('user_id', $userId)->get();
                if ($purchaseHistory->isNotEmpty()) {
                    CustomHelper::runInBackground(function () use ($userId, $purchaseHistory) {
                        $zohoPH = app(ZohoFinance::class);
                        foreach ($purchaseHistory as $phi) {
                            if ($phi->zoho_finance_id === null || $phi->zoho_finance_id === '') {
                                // Create new Zoho record
                                $zohoPH->integratePurchaseHistory($userId, $phi->id);
                            } else {
                                // Update existing Zoho record
                                $zohoPH->updateZohoPurchaseHistory(
                                    $userId,
                                    $phi->id,
                                    $phi->zoho_finance_id
                                );
                            }
                        }
                    });
                }
            }


            return back()->with('success', 'Seller  pushed to Zoho successfully.');
        } catch (\Throwable $e) {

            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function allLoginHistoryList(Request $request)
    {
        if ($request->ajax()) {

            $query = LoginHistory::query()
                ->select(
                    'users.id as user_id',
                    'users.name',
                    'users.email',
                    DB::raw('COUNT(login_histories.id) as total_logins'),
                    DB::raw('MIN(login_histories.login_at) as first_login'),
                    DB::raw('MAX(login_histories.login_at) as last_login'),
                    DB::raw('(SELECT lh.ip FROM login_histories lh WHERE lh.user_id = users.id ORDER BY lh.login_at DESC LIMIT 1) as last_ip'),
                    DB::raw('(SELECT lh.user_agent FROM login_histories lh WHERE lh.user_id = users.id ORDER BY lh.login_at DESC LIMIT 1) as last_device')
                )
                ->join('users', 'users.id', '=', 'login_histories.user_id')
                ->whereNull('users.deleted_at')
                ->whereIn('users.user_type', [1])
                ->where('users.form_status', 1)
                ->where(function ($q) {
                    $q->whereNull('users.name')
                        ->orWhere('users.name', 'not like', '%test%');
                })
                ->where(function ($q) {
                    $q->whereNull('users.email')
                        ->orWhere('users.email', 'not like', '%test%');
                })
                ->groupBy('users.id', 'users.name', 'users.email');
            // ->orderBy('last_login', 'DESC');

            // Date filter on login history
            if ($request->from_date && $request->to_date) {
                $query->whereBetween('login_histories.login_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            }


            return DataTables::of($query)
                // ->addIndexColumn()/
                ->orderColumn('users.id', function ($query, $order) {
                    $query->orderBy('users.id', $order);
                })
                ->addColumn('last_ip', function ($row) {
                    return $row->last_ip ?? '';
                })
                ->addColumn('last_device', function ($row) {
                    return $row->last_device ?? '';
                })
                ->addColumn('first_login_time', function ($row) {
                    return $row->first_login ? Carbon::parse($row->first_login)->format('d/m/Y h:i A') : '';
                })
                ->addColumn('last_login_time', function ($row) {
                    return $row->last_login ? Carbon::parse($row->last_login)->format('d/m/Y h:i A') : '';
                })
                ->addColumn('total_logins_count', function ($row) {
                    return $row->total_logins;
                })


                ->filterColumn('last_ip', function ($query, $keyword) {
                    $query->whereRaw("EXISTS (
                    SELECT 1 FROM login_histories lh
                    WHERE lh.user_id = users.id
                    AND lh.ip LIKE ?
                    ORDER BY lh.login_at DESC
                    LIMIT 1
                )", ["%{$keyword}%"]);
                })
                ->filterColumn('last_device', function ($query, $keyword) {
                    $query->whereRaw("EXISTS (
                    SELECT 1 FROM login_histories lh
                    WHERE lh.user_id = users.id
                    AND lh.user_agent LIKE ?
                    ORDER BY lh.login_at DESC
                    LIMIT 1
                )", ["%{$keyword}%"]);
                })

                ->filterColumn('first_login_time', function ($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(login_histories.login_at, '%d/%m/%Y %h:%i %p') LIKE ?", ["%{$keyword}%"]);
                })
                ->filterColumn('last_login_time', function ($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(login_histories.login_at, '%d/%m/%Y %h:%i %p') LIKE ?", ["%{$keyword}%"]);
                })

                ->make(true);
        }

        return view('seller.all_seller_login_history');
    }


    public function exportLoginHistoryExcel(Request $request)
    {
        $fromDate = $request->start_date ?? null;
        $toDate = $request->end_date ?? null;
        $search = $request->search ?? null;
        $userType =  $request->userType;

        $fileName = 'Lead Buyers Login History List' . '.xlsx';

        return Excel::download(new LoginHistoryListExport($fromDate, $toDate, $search, $userType), $fileName);
    }

    public function exportLoginHistoryCsv(Request $request)
    {
        $fromDate = $request->start_date ?? null;
        $toDate = $request->end_date ?? null;
        $search = $request->search ?? null;
        $userType =  $request->userType;

        $fileName = 'Lead Buyers Login History List' . '.csv';

        return Excel::download(new LoginHistoryListExport($fromDate, $toDate, $search, $userType), $fileName);
    }


    public function deductCredits(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'deduct_credit' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $user = User::lockForUpdate()->findOrFail($request->user_id);

            // Fresh DB value
            $currentCredits = $user->total_credit;

            // ❌ If entered credits > available → error
            if ($request->deduct_credit > $currentCredits) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient credits. Available credits: ' . $currentCredits
                ]);
            }

            // ✅ Deduct full amount only
            $user->decrement('total_credit', $request->deduct_credit);

            // ✅ Save deduction history (corrected)
            $detail = "Manual {$request->deduct_credit} credit deducted";

            $data['user_id'] = $request->user_id;
            $data['purchase_date'] = now()->format('Y-m-d');
            $data['price'] = 0;
            $data['credits'] = $request->deduct_credit; // minus for deduction
            $data['details'] = $detail;
            $data['payment_type'] = 1;
            $data['error_response'] = null;
            $data['status'] = 1;
            $data['created_at'] = now();


            $purchase = PurchaseHistory::create($data);

            $transactionId = $purchase->id;

            $userId = $user->id;

            if ($transactionId) {
                CustomHelper::runInBackground(function () use ($userId, $transactionId) {
                    app(ZohoFinance::class)->integratePurchaseHistory($userId, $transactionId);
                });
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$request->deduct_credit} credits deducted successfully.",
                'new_credit' => $user->total_credit
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.'
            ]);
        }
    }
}
