<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         abort_if(!auth()->user()->can('coupons.viewlist'), 403, __('User does not have the right permissions.'));
        $aRows = Coupon::orderBy('id','DESC')->get(); 
        return view('coupon.index',get_defined_vars());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(!auth()->user()->can('coupons.create'), 403, __('User does not have the right permissions.'));
        $aRow = array();
        return view('coupon.create',get_defined_vars());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         abort_if(!auth()->user()->can('coupons.create'), 403, __('User does not have the right permissions.'));
        $this->validateSave($request);   
        return redirect()->route('coupon.index')->with('success', 'Coupon created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Coupon $coupon)
    {
        return $coupon;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        abort_if(!auth()->user()->can('coupons.edit'), 403, __('User does not have the right permissions.'));
        $aRow = Coupon::where('id',$id)->first();
        return view('coupon.create',get_defined_vars());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         abort_if(!auth()->user()->can('coupons.edit'), 403, __('User does not have the right permissions.'));
        $coupon = Coupon::where('id',$id)->first();
        $this->validateSave($request,$coupon);      
        return redirect()->route('coupon.index')
                         ->with('success', 'Coupon updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
         abort_if(!auth()->user()->can('coupons.delete'), 403, __('User does not have the right permissions.'));
        Coupon::where('id',$id)->delete();
        return redirect()->route('coupon.index')
                         ->with('success', 'Coupon deleted successfully.');
    }

    protected function validateSave(Request $request,$isEdit = "")
    {
        $aValids['percentage'] =  'required';
        $aValids['valid_from'] =  'required';
        $aValids['valid_to'] =  'required';
        $aValids['coupon_limit'] =  'required';

        
        if($isEdit)
        {
            $request->validate($aValids);
            $aVals = $request->all();
            $isEdit->update($aVals);
        }
        else{
            $aValids['coupon_code'] =  'required';
            $request->validate($aValids);
            $aVals = $request->all();
            Coupon::create($aVals);
        }

       

        
    }
}
