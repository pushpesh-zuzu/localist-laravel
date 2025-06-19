<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserAccreditation;
use App\Models\UserServiceDetail;
use App\Models\ProfileQuestion;
use App\Models\UserCardDetail;
use App\Models\UserDetail;
use App\Models\ProfileQA;
use App\Models\User;
use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentMethod;

class SettingController extends Controller
{
    public function updateSellerProfile(Request $request): JsonResponse
    {
        
        $user_id = $request->user_id; 
        $aValues = $request->all();
        
        $users = User::where('id',$user_id)->first();
        $userdetails = UserDetail::where('user_id',$user_id)->first();
        
        if($aValues['type'] == 'about'){
            if ($request->hasFile('company_logo')) {
                $imagePath =  CustomHelper::fileUpload($aValues['company_logo'],'users');
                $company_logo = $imagePath; 
            }else{
                $company_logo = "";
            }
            if ($request->hasFile('profile_image')) {
                $profileimagePath =  CustomHelper::fileUpload($aValues['profile_image'],'users');
                $profile_image = $profileimagePath; 
            }else{
                $profile_image = "";
            }
            $users->update([
                'company_name' => $aValues['company_name'],
                'company_logo' => $company_logo,
                'name' => $aValues['name'],
                'profile_image' => $profile_image,
                'company_email' => $aValues['company_email'],
                'company_phone' => $aValues['company_phone'],
                'company_website' => $aValues['company_website'],
                'company_location' => $aValues['company_location'],
                'company_locaion_reason' => $aValues['company_locaion_reason'],
                'company_size' => $aValues['company_size'],
                'company_total_years' => $aValues['company_total_years'],
                'about_company' => $aValues['about_company'],
            ]);
        }

        if($aValues['type'] == 'photos'){
            if ($request->hasFile('company_photos')) {
                $companyimgPaths = []; // Store multiple image names
                foreach ($request->file('company_photos') as $image) {
                    $companyimagePath = CustomHelper::fileUpload($image, 'users');
                    if ($companyimagePath) {
                        $companyimgPaths[] = $companyimagePath; // Add filename to the array
                    }
                }
                
                $company_photos = implode(',', $companyimgPaths); // Convert array to a comma-separated string
            }else{
                $company_photos = "";
            }
            if(isset($userdetails) && $userdetails != ''){
                $userdetails->update([
                    'company_photos' => $company_photos,
                    'company_youtube_link' => $aValues['company_youtube_link'],
                ]);  
            }else{
                $userdetails = UserDetail::create([
                    'user_id'  => $user_id,
                    'company_photos' => $company_photos,
                    'company_youtube_link' => $aValues['company_youtube_link'],
                    'is_autobid' => 1
                ]);
            }
        }

        if($aValues['type'] == 'social_media'){
            // echo "<pre>";
            // print_r($aValues);
            // exit;
            $type = "";
            if(isset($userdetails) && $userdetails != ''){
                $userdetails->update([
                    'fb_link' => $aValues['fb_link'],
                    'twitter_link' => $aValues['twitter_link'],
                    'tiktok_link' => $aValues['tiktok_link'],
                    'insta_link' => $aValues['insta_link'],
                    'linkedin_link' => $aValues['linkedin_link'],
                    'extra_links' => str_replace("\n", ",", $aValues['extra_links'])
                    
                ]);  
                echo $type = 'update';
            }else{
                $userdetails = UserDetail::create([
                    'user_id'  => $user_id,
                    'fb_link' => $aValues['fb_link'],
                    'twitter_link' => $aValues['twitter_link'],
                    'tiktok_link' => $aValues['tiktok_link'],
                    'insta_link' => $aValues['insta_link'],
                    'linkedin_link' => $aValues['linkedin_link'],
                    'extra_links' => $aValues['extra_links'],
                    'is_autobid' => 1
                ]);
                $type = 'new';
            }
        }
        if($aValues['type'] == 'accreditations'){
           
            //uploading multiple accres
            $files = $request->file('accre_image');
            $names = $request->input('accre_name', []); 
            foreach($files as $index => $img){
                $imagePath =  CustomHelper::accfileUpload($img,'accreditations');                    
                $accreditations = UserAccreditation::create([
                    'user_id'  => $user_id,
                    'name' => $names[$index],
                    'image' => $imagePath
                ]);
            }

        }

        return $this->sendResponse(__('Profile updated successfully'));
    }

    public function sellerProfileQues(){
        $questions = ProfileQuestion::where('status', 1)->orderBy('id', 'DESC')->get();
        return $this->sendResponse(__('Profile Questions Data'), $questions);
    }

    public function sellerMyprofileqa(Request $request): JsonResponse
    {
        $user_id = $request->user_id;
        $questions = $request->input('questions', []); // Get array of questions
        $answers = $request->input('answers', []); // Get array of answers
    
        if (!is_array($questions) || !is_array($answers)) {
            return $this->sendError("Invalid data format");
        }
    
        $data = [];
        foreach ($questions as $index => $question) {
            $answer = $answers[$index] ?? null;
    
            if (empty($question) || empty($answer)) {
                continue; // Skip if question or answer is empty
            }
    
            // Check if the question already exists for this user
            $profileQues = ProfileQA::where('user_id', $user_id)
                ->where('questions', $question)
                ->first();
    
            if ($profileQues) {
                // Update existing record
                $profileQues->update([
                    'answer' => $answer
                ]);
                $data[] = $profileQues;
            } else {
                // Insert new record
                $newQnA = ProfileQA::create([
                    'user_id' => $user_id,
                    'questions' => $question,
                    'answer' => $answer
                ]);
                $data[] = $newQnA;
            }
        }
    
        if (empty($data)) {
            return $this->sendError("No valid data submitted");
        }
    
        return $this->sendResponse(__('Data Submitted successfully'), $data);
    }

    public function sellerBillingDetails(Request $request){
        $user_id = $request->user_id; 
        $aValues = $request->all();
        $userdetails = UserDetail::where('user_id',$user_id)->first();
        if(isset($userdetails) && $userdetails != ''){
            $userdetails->update([
                'billing_contact_name' => $aValues['billing_contact_name'],
                'billing_address1' => $aValues['billing_address1'],
                'billing_address2' => $aValues['billing_address2'],
                'billing_city' => $aValues['billing_city'],
                'billing_postcode' => $aValues['billing_postcode'],
                'billing_phone' => $aValues['billing_phone'],
                'billing_vat_register' => $aValues['billing_vat_register'],
            ]);  
        }else{
            $userdetails = UserDetail::create([
                'user_id'  => $user_id,
                'billing_contact_name' => $aValues['billing_contact_name'],
                'billing_address1' => $aValues['billing_address1'],
                'billing_address2' => $aValues['billing_address2'],
                'billing_city' => $aValues['billing_city'],
                'billing_postcode' => $aValues['billing_postcode'],
                'billing_phone' => $aValues['billing_phone'],
                'billing_vat_register' => $aValues['billing_vat_register'],
            ]);
        }
        return $userdetails;
    }

    public function sellerCardDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'card_number' => 'required',
            'expiry_date' => 'required',
            'cvc' => 'required',
            'stripe_payment_method_id' => 'required',
          ], [
            'card_number.required' => 'Card Number is required.',
            'expiry_date.required' => 'Card Valid till date is required.',
            'stripe_payment_method_id.required' => 'Stripe Payment method Id is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $user_id = $request->user_id; 
        $aValues = $request->all();
        $userdetails = UserCardDetail::where('user_id',$user_id)->first();
        $type = "";
        if(isset($userdetails) && $userdetails != ''){
            $userdetails->update([
                'card_number' => encrypt($aValues['card_number']),
                'expiry_date' => $aValues['expiry_date'],
                'cvc' => encrypt($aValues['cvc'])
            ]);
            $type = 'updated';  
        }else{
            $userdetails = UserCardDetail::create([
                'user_id'  => $user_id,
                'card_number' => encrypt($aValues['card_number']),
                'expiry_date' => $aValues['expiry_date'],
                'cvc' => encrypt($aValues['cvc'])
            ]);
            $type = 'added';
        }

        //update stripe card id to user
        $dataN['stripe_payment_method_id'] = $request->stripe_payment_method_id;
        $dataN['updated_at'] = date('y-m-d H:i:s');
        User::where('id',$user_id)->update($dataN);

        //check if customer exits in database or not
        $user = User::where('id',$user_id)->first();
        $stipeCustomerId = $user->stripe_customer_id;
        
        Stripe::setApiKey(CustomHelper::setting_value('stripe_secret'));
        if(empty($stipeCustomerId)){ //customer not exits in database
            $customer = Customer::create([
                'name' => $user->name,
                'email' => $user->email,
                'payment_method' => $request->stripe_payment_method_id,
                
            ]);
            if(!empty($customer)){
                $stipeCustomerId = $customer['id'];
                
                $dataU['stripe_customer_id'] = $stipeCustomerId;
                $dataU['updated_at'] = date('Y-m-d H:i:s');
                User::where('id',$user_id)->update($dataU);
            }
        }else{
            // check if customer exits in stripe or not
            try {
                $customer = Customer::retrieve($stipeCustomerId);
                if ($customer && isset($customer->id)) { // customer exists, attach new card to it
                    $card = PaymentMethod::retrieve($request->stripe_payment_method_id);
                    $card->attach(['customer' => $stipeCustomerId]);
                    
                }else{ //customer does not exits, create new customer and attach card to it
                    $customer2 = Customer::create([
                        'name' => $user->name,
                        'email' => $user->email,
                        'payment_method' => $request->stripe_payment_method_id,
                        
                    ]);
                    if(!empty($customer2)){
                        $stipeCustomerId = $customer2['id'];
                        
                        $dataU2['stripe_customer_id'] = $stipeCustomerId;
                        $dataU2['updated_at'] = date('Y-m-d H:i:s');
                        User::where('id',$user_id)->update($dataU2);
                    }
                }
            } catch (\Exception $e) {
                return $this->sendError("Please add card again, ERROR: " .$e->getMessage());
            }
        }
        
        return $this->sendResponse("Card $type successfully!");
    }

    public function getSellerCard(Request $request){
        $user_id = $request->user_id;
        $data = UserCardDetail::where('user_id',$user_id)->get()->toArray();
        if(!empty($data)){
            $data[0]['card_number'] = decrypt($data[0]['card_number']);
            $data[0]['cvc'] = decrypt($data[0]['cvc']);
            return $this->sendResponse("Card Details", $data);
        }
        return $this->sendResponse("No Card found!",$data);
    }
}
