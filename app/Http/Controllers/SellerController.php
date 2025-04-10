<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Models\Category;

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
        }
        return view('seller.services', compact('aRows'));
    }

    // public function sellerLocations($userid){
    //     $serviceId = UserService::where('user_id', $userid)->pluck('service_id')->toArray();
    //     $aRows = Category::whereIn('id', $serviceId)->get();
    //     return view('seller.services', compact('aRows'));
    // }
}
