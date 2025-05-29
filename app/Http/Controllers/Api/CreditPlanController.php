<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseHistory;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Plan;
use App\Models\UserDetail;
use \Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};

class CreditPlanController extends Controller
{
    public function getPlans(Request $request){
        $plans = Plan::where('status',1)->orderBy('id','DESC')->get();
        foreach ($plans as $key => $value) {
            if ($value->no_of_leads > 0) {
                $value['per_credit'] = round($value->price / (float) $value->no_of_leads, 2);
            } else {
                $value['per_credit'] = 0; // or null, or handle however you want
            }
            $isVat = UserDetail::where('user_id',$request->user_id)->value('billing_vat_register');
            $value['billing_vat_register'] = $isVat ? 1 : 0 ;
        }
        return $this->sendResponse(__('Plans Data'), $plans);
    }

    

    public function addCoupon(Request $request)
    {
        $aValues = $request->all();
    
        $coupon = Coupon::where('coupon_code', $aValues['coupon_code'])->first();
    
        if (!$coupon) {
            return $this->sendError('Invalid coupon code');
        }
    
        $today = now(); // Current date
    
        // Check date validity
        if ($coupon->valid_from && $coupon->valid_to) {
            if ($today->lt($coupon->valid_from) || $today->gt($coupon->valid_to)) {
                return $this->sendError('Coupon is not valid at this time');
            }
        }
    
        // Check coupon usage limit
        if ($coupon->coupon_limit <= 0) {
            return $this->sendError('Coupon Expired');
        }
    
        // Apply coupon logic (e.g., get discount)
        $discount = $coupon->percentage;
    
        // Optionally decrement coupon_limit (if one-time use per request)
        $coupon->decrement('coupon_limit');
        return $this->sendResponse('Coupon applied successfully', $discount .'%');
    }

    public function getCoupon(Request $request)
    {
        $currentDate = Carbon::now()->format('Y-m-d');

        $coupons = Coupon::whereDate('valid_from', '<=', $currentDate)
            ->whereDate('valid_to', '>=', $currentDate)
            ->get();
        return $this->sendResponse('Coupon fetched', $coupons);
    }
}
