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
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoLeadBuyers;
use Illuminate\Support\Facades\{
    Auth,
    Hash,
    DB,
    Log,
    Mail,
    Validator
};
use Illuminate\Support\Facades\Storage;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentMethod;
use App\Helpers\Zoho\ZohoSocialMedia;

class SettingController extends Controller
{
    public function updateSellerProfile(Request $request)
    {

        $user_id = $request->user_id;
        $aValues = $request->all();

        $users = User::where('id', $user_id)->first();
        $userdetails = UserDetail::where('user_id', $user_id)->first();

        if ($aValues['type'] == 'about') {
            if ($request->hasFile('company_logo')) {
                $imagePath =  CustomHelper::fileUpload($aValues['company_logo'], 'users');
                $company_logo = $imagePath;
            } else {
                $company_logo = "";
            }
            if ($request->hasFile('profile_image')) {
                $profileimagePath =  CustomHelper::fileUpload($aValues['profile_image'], 'users');
                $profile_image = $profileimagePath;
            } else {
                $profile_image = "";
            }

            $userData = [
                'company_reg_number'    => $aValues['company_reg_number'] ?? null,
                'company_name'          => $aValues['company_name'] ?? null,
                'company_logo'          => $company_logo ?? null,
                'name'                  => $aValues['name'] ?? null,
                'profile_image'         => $profile_image ?? null,
                'company_email'         => $aValues['company_email'] ?? null,
                'company_phone'         => $aValues['company_phone'] ?? null,
                'company_website'       => $aValues['company_website'] ?? null,
                'company_location'      => $aValues['company_location'] ?? null,
                'company_locaion_reason' => $aValues['company_locaion_reason'] ?? null,
                'company_size'          => $aValues['company_size'] ?? null,
                'company_total_years'   => $aValues['company_total_years'] ?? null,
                'about_company'         => $aValues['about_company'] ?? null,
            ];

            // Filter out any field with null or empty string ("")
            $filteredUserData = array_filter($userData, function ($value) {
                return !is_null($value) && $value !== '';
            });

            // Now update only non-empty fields
            $users->update($filteredUserData);
        }

        if ($aValues['type'] == 'photos') {
            $existingPhotos = $request->input('existing_photos'); // "old1.png,old2.png"

            if (is_string($existingPhotos)) {
                $existingPhotosArray = $existingPhotos ? explode(',', $existingPhotos) : [];
            } elseif (is_array($existingPhotos)) {
                $existingPhotosArray = $existingPhotos;
            } else {
                $existingPhotosArray = [];
            }




            $newPhotos = []; // will hold uploaded files

            if ($request->hasFile('company_photos')) {
                foreach ($request->file('company_photos') as $image) {
                    // Upload file
                    $companyimagePath = CustomHelper::fileUpload($image, 'users');
                    if ($companyimagePath) {
                        $newPhotos[] = $companyimagePath; // add new photo filename
                    }
                }
            }

            $allPhotos = array_merge($existingPhotosArray, $newPhotos);

            if (isset($userdetails) && $userdetails != '') {
                $userdetails->update([
                    'company_photos' => implode(',', $allPhotos),
                    'has_youtube_link' => !empty($aValues['has_youtube_link']) ? 1 : 0,
                    'company_youtube_link' => $aValues['company_youtube_link'] ?? null,
                ]);
            } else {
                $userdetails = UserDetail::create([
                    'user_id'  => $user_id,
                    'company_photos' => implode(',', $allPhotos),
                    'has_youtube_link' => !empty($aValues['has_youtube_link']) ? 1 : 0,
                    'company_youtube_link' => $aValues['company_youtube_link'] ?? null,
                    'is_autobid' => 1
                ]);
            }
        }




        if ($aValues['type'] == 'social_media') {
            // echo "<pre>";
            // print_r($aValues);
            // exit;
            if (isset($userdetails) && $userdetails != '') {
                $userdetails->update([
                    'has_fb_link' => !empty($aValues['has_fb_link']) ? 1 : 0,
                    'fb_link' => $aValues['fb_link'],
                    'has_twitter_link' => !empty($aValues['has_twitter_link']) ? 1 : 0,
                    'twitter_link' => $aValues['twitter_link'],
                    'has_tiktok_link' => !empty($aValues['has_tiktok_link']) ? 1 : 0,
                    'tiktok_link' => $aValues['tiktok_link'],
                    'has_insta_link' => !empty($aValues['has_insta_link']) ? 1 : 0,
                    'insta_link' => $aValues['insta_link'],
                    'has_linkedin_link' => !empty($aValues['has_linkedin_link']) ? 1 : 0,
                    'linkedin_link' => $aValues['linkedin_link'],
                    'has_extra_links' => !empty($aValues['has_extra_links']) ? 1 : 0,
                    'extra_links' => str_replace("\n", ",", $aValues['extra_links'])

                ]);
            } else {
                $userdetails = UserDetail::create([
                    'user_id'  => $user_id,
                    'has_fb_link' => !empty($aValues['has_fb_link']) ? 1 : 0,
                    'fb_link' => $aValues['fb_link'],
                    'has_twitter_link' => !empty($aValues['has_twitter_link']) ? 1 : 0,
                    'twitter_link' => $aValues['twitter_link'],
                    'has_tiktok_link' => !empty($aValues['has_tiktok_link']) ? 1 : 0,
                    'tiktok_link' => $aValues['tiktok_link'],
                    'has_insta_link' => !empty($aValues['has_insta_link']) ? 1 : 0,
                    'insta_link' => $aValues['insta_link'],
                    'has_linkedin_link' => !empty($aValues['has_linkedin_link']) ? 1 : 0,
                    'linkedin_link' => $aValues['linkedin_link'],
                    'has_extra_links' => !empty($aValues['has_extra_links']) ? 1 : 0,
                    'extra_links' => str_replace("\n", ",", $aValues['extra_links']),
                    'is_autobid' => 1
                ]);
            }
        }


        if ($aValues['type'] == 'accreditations') {
            $ids      = $request->input('accre_id', []);        // indexed ids
            $names    = $request->input('accre_name', []);      // indexed names
            $files    = $request->file('accre_file', []);       // indexed files
            $existing = $request->input('accre_existing', []);  // indexed filenames
            
            if(!empty($aValues['has_accreditations'])){
                $userdetails->update([
                    'has_accreditations' => !empty($aValues['has_accreditations']) ? 1 : 0,
                ]);
            }
            
            foreach ($names as $index => $name) {
                $id   = $ids[$index] ?? null;
                $file = $files[$index] ?? null;
                $imagePath = null;

                if ($file instanceof \Illuminate\Http\UploadedFile) {

                    $imagePath = CustomHelper::accfileUpload($file, 'accreditations');
                } elseif (!empty($existing[$index])) {

                    $imagePath = $existing[$index];
                }

                if ($id) {

                    $accr = UserAccreditation::find($id);
                    if ($accr) {
                        $accr->update([
                            'name'  => $name,
                            'image' => $imagePath,
                        ]);
                    }
                } else {

                    UserAccreditation::create([
                        'user_id' => $user_id,
                        'name'    => $name,
                        'image'   => $imagePath,
                    ]);
                }
            }
        }


        if ($aValues['type'] == 'delete_accreditation') {
            $id = $request->input('accre_id');
            if ($id) {
                UserAccreditation::where('id', $id)->delete();
            }
        }

        CustomHelper::runInBackground(function () use ($user_id) {
            app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($user_id);
        });

        return $this->sendResponse(__('Profile updated successfully'));
    }

    public function sellerProfileQues()
    {
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

    public function sellerBillingDetails(Request $request)
    {
        $user_id = $request->user_id;
        $aValues = $request->all();
        $userdetails = UserDetail::where('user_id', $user_id)->first();
        if (isset($userdetails) && $userdetails != '') {
            $userdetails->update([
                'billing_contact_name' => $aValues['billing_contact_name'],
                'billing_address1' => $aValues['billing_address1'],
                'billing_address2' => $aValues['billing_address2'],
                'billing_city' => $aValues['billing_city'],
                'billing_postcode' => $aValues['billing_postcode'],
                'billing_phone' => $aValues['billing_phone'],
                'billing_vat_register' => $aValues['billing_vat_register'],
            ]);
        } else {
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

        CustomHelper::runInBackground(function () use ($user_id) {
            app(ZohoLeadBuyers::class)->integrateZohoDetails($user_id);
        });
        return $this->sendResponse('Billing details submitted successfully!', $userdetails);
    }

    public function sellerCardDetails(Request $request)
    {
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

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $user_id = $request->user_id;
        $aValues = $request->all();
        $userdetails = UserCardDetail::where('user_id', $user_id)->first();
        $type = "";
        if (isset($userdetails) && $userdetails != '') {
            $userdetails->update([
                'card_number' => encrypt($aValues['card_number']),
                'expiry_date' => $aValues['expiry_date'],
                'cvc' => encrypt($aValues['cvc'])
            ]);
            $type = 'updated';
        } else {
            $userdetails = UserCardDetail::create([
                'user_id'  => $user_id,
                'card_number' => encrypt($aValues['card_number']),
                'expiry_date' => $aValues['expiry_date'],
                'cvc' => encrypt($aValues['cvc'])
            ]);
            $type = 'added';
        }
        // print_r("card updated");
        //update stripe card id to user
        $dataN['stripe_payment_method_id'] = $request->stripe_payment_method_id;
        $dataN['updated_at'] = date('y-m-d H:i:s');
        User::where('id', $user_id)->update($dataN);

        //check if customer exits in database or not
        $user = User::where('id', $user_id)->first();
        $stipeCustomerId = $user->stripe_customer_id;

        Stripe::setApiKey(CustomHelper::setting_value('stripe_secret'));
        if (empty($stipeCustomerId)) { //customer not exits in database
            // print_r("customer not exits in database");
            //create new customer and attach card to it 
            $customer = Customer::create([
                'name' => $user->name,
                'email' => $user->email,
                'payment_method' => $request->stripe_payment_method_id,

            ]);
            // print_r($customer); exit;
            if (!empty($customer)) {
                $stipeCustomerId = $customer['id'];

                $dataU['stripe_customer_id'] = $stipeCustomerId;
                $dataU['updated_at'] = date('Y-m-d H:i:s');
                User::where('id', $user_id)->update($dataU);
            }
        } else {
            // check if customer exits in stripe or not
            try {
                $customer = Customer::retrieve($stipeCustomerId);
                if ($customer && isset($customer->id)) { // customer exists, attach new card to it
                    $card = PaymentMethod::retrieve($request->stripe_payment_method_id);
                    $card->attach(['customer' => $stipeCustomerId]);
                } else { //customer does not exits, create new customer and attach card to it
                    $customer2 = Customer::create([
                        'name' => $user->name,
                        'email' => $user->email,
                        'payment_method' => $request->stripe_payment_method_id,

                    ]);
                    if (!empty($customer2)) {
                        $stipeCustomerId = $customer2['id'];

                        $dataU2['stripe_customer_id'] = $stipeCustomerId;
                        $dataU2['updated_at'] = date('Y-m-d H:i:s');
                        User::where('id', $user_id)->update($dataU2);
                    }
                }
            } catch (\Exception $e) {
                return $this->sendError("Please add card again, ERROR: " . $e->getMessage());
            }
        }

        return $this->sendResponse("Card $type successfully!");
    }



    public function sellerCardDetailsnew(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'card_number' => 'required',
            'expiry_date' => 'required',
            'cvc' => 'required',
            'stripe_payment_method_id' => 'required',
        ], [
            'user_id.required' => 'User ID is required.',
            'card_number.required' => 'Card Number is required.',
            'expiry_date.required' => 'Card expiry date is required.',
            'stripe_payment_method_id.required' => 'Stripe payment method ID is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        DB::beginTransaction();

        try {

            $user = User::findOrFail($request->user_id);
            $existingCards = UserCardDetail::where('user_id', $user->id)->get();
            foreach ($existingCards as $existingCard) {
                try {
                    if (decrypt($existingCard->card_number) === $request->card_number) {
                        DB::rollBack();
                        return $this->sendError("This card is already added for the user.");
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            $encryptedCardNumber = encrypt($request->card_number);
            $encryptedCvc = encrypt($request->cvc);

            Stripe::setApiKey(CustomHelper::setting_value('stripe_secret'));
            $stripeCustomerId = $user->stripe_customer_id;

            if (empty($stripeCustomerId)) {
                $customer = Customer::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'payment_method' => $request->stripe_payment_method_id,
                ]);

                $stripeCustomerId = $customer->id;
                $user->update([
                    'stripe_customer_id' => $stripeCustomerId,
                    'updated_at' => now(),
                ]);
            } else {

                $paymentMethod = PaymentMethod::retrieve($request->stripe_payment_method_id);
                $paymentMethod->attach(['customer' => $stripeCustomerId]);
            }

            $stripeCardId = $request->stripe_payment_method_id;
            $hasPrimary = UserCardDetail::where('user_id', $user->id)->where('is_primary', 1)->exists();

            $userCard = UserCardDetail::create([
                'user_id' => $user->id,
                'card_number' => $encryptedCardNumber,
                'expiry_date' => $request->expiry_date,
                'cvc' => $encryptedCvc,
                'stripe_card_id' => $stripeCardId,
                'is_primary' => $hasPrimary ? 0 : 1,
            ]);

            if ($userCard->is_primary == 1) {
                $user->update([
                    'stripe_payment_method_id' => $stripeCardId,
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return $this->sendResponse("Card added successfully!", [
                'card_id' => $userCard->id,
                'last4' => substr($request->card_number, -4),
                'is_primary' => $userCard->is_primary,
                'stripe_card_id' => $stripeCardId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError("Failed to add card. ERROR: " . $e->getMessage());
        }
    }


    public function removeCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'card_id' => 'required|exists:user_card_details,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        DB::beginTransaction();

        try {
            $user = User::findOrFail($request->user_id);

            $card = UserCardDetail::where('user_id', $user->id)
                ->where('id', $request->card_id)
                ->firstOrFail();

            Stripe::setApiKey(CustomHelper::setting_value('stripe_secret'));
           
            if ($card->stripe_card_id && $user->stripe_customer_id) {
                $paymentMethod = PaymentMethod::retrieve($card->stripe_card_id);
                $paymentMethod->detach();
            }

            $wasPrimary = $card->is_primary == 1;
            $card->delete();

            if ($wasPrimary) {
              
                $user->stripe_payment_method_id = null;
                $user->save();

               
                $nextCard = UserCardDetail::where('user_id', $user->id)
                    ->orderBy('id', 'desc') 
                    ->first();

                if ($nextCard) {
                    $nextCard->is_primary = 1;
                    $nextCard->save();
                 
                    $user->stripe_payment_method_id = $nextCard->stripe_card_id;
                    $user->save();
                }
            }

            DB::commit();
            return $this->sendResponse("Card removed successfully.", []);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError("Failed to remove card. ERROR: " . $e->getMessage());
        }
    }




    public function makePrimaryCard(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'card_id' => 'required|exists:user_card_details,id',
        ], [
            'user_id.required' => 'User ID is required.',
            'card_id.required' => 'Card ID is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        DB::beginTransaction();

        try {

            $card = UserCardDetail::where('id', $request->card_id)
                ->where('user_id', $request->user_id)
                ->firstOrFail();

            UserCardDetail::where('user_id', $request->user_id)
                ->where('id', '!=', $card->id)
                ->update(['is_primary' => 0]);

            // Mark this card as primary
            $card->update(['is_primary' => 1]);


            User::where('id', $request->user_id)
                ->update([
                    'stripe_payment_method_id' => $card->stripe_card_id,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return $this->sendResponse("Card marked as primary successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError("Failed to update card. ERROR: " . $e->getMessage());
        }
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

    public function getSellerCardnew(Request $request)
    {
        $user_id = $request->user_id;
        $data = UserCardDetail::where('user_id', $user_id)->get()->toArray();

        if (!empty($data)) {
            foreach ($data as &$card) {
                $card['card_number'] = decrypt($card['card_number']);
                $card['cvc'] = decrypt($card['cvc']);
            }
            return $this->sendResponse("Card Details", $data);
        }

        return $this->sendResponse("No Card found!", []);
    }
}
