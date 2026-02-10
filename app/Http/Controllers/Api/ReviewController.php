<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Validation\Rule;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoLeadBuyers;
use App\Helpers\Zoho\ZohoReview;
use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Models\RecommendedLead;
use App\Models\UserResponseTime;
use App\Models\UserDetail;
use App\Models\UserAccreditation;
use App\Models\Review;
use App\Models\NotificationSetting;
use App\Models\NotificationLog;

class ReviewController extends Controller{

    public function getProfile(Request $request){

        $validator = Validator::make($request->all(), [
            'profile_uuid' => 'required|exists:users,uuid',
            ], [
            'profile_uuid.required' => 'Profile id is required.',
            'profile_uuid.exists' => 'Profile id does not exists.',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        

        $sellerId = User::where('uuid',$request->profile_uuid)->value('id');
        
        $ratings = CustomHelper::getAverageRating($sellerId);

        $user = User::where('id',$sellerId)->first();
        //percentage completed
        $user['percentage_completed'] = $user->getProfileCompletionPercentage();
        //for hired list count
        $hireCount = RecommendedLead::where('seller_id', $sellerId)
            ->where('status','hired')
            ->count();

        $user['hire_count'] = $hireCount;

        // $replyCount = RecommendedLead::where('lead_id', $leadId)
        //     ->where('seller_id',$sellerId)
        //     ->where('buyer_id', $buyerId)
        //     ->count();
        // $user['lead_purchased'] = $replyCount > 0 ? 1 : 0;
        $responseTime = UserResponseTime::where('seller_id', $sellerId)->value('average');
        $responseTime = !empty($responseTime) ? $responseTime : 15;
        $user['response_time'] = CustomHelper::formatTimeDuration($responseTime);
        $user['user_details'] = UserDetail::where('user_id',$sellerId)->first();
        $user['reviews'] = Review::where('user_id',$sellerId)->get();
        $user['reviews_count'] = $ratings['total_reviews'] ?? 0;
        $user['accreditations'] = UserAccreditation::where('user_id',$sellerId)->get();
        $user['services'] = UserService::where('user_id',$sellerId)->with(['userServices'])->get();
        $user['qa'] = \DB::table('profile_q_a_s')->where('user_id',$sellerId)->get();
        return $this->sendResponse('Review profile', $user);
    }

    public function getCustomerLink(Request $request){
        $user_id = $request->user_id;
        $uuid = User::where('id',$user_id)->value('uuid');
        $postloginBaseUrl = CustomHelper::setting_value('postlogin_react_base_url');
        $url = $postloginBaseUrl .'review/' .$uuid ;
        return $this->sendResponse('Customer review link',str_replace('/admin','',$url));
    }

    public function submitReview(Request $request){
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:users,uuid',
            'name' => 'required',
            'email' => 'required',
            'ratings' => 'required|numeric|min:0|max:5',
          ], [
            'uuid.required' => 'User identification is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $uuid = $request->uuid;
        $user_id = User::where('uuid',$uuid)->value('id');
        $data['user_id'] = $user_id;
        $data['name'] = $request->name;
        $data['email'] = $request->email;
        $data['review'] = !empty($request->review) ? $request->review : '';
        $data['ratings'] = $request->ratings;
        $data['source'] = 'localists';
        $data['created_at'] = date('y-m-d H:i:s');
        $data['updated_at'] = date('y-m-d H:i:s');
        $aid = Review::insertGetId($data);



        if($aid){
            // $avg_rating = Review::where('user_id', $user_id)->where('source', 'localists')->avg('ratings');
            // $data2['avg_rating'] = number_format($avg_rating, 1);
            // $data2['updated_at'] = date('y-m-d H:i:s');
            // User::where('id',$user_id)->update($data2);
            // $seller = User::where('id', $user_id)->first();

            CustomHelper::getAverageRating($user_id);

            //Add Notification Log for new review
            CustomHelper::logNotifications($user_id,0,'buyer_browser_new_review', 'New Review', $request->review);

            CustomHelper::runInBackground(function() use ($user_id) {
                app(ZohoReview::class)->integrateZohoReview($user_id);
            });

            return $this->sendResponse('Review submitted successfully!');
        }
        return $this->sendError('Something went wrong, try again!');

    }

    public function getReviews(Request $request, $uuid){
        $user_id = User::where('uuid',$uuid)->value('id');
        $list = Review::where('user_id',$user_id)->get();
        return $this->sendResponse('Reviews list',$list);
    }
}
