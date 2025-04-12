<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Models\PurchaseHistory;
use App\Models\LeadRequest;
use App\Models\LeadPrefrence;
use App\Models\Plan;
use App\Models\Category;
use App\Models\Bid;

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
        $aRows = User::where('id',$id)->get(); 
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
        User::where('id',$id)->delete();
        return redirect()->route('seller.index')
                         ->with('success', 'Seller deleted successfully.');
    }

    public function incompletelist()
    {
        $aRows = User::whereIn('user_type', [1, 3])->where('form_status',0)->orderBy('id','DESC')->get(); 
        return view('seller.incomplete', compact('aRows'));
    }

    public function sellerServices($userid){
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
        return view('seller.services', compact('aRows'));
    }

    public function creditPlans($userid){
        $user = User::where('id', $userid)->pluck('name')->first();
        $aRows = PurchaseHistory::where('user_id', $userid)->with(['plans','users'])->get();
        return view('seller.credit_plans', get_defined_vars());
    }

    public function sellerBids($userid)
    {
        $buyerIds = Bid::where('seller_id', $userid)->pluck('buyer_id')->unique()->toArray();
        $leads = LeadRequest::whereIn('customer_id', $buyerIds)->get();
        // Group all leads by customer_id
        $groupedLeads = $leads->groupBy('customer_id');

        $aRows = [];

        foreach ($groupedLeads as $customerId => $customerLeads) {
            $user = User::find($customerId);
            
            $aRows[] = [
                'buyer_name' => $user ? $user->name : '',
                'customer_id' => $customerId,
                'leads' => $customerLeads->map(function ($lead) {
                    $lead->service_name = Category::where('id', $lead->service_id)->pluck('name')->first();
                    return $lead;
                })
            ];
        }

        return view('seller.autobid_leads', compact('aRows'));
    }


    // public function sellerBids($userid){
    //     $buyerId = Bid::whereIn('seller_id', [$userid])->pluck('buyer_id')->toArray();
    //     // $leadId = Bid::whereIn('seller_id', $userid)->pluck(['lead_id'])->toArray();
    //     $aRows = LeadRequest::whereIn('customer_id', $buyerId)->get();
    //     foreach ($aRows as $key => $value) {
    //         $value['buyer_name'] = User::where('id', $value->customer_id)->pluck('name')->first();
    //         $value['service_name'] = Category::where('id', $value->service_id)->pluck('name')->first();
    //     }
    //     return view('seller.autobid_leads', compact('aRows'));
    // }
}
