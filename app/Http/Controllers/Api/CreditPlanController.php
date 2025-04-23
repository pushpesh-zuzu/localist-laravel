<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseHistory;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Plan;
use \Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};

class CreditPlanController extends Controller
{
    public function getPlans(Request $request){
        $plans = Plan::where('status',1)->get();
        foreach ($plans as $key => $value) {
            if ($value->no_of_leads > 0) {
                $value['per_credit'] = round($value->price / (float) $value->no_of_leads, 2);
            } else {
                $value['per_credit'] = 0; // or null, or handle however you want
            }
        }
        return $this->sendResponse(__('Plans Data'), $plans);
    }

    public function buyCredits(Request $request){
        $aValues = $request->all();
        $plans = Plan::where('id',$aValues['plan_id'])->first();
            $userdetails = PurchaseHistory::create([
                'user_id'  => $aValues['user_id'],
                'plan_id' => $aValues['plan_id'],
                'purchase_date' => Carbon::now(),
                'price' => $plans['price'],
                'credits' => $plans['no_of_leads']
            ]);
            User::where('id',$aValues['user_id'])
                ->update([
                            'total_credit'=>DB::raw("total_credit + " . (int)$plans['no_of_leads'])
                        ]);
        return $this->sendResponse(__('Plan has been sucessfully purchased ') );
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
        return $this->sendResponse('Coupon applied successfully', []);
    }
}
