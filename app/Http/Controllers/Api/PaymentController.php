<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseHistory;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Plan;
use App\Helpers\CustomHelper;
use App\Models\UserDetail;
use App\Models\Invoice;

use \Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    public function buyCredits(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'credits' => 'required|numeric',
            'details' => 'required',
            ], [
            'postcode.required' => 'Location Postcode is required.',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $user_id = $request->user_id;
        $user = User::where('id',$user_id)->first();

        

        $paymentMethodId = $user->stripe_payment_method_id;
        if(empty($paymentMethodId)){
            return $this->sendError("No saved card found!"); 
        }
        $amount = number_format($request->amount/100, 2);
        $credits = $request->credits;
        $details = $request->details ." credits purchased";

        $stipeCustomerId = $user->stripe_customer_id;
        $invoicePrefix = "";
        Stripe::setApiKey(config('services.stripe.secret'));
        if(empty($stipeCustomerId)){
            $customer = Customer::create([
                'name' => $user->name,
                'email' => $user->email,
                'payment_method' => $paymentMethodId,
                
            ]);
            if(!empty($customer)){
                $stipeCustomerId = $customer['id'];
                $invoicePrefix = $customer['invoice_prefix'];

                $dataU['stripe_customer_id'] = $stipeCustomerId;
                $dataU['updated_at'] = date('Y-m-d H:i:s');
                User::where('id',$user_id)->update($dataU);
            }
        }
        try {
            // Create and confirm PaymentIntent using saved payment method
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount, // amount in cents (e.g., $49.99)
                'currency' => 'GBP',
                'customer' => $stipeCustomerId,
                'payment_method' => $paymentMethodId,
                'off_session' => true,
                'confirm' => true,
            ]);
            
            

            if ($paymentIntent->status === 'succeeded') {
                $tId = CustomHelper::createTrasactionLog($user_id, $amount, $credits, $details, 0, 1);
                $dataInv['user_id'] = $user_id;
                $dataInv['invoice_number'] = $invoicePrefix ."-" .$tId;
                $dataInv['details'] = $request->details;
                $dataInv['period'] = 'One off charge';
                $dataInv['amount'] = $amount;
                $this->createInvoice($dataInv);
                return $this->sendResponse('Payment successful!');
            }else{
                $tId = CustomHelper::createTrasactionLog($user_id, $amount, $credits, $details, 0, 2);
                return $this->sendError('Payment did not succeed.');
            }
            
        } catch (\Stripe\Exception\CardException $e) {
            $tId = CustomHelper::createTrasactionLog($user_id, $amount, $credits, $details, 0, 2);
            return $this->sendError($e->getMessage()); 
        }catch (InvalidRequestException $e) {
            $tId = CustomHelper::createTrasactionLog($user_id, $amount, $credits, $details, 0, 2);
            return $this->sendError("Invalid request: " .$e->getMessage());

        } catch (\Exception $e) {
            $tId = CustomHelper::createTrasactionLog($user_id, $amount, $credits, $details, 0, 2);
            return $this->sendError("Something went wrong: " .$e->getMessage());
        }

        
        
        
    }

    private function createInvoice($data){
        $userDetails = UserDetail::where('user_id',$data['user_id'])->first();
        $vat = 0;
        $billing_vat_register = !empty($userDetails->billing_vat_register) ? $userDetails->billing_vat_register : 0;
        if($billing_vat_register){
            $vatRate = 20 / 100;
            $vat = number_format($data['amount'] * $vatRate, 2);
        }
        $vat = number_format($vat, 2);
        $totalAmount =  number_format($data['amount'] + $vat, 2);
        $data['vat'] = $vat;
        $data['total_amount'] = $totalAmount;

        if(!empty($userDetails)){
            $data['name'] =$userDetails->billing_contact_name;
            $data['address'] = $userDetails->billing_address1 .', ' .$userDetails->billing_address2 .', ' .$userDetails->billing_city .' - ';
            $data['address'] .= $userDetails->billing_postcode;
            $data['phone'] = $userDetails->billing_phone;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        Invoice::insertGetId($data);
    }


    public function getTransactionLogs(Request $request){
        $user_id = $request->user_id;
        $logs = PurchaseHistory::where('user_id',$user_id)->get();
        return $this->sendResponse('Transaction logs', $logs);
    }

}