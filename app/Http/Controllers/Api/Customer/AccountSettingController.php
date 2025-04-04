<?php

namespace App\Http\Controllers\Api\Customer;

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

class AccountSettingController extends Controller
{

    public function getProfileInfo(Request $request){
        $user_id = $request->user_id;
        
        $info = User::where('id',$user_id)->select('id','name','email','phone','profile_image')->get();

        return $this->sendResponse('User profile information',$info);

    }

    public function updateProfileImage(Request $request){
        $user_id = $request->user_id;
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'image_file' => 'required|mimes:jpeg,jpg,png',
          ], [
            'image_file.required' => 'Image is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        if($request->hasfile('image_file')){

            $dir = 'public/images/customer';
            $single_img=$request->file('image_file');
            $file_name = "img_" .time() ."." .$single_img->getClientOriginalExtension();
            $single_img->move($dir, $file_name);

            
            $data['profile_image'] = $dir .'/' .$file_name;
            $data['updated_at'] = date('y-m-d H:i:s');
            
            $sId = User::where('id',$user_id)->update($data);
            return $this->sendResponse('Profile image updated.');
        }

        return $this->sendError('Something went wrong, try again!');
    }

    private $request;

    public function updateProfileInfo(Request $request){
        $this->request = $request;

        $user_id = $request->user_id;
        
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required',
            'email' => ['required','email:filter',
                        Rule::unique('users')->where(function ($query){
                            return $query->where('email', $this->request->email);
                        })->ignore($this->request->user_id),
            ],
            'phone' => 'required',
          ], [
            'name.required' => 'Profile name is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $data['name'] = $request->name;
        $data['phone'] = $request->phone;
        $data['email'] = $request->email;
        $data['updated_at'] = date('y-m-d H:i:s');
        $uId = User::where('id',$user_id)->update($data);
        if($uId){
            return $this->sendResponse('Profile information updated.');
        }

        return $this->sendError('Something went wrong, try again!');
    }


    public function changePassword(Request $request){
        $user_id = $request->user_id;
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            // 'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
          ], [
            'name.required' => 'Profile name is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $data['password'] = bcrypt($request->password);
        $data['updated_at'] = date('y-m-d H:i:s');
        $uId = User::where('id',$user_id)->update($data);
        if($uId){
            return $this->sendResponse('Password updated.');
        }

        return $this->sendError('Something went wrong, try again!');
    }
    

}