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
use App\Models\PlanHistory;
use \Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator, Response
};

use Barryvdh\DomPDF\Facade\Pdf;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    public function buyCredits(Request $request){
        $validator = Validator::make($request->all(), [
            'top_up' => 'required|numeric',
            'credits' => 'required|numeric',
            'amount' => 'required|numeric',
            'vat' => 'required|numeric',
            'total_amount' => 'required|numeric',
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
        $total_amount = number_format($request->total_amount/100, 2);
        $credits = $request->credits;
        $details = $request->details .' ' .$credits." credits purchased";

        $stipeCustomerId = $user->stripe_customer_id;
        $invoicePrefix = "4152SX7I";

        Stripe::setApiKey(CustomHelper::setting_value('stripe_secret'));
        try {
            // Create and confirm PaymentIntent using saved payment method
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->total_amount, // amount in cents (e.g., $49.99)
                'currency' => 'GBP',
                'customer' => $stipeCustomerId,
                'payment_method' => $paymentMethodId,
                'off_session' => true,
                'confirm' => true,
            ]);
            
            if ($paymentIntent->status === 'succeeded') {
                //add up new credit in total credits
                $prevCredits = intval(User::where('id',$user_id)->value('total_credit'));

                $dataCr['total_credit'] = $prevCredits + intval($credits);
                $dataCr['updated_at'] = date('Y-m-d H:i:s');
                User::where('id',$user_id)->update($dataCr);

                //add plan purchase
                $dataPh['user_id'] = $user_id;
                $dataPh['is_topup'] = $request->top_up;
                $dataPh['credits'] = $credits;
                $dataPh['plan_name'] = $request->details;
                $dataPh['price'] = number_format($request->amount, 2);
                $dataPh['vat'] = number_format($request->vat, 2);
                $dataPh['total_amount'] = number_format($total_amount, 2);
                $dataPh['created_at'] = date('Y-m-d H:i:s');
                PlanHistory::insertGetId($dataPh);
                
                
                //create transaction logs
                $tId = CustomHelper::createTrasactionLog($user_id, $total_amount, $credits, $details);
                
                //Create invoice
                $dataInv['user_id'] = $user_id;
                $dataInv['invoice_number'] = $invoicePrefix ."-" .$tId;
                $dataInv['details'] = $request->details;
                $dataInv['period'] = 'One off charge';
                $dataInv['amount'] = number_format($request->amount, 2);
                $dataInv['vat'] = number_format($request->vat, 2);
                $dataInv['total_amount'] = number_format($total_amount, 2);
                
                $userDetails = UserDetail::where('user_id',$user_id)->first();
                if(!empty($userDetails)){
                    $dataInv['name'] =$userDetails->billing_contact_name;
                    $dataInv['address'] = $userDetails->billing_address1 .', ' .$userDetails->billing_address2 .', ' .$userDetails->billing_city .' - ';
                    $dataInv['address'] .= $userDetails->billing_postcode;
                    $dataInv['phone'] = $userDetails->billing_phone;
                }
                $dataInv['created_at'] = date('Y-m-d H:i:s');
                Invoice::insertGetId($dataInv);
                                
                return $this->sendResponse('Payment successful!');
            }else{
                $tId = CustomHelper::createTrasactionLog($user_id, $total_amount, $credits, $details, 2, 0, 'Payment did not succeed.');
                return $this->sendError('Payment did not succeed.');
            }
            
        } catch (\Stripe\Exception\CardException $e) {
            $tId = CustomHelper::createTrasactionLog($user_id, $total_amount, $credits, $details, 2, 0, $e->getMessage());
            return $this->sendError($e->getMessage()); 
        }catch (InvalidRequestException $e) {
            $tId = CustomHelper::createTrasactionLog($user_id, $total_amount, $credits, $details, 2, 0, $e->getMessage());
            return $this->sendError("Invalid request: " .$e->getMessage());

        } catch (\Exception $e) {
            $tId = CustomHelper::createTrasactionLog($user_id, $total_amount, $credits, $details, 2, 0, $e->getMessage());
            return $this->sendError("Something went wrong: " .$e->getMessage());
        }

        
        
        
    }

    public function getTransactionLogs(Request $request){
        $user_id = $request->user_id;
        $logs = PurchaseHistory::where('user_id',$user_id)->where('status',1)->get();
        return $this->sendResponse('Transaction logs', $logs);
    }

    public function getInvoices(Request $request){
        $user_id = $request->user_id;

        $invoices = Invoice::where('user_id', $user_id)->get();
        return $this->sendResponse('Invoices', $invoices);
    }

    public function downloadInvoice(Request $request){
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|numeric|exists:invoices,id',
            ], [
            'postcode.required' => 'Location Postcode is required.',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $user_id = $request->user_id;

        $invoices = Invoice::where('id', $request->invoice_id)->first()->toArray();
        $invoices['paid'] = 1;
        // return $invoices;
        $pdf = Pdf::loadView('invoices.invoice_template', $invoices);
        $file_name = $invoices['invoice_number'] .'.pdf';
        return Response::make($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice.pdf"',
        ]);
    }

}