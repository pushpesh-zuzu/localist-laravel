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

class SettingController extends Controller
{
    public function sellerMyprofile(Request $request): JsonResponse
    {
        $user_id = $request->user_id; 
        $aValues = $request->all();
        $users = User::where('id',$user_id)->first();
        $userdetails = UserDetail::where('user_id',$user_id)->first();
        $accreditations = UserAccreditation::where('user_id',$user_id)->where('id',$aValues['accreditation_id'])->first();
        $serviceDetails = UserServiceDetail::where('user_id',$user_id)->where('id',$aValues['user_service_id'])->first();
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
                $userData = self::aboutData($aValues,$company_logo,$profile_image,$users,$user_id);
        }

        if($aValues['type'] == 'user_details'){
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
            $userData = self::userdetailData($aValues,$company_photos,$userdetails,$user_id);
        }
        if($aValues['type'] == 'accreditations'){
                if ($request->hasFile('accre_image')) {
                    $imagePath =  CustomHelper::accfileUpload($aValues['accre_image'],'accreditations');
                    $accre_image = $imagePath; 
                }else{
                    $accre_image = "";
                }
                  // Handle delete condition
                if (isset($aValues['deleteData']) && $aValues['deleteData'] == 1) {
                    $accrDeleteIds = explode(',', $aValues['accr_delete_id']);
                    $accDatas = UserAccreditation::whereIn('id', $accrDeleteIds)
                                                 ->where('user_id', $user_id)
                                                 ->get();
                    if (count($accDatas)>0) {
                        UserAccreditation::whereIn('id', $accrDeleteIds)
                            ->where('user_id', $user_id)
                            ->delete();
                            return $this->sendResponse(__('Accreditations deleted successfully'), []);
                    } else {
                        return $this->sendError("No Data found");
                        // Delete a single record
                        // UserAccreditation::where('id', $aValues['accr_delete_id'])
                        //     ->where('user_id', $user_id)
                        //     ->delete();
                    }

                    
                }

                $userData = self::accreditationData($aValues,$accreditations,$user_id,$accre_image,$userdetails);
                $userData->is_accreditations =  $aValues['is_accreditations']; 
        }

        if($aValues['type'] == 'userservices'){
               // Handle delete condition
               if (isset($aValues['deleteData']) && $aValues['deleteData'] == 1) {
                $serviceDeleteIds = explode(',', $aValues['service_delete_id']);
                $serDatas = UserServiceDetail::whereIn('id', $serviceDeleteIds)
                                             ->where('user_id', $user_id)
                                             ->get();
                if (count($serDatas)>0) {
                    UserServiceDetail::whereIn('id', $serviceDeleteIds)
                                     ->where('user_id', $user_id)
                                     ->delete();
                } else {
                    return $this->sendError("No Data found");
                }

                return $this->sendResponse(__('Services deleted successfully'), []);
            }
            $userData = self::serviceData($aValues,$serviceDetails,$user_id);
        }
        return $this->sendResponse(__('MyProfile updated successfully'),$userData );
    }

    public function userdetailData($aValues,$company_photos,$userdetails,$user_id){
        if(isset($userdetails) && $userdetails != ''){
            $userdetails->update([
                'company_photos' => $company_photos,
                // 'user_emails_reviews' => $aValues['user_emails_reviews'],
                'is_youtube_video' => $aValues['is_youtube_video'],
                'company_youtube_link' => $aValues['company_youtube_link'],
                'is_fb' => $aValues['is_fb'],
                'fb_link' => $aValues['fb_link'],
                'is_twitter' => $aValues['is_twitter'],
                'twitter_link' => $aValues['twitter_link'],
                'is_link_desc' => $aValues['is_link_desc'],
                'link_desc' => $aValues['link_desc'],
            ]);  
        }else{
            $userdetails = UserDetail::create([
                'user_id'  => $user_id,
                'company_photos' => $company_photos,
                // 'user_emails_reviews' => $aValues['user_emails_reviews'],
                'is_youtube_video' => $aValues['is_youtube_video'],
                'company_youtube_link' => $aValues['company_youtube_link'],
                'is_fb' => $aValues['is_fb'],
                'fb_link' => $aValues['fb_link'],
                'is_twitter' => $aValues['is_twitter'],
                'twitter_link' => $aValues['twitter_link'],
                'is_link_desc' => $aValues['is_link_desc'],
                'link_desc' => $aValues['link_desc'],
                'is_autobid' => 1
            ]);
        }
        return $userdetails;
    }

    public function aboutData($aValues,$company_logo,$profile_image,$users,$user_id){
        // if(isset($users) && $users != ''){
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
        // }
        return $users;
    }

    public function accreditationData($aValues,$accreditations,$user_id,$image,$userdetails){
        if(isset($accreditations) && $accreditations != ''){
            $accreditations->update([
                'name' => $aValues['accre_name'],
                'image' => $image
            ]);
            $userdetails->update([
                'is_accreditations' => $aValues['is_accreditations'],
            ]);
        }else{
            $accreditations = UserAccreditation::create([
                'user_id'  => $user_id,
                'name' => $aValues['accre_name'],
                'image' => $image
            ]);
            UserDetail::create([
                'user_id'  => $user_id,
                'is_accreditations' => $aValues['is_accreditations'],
            ]);
        }
        return $accreditations;
    }

    public function serviceData($aValues,$serviceDetails,$user_id){
        if(isset($serviceDetails) && $serviceDetails != ''){
            $serviceDetails->update([
                'title' => $aValues['service_title'],
                'description' => $aValues['service_desc']
            ]);
        }else{
            $serviceDetails = UserServiceDetail::create([
                'user_id'  => $user_id,
                'title' => $aValues['service_title'],
                'description' => $aValues['service_desc']
            ]);
        }
        return $serviceDetails;
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
        $user_id = $request->user_id; 
        $aValues = $request->all();
        $userdetails = UserCardDetail::where('user_id',$user_id)->first();
        if(isset($userdetails) && $userdetails != ''){
            $userdetails->update([
                'card_number' => $aValues['card_number'],
                'expiry_date' => $aValues['expiry_date'],
                'cvc' => Hash::make($aValues['cvc'])
            ]);  
        }else{
            $userdetails = UserCardDetail::create([
                'user_id'  => $user_id,
                'card_number' => $aValues['card_number'],
                'expiry_date' => $aValues['expiry_date'],
                'cvc' => Hash::make($aValues['cvc'])
            ]);
        }
        return $this->sendResponse(__('Profile Questions Data'), $userdetails);
    }
}
