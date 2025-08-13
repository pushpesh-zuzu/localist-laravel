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

use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Models\NotificationSetting;
use App\Models\NotificationLog;
use App\Events\NewNotificationEvent;
use App\Helpers\Zoho\ZohoEmails;

class NotificationController extends Controller
{
    

    public function addUpdateNotificationSettings(Request $request){
        $user_id = $request->user_id;

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'noti_name' => 'required',
            'noti_value' => 'required',
          ], [
            'noti_name.required' => 'Notification api name is required'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $noti_name = $request->noti_name;
        $user_type = 'error';
        $noti_type = 'error';

        switch($noti_name){
            case 'customer_email_change_in_request':
            case 'customer_email_reminder_to_reply':
            case 'customer_email_update_about_new_feature':
                $user_type = 'customer';
                $noti_type = 'email';
                break;
            case 'buyer_browser_new_lead':
            case 'buyer_browser_customer_sending_message':
            case 'buyer_browser_new_review':
                $user_type = 'buyer';
                $noti_type = 'browser';
                break;
            case 'buyer_email_new_lead':
            case 'buyer_email_customer_closing_leads':
            case 'buyer_email_customer_hiring_me':
                $user_type = 'buyer';
                $noti_type = 'email';
                break;
            default:
                $user_type = 'error';
                $noti_type = 'error';
                break;
        }

        $data['user_id'] = $user_id;
        $data['noti_name'] = $noti_name;
        $data['noti_value'] = $request->noti_value;
        $data['user_type'] = $user_type;
        $data['noti_type'] = $noti_type;
        $data['updated_at'] = date('y-m-d H:i:s');

        if($noti_type != 'error'){
            $noti_id = NotificationSetting::where('noti_name',$noti_name)->where('user_type',$user_type)->value('id');
            if(empty($noti_id)){
                $data['created_at'] = date('y-m-d H:i:s');
                NotificationSetting::insertGetId($data);
            }else{
                NotificationSetting::where('id',$noti_id)->update($data);
            }
            return $this->sendResponse('Notification Setting Updated');
        }
        return $this->sendError('Something went wrong, please check for proper notification name');
    }

    public function getNotificationSettings(Request $request){
        $user_id = $request->user_id;

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'user_type' => 'required',
            'noti_type' => 'required',
          ], [
            'user_type.required' => 'User type is required, either customer or buyer',
            'noti_type.required' => 'User type is required, either email or browser'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $notiSettingList = NotificationSetting::where('user_id',$user_id)
            ->where('user_type',$request->user_type)
            ->where('noti_type',$request->noti_type)
            ->select(['user_id','noti_name','noti_value','user_type','noti_type'])
            ->get()->toArray();

        
        //for customer
        if($request->user_type == 'customer'){
            //Changes to my requests
            if (!in_array('customer_email_change_in_request', array_column($notiSettingList, 'noti_name'))) {
                $noti = array(
                    "user_id"=> $user_id,
                    "noti_name"=> "customer_email_change_in_request",
                    "noti_value"=> 0,
                    "user_type"=> "customer",
                    "noti_type"=> "email"
                );
                array_push($notiSettingList,$noti);
            }
            //Reminders to reply to Professionals
            if (!in_array('customer_email_reminder_to_reply', array_column($notiSettingList, 'noti_name'))) {
                $noti = array(
                    "user_id"=> $user_id,
                    "noti_name"=> "customer_email_reminder_to_reply",
                    "noti_value"=> 0,
                    "user_type"=> "customer",
                    "noti_type"=> "email"
                );
                array_push($notiSettingList,$noti);
            }
            //Updates about new features on Localist
            if (!in_array('customer_email_update_about_new_feature', array_column($notiSettingList, 'noti_name'))) {
                $noti = array(
                    "user_id"=> $user_id,
                    "noti_name"=> "customer_email_update_about_new_feature",
                    "noti_value"=> 0,
                    "user_type"=> "customer",
                    "noti_type"=> "email"
                );
                array_push($notiSettingList,$noti);
            }
        }
        //for seller
        if($request->user_type == 'buyer'){
            if($request->noti_type == 'email'){ //for email type
                //Changes to buyer_email_new_lead
                if (!in_array('buyer_email_new_lead', array_column($notiSettingList, 'noti_name'))) {
                    $noti = array(
                        "user_id"=> $user_id,
                        "noti_name"=> "buyer_email_new_lead",
                        "noti_value"=> 0,
                        "user_type"=> "buyer",
                        "noti_type"=> "email"
                    );
                    array_push($notiSettingList,$noti);
                }

                //Changes to buyer_email_customer_closing_leads
                if (!in_array('buyer_email_customer_closing_leads', array_column($notiSettingList, 'noti_name'))) {
                    $noti = array(
                        "user_id"=> $user_id,
                        "noti_name"=> "buyer_email_customer_closing_leads",
                        "noti_value"=> 0,
                        "user_type"=> "buyer",
                        "noti_type"=> "email"
                    );
                    array_push($notiSettingList,$noti);
                }

                //Changes to buyer_email_customer_hiring_me
                if (!in_array('buyer_email_customer_hiring_me', array_column($notiSettingList, 'noti_name'))) {
                    $noti = array(
                        "user_id"=> $user_id,
                        "noti_name"=> "buyer_email_customer_hiring_me",
                        "noti_value"=> 0,
                        "user_type"=> "buyer",
                        "noti_type"=> "email"
                    );
                    array_push($notiSettingList,$noti);
                }
            }


            if($request->noti_type == 'browser'){ //for browser type
                //Changes to buyer_browser_new_lead
                if (!in_array('buyer_browser_new_lead', array_column($notiSettingList, 'noti_name'))) {
                    $noti = array(
                        "user_id"=> $user_id,
                        "noti_name"=> "buyer_browser_new_lead",
                        "noti_value"=> 0,
                        "user_type"=> "buyer",
                        "noti_type"=> "browser"
                    );
                    array_push($notiSettingList,$noti);
                }

                //Changes to buyer_browser_customer_sending_message
                if (!in_array('buyer_browser_customer_sending_message', array_column($notiSettingList, 'noti_name'))) {
                    $noti = array(
                        "user_id"=> $user_id,
                        "noti_name"=> "buyer_browser_customer_sending_message",
                        "noti_value"=> 0,
                        "user_type"=> "buyer",
                        "noti_type"=> "browser"
                    );
                    array_push($notiSettingList,$noti);
                }

                //Changes to buyer_browser_new_review
                if (!in_array('buyer_browser_new_review', array_column($notiSettingList, 'noti_name'))) {
                    $noti = array(
                        "user_id"=> $user_id,
                        "noti_name"=> "buyer_browser_new_review",
                        "noti_value"=> 0,
                        "user_type"=> "buyer",
                        "noti_type"=> "browser"
                    );
                    array_push($notiSettingList,$noti);
                }
            }
        }
        return $this->sendResponse('Notification Settings List',$notiSettingList);

    }

    public function getAllNotifications(Request $request){
        $userId =$request->user_id;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }

            $notifications = NotificationLog::where('user_id', $userId)
                ->where('status','unread')
                ->orderBy('id', 'asc')
                ->selectRaw("id, title, message, DATE_FORMAT(created_at, '%Y-%m-%d %I:%i %p') as created_at, status")
                ->get();
            $lastId = $notifications->last()?->id ?? null;
            return response()->json([
                'success' => true,
                'last_id' => $lastId,
                'data' => $notifications
            ]);
    }

    public function markAllNotifications(Request $request){
        $userId =$request->user_id;
        $last_id =$request->last_id;
        if (!$userId) {
        return response()->json(['success' => false, 'message' => 'User ID is required'], 400);
        }

        NotificationLog::where('user_id', $userId)
            ->where('status', 'unread')
            ->where('id', '<=', $last_id)
            ->update(['status' => 'read']);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.'
        ]);
    }

    public function notificationCronLogs(Request $request){
        $leadPref = new LeadPreferenceController();

        User::where('form_status', 1)
            ->whereIn('user_type', [1, 3])
            ->select('id')
            ->chunk(1000, function ($sellersChunk) use ($leadPref) {
                foreach ($sellersChunk as $seller) {
                    $baseQuery = $leadPref->getBaseQuery($seller->id);
                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();

                    $allLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    foreach ($allLeads as $lead) {
                        CustomHelper::logNotifications(
                            $seller->id,
                            $lead->id,
                            'buyer_browser_new_lead',
                            'New Lead',
                            'You have got a new lead',
                            true
                        );
                    }
                }
            });

        unset($leadPref);
    }


}
