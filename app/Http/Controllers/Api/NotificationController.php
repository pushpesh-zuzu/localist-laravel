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

class NotificationController extends Controller
{
    public function addUpdateNotification(Request $request){
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
}