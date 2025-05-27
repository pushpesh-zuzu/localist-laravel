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

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;

class PaymentController extends Controller
{
    public function verifyCard(Request $request){
        $validator = Validator::make($request->all(), [
            'cardnumber' => 'required',
            'exp-date' => 'required',
            'cvc' => 'required',
            ], [
            'postcode.required' => 'Location Postcode is required.',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $intent = SetupIntent::create();

        return $this->sendResponse('Abodned user!',$intent);
    }

}