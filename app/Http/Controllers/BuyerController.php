<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecommendedLead;
use App\Models\LeadRequest;
use App\Models\LoginHistory;
use App\Models\User;

class BuyerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $aRows = User::whereIn('user_type', [2, 3])->get(); 
        return view('buyer.index', compact('aRows'));
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
        $aRows = User::where('id',$id)->with(['leadRequests.category'])->first(); 
        return view('buyer.view', compact('aRows'));
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
        // dd($user);
        User::where('id',$id)->delete();
        return redirect()->route('buyer.index')
                         ->with('success', 'Buyer deleted successfully.');
    }

    public function leadDetails($leadid){
        $aRows =  LeadRequest::where('id',$leadid)->first();
        $user = User::where('id', $aRows->customer_id)->pluck('name')->first();
        return view('buyer.lead_details', get_defined_vars());
    }

    public function buyerBids($userid)
    {
        $buyerIds = RecommendedLead::where('buyer_id', $userid)->pluck('seller_id')->unique()->toArray();
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

        return view('buyer.autobid_leads', compact('aRows'));
    }

    public function buyerLogin($userid){
        $aRows =  LoginHistory::where('user_id',$userid)->get();
        $user = User::where('id', $userid)->pluck('name')->first();
        return view('buyer.login_history', get_defined_vars());
    }
}
