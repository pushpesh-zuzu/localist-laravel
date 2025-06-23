<?php

namespace App\Http\Controllers;

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

class SellerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $aRows = User::whereIn('user_type', [1, 3])->where('form_status',1)->orderBy('id','DESC')->get(); 
        return view('seller.complete', compact('aRows'));
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
    public function show(string $id)
    {
        $aRows = User::where('id',$id)->with(['userDetails'])->first(); 
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
    public function destroy(string $id)
    {
        \DB::table('users')->where('id',$id)->delete();
        \DB::table('user_accreditations')->where('user_id',$id)->delete();
        \DB::table('user_card_details')->where('user_id',$id)->delete();
        \DB::table('user_details')->where('user_id',$id)->delete();
        \DB::table('user_hiring_histories')->where('user_id',$id)->delete();
        \DB::table('user_response_times')->where('seller_id',$id)->delete();
        \DB::table('user_services')->where('user_id',$id)->delete();
        \DB::table('user_service_locations')->where('user_id',$id)->delete();
        \DB::table('activity_logs')->where('from_user_id',$id)->orWhere('to_user_id',$id)->delete();
        \DB::table('invoices')->where('user_id',$id)->delete();
        \DB::table('lead_prefrences')->where('user_id',$id)->delete();
        \DB::table('lead_requests')->where('customer_id',$id)->delete();
        \DB::table('lead_statuses')->where('user_id',$id)->delete();
        \DB::table('login_histories')->where('user_id',$id)->delete();
        \DB::table('plan_histories')->where('user_id',$id)->delete();
        \DB::table('profile_q_a_s')->where('user_id',$id)->delete();
        \DB::table('purchase_histories')->where('user_id',$id)->delete();
        \DB::table('recommended_leads')->where('seller_id',$id)->orWhere('buyer_id',$id)->delete();
        \DB::table('reviews')->where('user_id',$id)->delete();
        \DB::table('save_for_laters')->where('seller_id',$id)->orWhere('user_id',$id)->delete();
        \DB::table('seller_notes')->where('seller_id',$id)->orWhere('buyer_id',$id)->delete();
        \DB::table('suggested_questions')->where('user_id',$id)->delete();
        \DB::table('unique_visitors')->where('seller_id',$id)->orWhere('buyer_id',$id)->delete();
        
        return redirect()->route('seller.index')
                         ->with('success', 'Seller deleted successfully.');
    }

    public function incompletelist()
    {
        $aRows = User::whereIn('user_type', [1, 3])->where('form_status',0)->orderBy('id','DESC')->get(); 
        return view('seller.incomplete', compact('aRows'));
    }

    public function sellerServices($userid){
        $user = User::where('id', $userid)->pluck('name')->first();
        $serviceId = UserService::where('user_id', $userid)->pluck('service_id')->toArray();
        $aRows = Category::whereIn('id', $serviceId)->get();
        foreach ($aRows as $key => $value) {
            $value['locations'] = UserServiceLocation::whereIn('user_id',[$userid])->whereIn('service_id', [$value->id])->select(['miles','postcode','nation_wide'])->get()->toArray();
            $value['leadpref'] = LeadPrefrence::whereIn('service_id', [$value->id])
                                                ->where('user_id', $userid)
                                                ->with('serquestions')
                                                ->get();
            $value['autobid'] = UserService::where('user_id', $userid)->where('service_id', $value->id)->pluck('auto_bid')->first();
        }
        return view('seller.services', get_defined_vars());
    }

    public function creditPlans($userid){
        $user = User::where('id', $userid)->pluck('name')->first();
        $aRows = PurchaseHistory::where('user_id', $userid)->with(['plans','users'])->get();
        return view('seller.credit_plans', get_defined_vars());
    }

    public function sellerBids($userid)
{
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
        $leads = LeadRequest::whereIn('customer_id', $buyerIds)->orderBy('id','DESC')->get();
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
                    ->where('seller_id',$userid)
                    ->pluck('purchase_type')
                    ->first();
                    return $lead;
                })
            ];
        }

        return view('seller.autobid_leads', compact('aRows'));
    }

    public function sellerAccreditations($userid){
        $aRows = UserAccreditation::where('user_id', $userid)->orderBy('id','DESC')->get();
        $user = User::where('id', $userid)->pluck('name')->first();
        return view('seller.seller_accreditations', get_defined_vars());
    }

    public function sellerProfileServices($userid){
        $aRows = UserServiceDetail::where('user_id', $userid)->orderBy('id','DESC')->get();
        $user = User::where('id', $userid)->pluck('name')->first();
        return view('seller.seller_services', get_defined_vars());
    }

    public function suggestedQuestions($userid){
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

    public function sellerLogin($userid){
        $aRows =  LoginHistory::where('user_id',$userid)->orderBy('id','DESC')->get();
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
}
