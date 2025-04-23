<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Category;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Validation\Rule;
use App\Helpers\CustomHelper;
use App\Services\ZeroBounceService;
use Illuminate\Support\Str;

class UserController extends Controller
{

    protected $zeroBounceService;

    public function __construct(ZeroBounceService $zeroBounceService)
    {
        $this->zeroBounceService = $zeroBounceService;
    }

    public function index()
    {
        return response()->json(User::all(), 200);
    }

    public function registration(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $auto_bid = $request->auto_bid;
        $loggedUser = $request->loggedUser;//For checking seller/buyer
        $users = User::where('email',$request->email)->first();
        if(!empty($users) && $users != ''){
            return $this->sendError('Email already exists');
        }
        if($loggedUser == 2){
            if(!empty($users) && $users != ''){
                $token = $users->createToken('authToken', ['user_id' => $users->id])->plainTextToken;
                $users->update(['remember_token' => $token]);
                $users->remember_tokens = $token;
                return $this->sendResponse('Email already exists', $users);
            }
        }

        // if(!empty($users) && $users != ''){
        //     return $this->sendError('Email already exists');
        // }
        if($aVals['form_status'] == 1){
            $validator = self::validators($aVals,$loggedUser);
            if ($validator->fails()) {
                return $this->sendError($validator->errors());
            }
            $result = $this->zeroBounceService->validateEmail($request->email);
            if (isset($result['status']) && $result['status'] === 'invalid') {
                return $this->sendError('Email is Invalid');
            }
        }
        // if($loggedUser == 2){
            $randomString = Str::random(10);
            $aVals['password'] = Hash::make($randomString);
        // }else{
        //     $aVals['password'] = Hash::make($request->password);
        // }
        // $aVals['password'] = Hash::make($request->password);
        $randomNumber = rand(1000, 5000);
        $aVals['total_credit'] = $randomNumber;
        $user = User::create($aVals);
        $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
        $user->update(['remember_token' => $token]);
        $user->remember_tokens = $token;
        // $aVals['password'] = Hash::make($request->password);

        // $user = User::create($aVals);

        if($user && $loggedUser == 1)
        {
              // Check if service_id is an array or convert it to one
            $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);

            foreach ($serviceIds as $serviceId) {
                // Create a separate row for each service_id
                $service = UserService::createUserService($user->id, $serviceId, $auto_bid);

                if ($service) {
                    $aLocations['service_id'] = $serviceId;
                    $aLocations['user_service_id'] = $service->id;
                    $aLocations['user_id'] = $user->id;
                    if(isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1){
                        $aLocations['miles'] = 0;
                        $aLocations['nation_wide'] = 1;
                    }else{
                        if (isset($aVals['miles1']) && isset($aVals['miles2']) && !empty($aVals['miles1']) && !empty($aVals['miles2'])) {
                            $aLocations['miles'] = $aVals['miles1'] + $aVals['miles2'];
                        } elseif (!empty($aVals['miles1'])) {
                            $aLocations['miles'] = $aVals['miles1'];
                        } else {
                            $aLocations['miles'] = 0; // Default value to avoid undefined variable issues
                        }
                        // $aLocations['postcode'] = $aVals['postcode'];
                        $aLocations['nation_wide'] = 0;
                    }
                    if(isset($aVals['postcode']) && $aVals['postcode'] !=''){
                        $aLocations['postcode'] = $aVals['postcode'];
                    }else{
                        $aLocations['postcode'] = '000000';
                    }
                    // dd($aLocations['nation_wide']);
                    // if (isset($aVals['miles1']) && isset($aVals['miles2']) && !empty($aVals['miles1']) && !empty($aVals['miles2'])) {
                    //     $aLocations['miles'] = $aVals['miles1'] + $aVals['miles2'];
                    // } elseif (!empty($aVals['miles1'])) {
                    //     $aLocations['miles'] = $aVals['miles1'];
                    // } else {
                    //     $aLocations['miles'] = 0; // Default value to avoid undefined variable issues
                    // }
                    // $aLocations['postcode'] = $aVals['postcode'];
                    UserServiceLocation::createUserServiceLocation($aLocations);
                }
            }
            // $service = UserService::createUserService($user->id,$aVals['service_id']);
            // if($service)
            // {
            //     $aLocations['service_id'] = $service->id;
            //     $aLocations['user_id'] = $user->id;
            //     $aLocations['miles'] = $aVals['miles'];
            //     $aLocations['postcode'] = $aVals['postcode'];
            //     UserServiceLocation::createUserServiceLocation($aLocations);
            // }
        }
        // CustomHelper::sendEmail();
        if($aVals['active_status'] == 1){
            $modes = 'Seller Registration';
        }else{
            $modes = 'Buyer Registration';
        }
       
        // CustomHelper::sendEmail(array("to" => $aVals['email'],"subject" => $modes, "body" => "Thankyou for registration",'receiver' => $aVals['name']));
        // CustomHelper::sendEmail(array("to" => $aVals['email'],"subject" => $modes, "body" => "Thankyou for registration",'receiver' => $aVals['name']));
        CustomHelper::sendEmail(array("to" => $aVals['email'],"subject" =>  $modes, "body" => "Thankyou for registration",'receiver' => $aVals['name']));
        return $this->sendResponse('Registration Sucessful.', $user);
        // return $this->sendResponse(__('registration successfully',$user));

    }

    public function validators($aVals,$loggedUser){
        if($loggedUser == 1){
            $validator = Validator::make($aVals, [
                'miles1' => 'required',
                'miles2' => 'sometimes',
                'postcode' => 'required',
                'service_id' => 'required',
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
            //     'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&]/|max:16'
            //   ], [
            //     'password.min' => 'The new password must be at least 8 characters.',
            //     'password.regex' => 'The new password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
            ]);
            
        }else{
            $validator = Validator::make($aVals, [
                'name' => 'sometimes',
                'email' => 'required|email|unique:users,email',
                // 'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&]/|max:16'
              ], [
                // 'password.min' => 'The new password must be at least 8 characters.',
                // 'password.regex' => 'The new password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
            ]);
        }
        return $validator;
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        if(!Auth::attempt(['email'=>$request->email, 'password'=>$request->password])){
            return $this->sendError("Unable to Login due to Invalid Credentials");
        }

        $user = Auth::user();
        if ($user && $user->form_status != 0) {
                if($user->status == 0)
                {
                    return $this->sendError("User is inactive");
                }
                $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
                $user->update(['remember_token' => $token]);
                $user->remember_tokens = $token;
                return $this->sendResponse('Login Successfully.', $user);
        } else {
                return $this->sendError('You are not register or invalid user');
        }

        // $user = User::where('email', $request->email)->first();
        // if ($user) {
        //         if($user->status == 0)
        //         {
        //             return $this->sendError("User is inactive");
        //         }

        //         if(Hash::check($request->password, $user->password)){
        //             return $this->sendResponse('Login Successfully.', $user);
        //     }else{
        //         return $this->sendError('Password Invalid. Please try again.');
        //     }
        // } else {
        //         return $this->sendError('You are not register or invalid user');
        // }
    }

    // public function registration(Request $request): JsonResponse
    // {

    //     $aVals = $request->all();
    //     $validator = Validator::make($aVals, [
    //         'miles' => 'required',
    //         'postcode' => 'required',
    //         'service_id' => 'required',
    //         'name' => 'required',
    //         'email' => 'required|email|unique:users,email',
    //         'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&]/|max:16',
    //         'phone' => 'required'
    //       ], [
    //         'password.min' => 'The new password must be at least 8 characters.',
    //         'password.regex' => 'The new password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
    //         'mobile.required' => 'The mobile number is required.',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors());
    //     }
     

    //     $aVals['password'] = Hash::make($request->password);

    //     $user = User::create($aVals);

    //     if($user)
    //     {
    //         $service = UserService::createUserService($user->id,$aVals['service_id']);

    //         if($service)
    //         {
    //             $aLocations['service_id'] = $service->id;
    //             $aLocations['user_id'] = $user->id;
    //             $aLocations['miles'] = $aVals['miles'];
    //             $aLocations['postcode'] = $aVals['postcode'];
    //             UserServiceLocation::createUserServiceLocation($aLocations);
    //         }

            

    //     }

    //     return $this->sendResponse(__('register successfully'));

    // }

    // public function login(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email',
    //         'password' => 'required'
    //     ]);

    //     if($validator->fails()){
    //         return $this->sendError($validator->errors());
    //     }

    //     $user = User::where('email', $request->email)->first();
    //     if ($user) {
    //         if($user->status == 0)
    //         {
    //             return $this->sendError("User is inactive");
    //         }

    //         if(Hash::check($request->password, $user->password)){
    //             return $this->sendResponse('Login Successfully.', $user);
    //     }else{
    //         return $this->sendError('Password Invalid. Please try again.');
    //     }
    // } else {
    //          return $this->sendError('You are not register or invalid user');
    //     }
    // }

    // public function show($id)
    // {
    //     $user = User::findOrFail($id);
    //     return response()->json($user, 200);
    // }

    // public function update(Request $request, $id)
    // {
    //     $user = User::findOrFail($id);
    //     $user->update($request->all());
    //     return response()->json($user, 200);
    // }

    // public function destroy($id)
    // {
    //     User::destroy($id);
    //     return response()->json(null, 204);
    //

    

    // public function addUserLocation(Request $request): JsonResponse
    // {
    //     $aVals = $request->all();
    //     $userId = $aVals['user_id'];

    //     $validator = Validator::make($aVals, [
    //         'service_id' => [
    //             'required',
    //             'exists:categories,id',
    //         ],
    //         'user_id' => 'required|exists:users,id',
    //     ],
    //     [
    //         'user_id.exists' => 'The selected user does not exist.',
    //         'service_id.exists' => 'The selected service does not exist.',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors());
    //     }

    //     $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);

    //     foreach ($serviceIds as $serviceId) {
    //         // Get corresponding user_service_id
    //         $userService = UserService::where('user_id', $userId)
    //                                 ->where('service_id', $serviceId)
    //                                 ->first();

    //         if (!$userService) {
    //             continue; // skip if user_service does not exist
    //         }

    //         $userServiceId = $userService->id;

    //         $postcode = isset($aVals['postcode']) && $aVals['postcode'] !== '' ? $aVals['postcode'] : '000000';
    //         $miles = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 0 : $aVals['miles'];
    //         $nationWide = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 1 : 0;

    //         // Check if location already exists
    //         $location = UserServiceLocation::where('user_id', $userId)
    //                                         ->where('service_id', $serviceId)
    //                                         ->first();

    //         if ($location) {
    //             // Update existing location
    //             $location->update([
    //                 'postcode' => $postcode,
    //                 'miles' => $miles,
    //                 'nation_wide' => $nationWide
    //             ]);
    //         } else {
    //             // Create new location
    //             UserServiceLocation::create([
    //                 'user_id' => $userId,
    //                 'user_service_id' => $userServiceId,
    //                 'service_id' => $serviceId,
    //                 'postcode' => $postcode,
    //                 'miles' => $miles,
    //                 'nation_wide' => $nationWide
    //             ]);
    //         }
    //     }

    //     return $this->sendResponse(__('Location added successfully'));
    // }

    


    // public function editUserLocation(Request $request): JsonResponse
    // {
    //     $aVals = $request->all();
    //     $userId = $aVals['user_id'];

    //     $validator = Validator::make($aVals, [
    //         'service_id' => [
    //             'required',
    //             'exists:categories,id',
    //         ],
    //         'user_id' => 'required|exists:users,id',
    //     ],
    //     [
    //         'user_id.exists' => 'The selected user does not exist.',
    //         'service_id.exists' => 'The selected service does not exist.',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors());
    //     }

    //     $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);

    //     foreach ($serviceIds as $serviceId) {
    //         // Get corresponding user_service_id
    //         $userService = UserService::where('user_id', $userId)
    //                                 ->where('service_id', $serviceId)
    //                                 ->first();

    //         if (!$userService) {
    //             continue; // skip if user_service does not exist
    //         }

    //         $userServiceId = $userService->id;

    //         $postcode = isset($aVals['postcode']) && $aVals['postcode'] !== '' ? $aVals['postcode'] : '000000';
    //         $miles = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 0 : $aVals['miles'];
    //         $nationWide = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 1 : 0;

    //         // Check if location already exists
    //         $location = UserServiceLocation::where('user_id', $userId)
    //                                         ->where('service_id', $serviceId)
    //                                         ->first();

    //         if ($location) {
    //             // Update existing location
    //             $location->update([
    //                 'postcode' => $postcode,
    //                 'miles' => $miles,
    //                 'nation_wide' => $nationWide
    //             ]);
    //         } else {
    //             // Create new location
    //             UserServiceLocation::create([
    //                 'user_id' => $userId,
    //                 'user_service_id' => $userServiceId,
    //                 'service_id' => $serviceId,
    //                 'postcode' => $postcode,
    //                 'miles' => $miles,
    //                 'nation_wide' => $nationWide
    //             ]);
    //         }
    //     }

    //     return $this->sendResponse(__('Location added successfully'));
    // }


    


    


    // public function getUserLocations(Request $request): JsonResponse
    // {
    //     $aVals = $request->all();
    //     $userId = $aVals['user_id'];
    //     $aRows = UserServiceLocation::where('user_id',$userId)->orderby('postcode')->get();
    //     foreach ($aRows as $key => $value) {
    //         $value['total_services'] = UserServiceLocation::where('user_id',$userId)->where('postcode',$value->postcode)->count();
    //     }
    //     // $aRows = UserServiceLocation::where('user_id',$userId)
    //     // ->join('categories', 'categories.id', '=', 'user_service_locations.service_id')
    //     // ->select('user_service_locations.*', 'categories.name')
    //     // ->get();
    //     return $this->sendResponse(__('User Service Data'),$aRows);
    // }
    
    public function switchUser(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];
        $userType = $aVals['user_type']; // 1 = buyer, 2 = seller
    
        $user = User::find($userId);
    
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        // If user_type is already 3, don't change it again — only update active_status
        if ($user->user_type == 3) {
            if ($userType == 1) {
                $user->active_status = 1;
                $mode = 'Seller';
            } elseif ($userType == 2) {
                $user->active_status = 2;
                $mode = 'Buyer';
            } else {
                return response()->json(['error' => 'Invalid user type'], 400);
            }
    
            $user->save();
            return $this->sendResponse(__('Switched to ' . $mode));
        }
    
        // Update user_type and active_status if user_type is not 3 yet
        if ($userType == 2) {
            if ($user->user_type == 1) {
                $user->user_type = 3; // Upgrade to Both
            } else {
                $user->user_type = 2; // Only Seller
            }
            $user->active_status = 2;
            $mode = 'Buyer';
        } elseif ($userType == 1) {
            if ($user->user_type == 2) {
                $user->user_type = 3; // Upgrade to Both
            } else {
                $user->user_type = 1; // Only Buyer
            }
            $user->active_status = 1;
            $mode = 'Seller';
        } else {
            return response()->json(['error' => 'Invalid user type'], 400);
        }
    
        $user->save();
        return $this->sendResponse(__('Switched to ' . $mode));
    }


    // public function switchUser(Request $request): JsonResponse
    // {
    //     $aVals = $request->all();
    //     $userId = $aVals['user_id'];
    //     $userType = $aVals['user_type']; // 'buyer' or 'seller'

    
    //     $user = User::find($userId);

    //     if (!$user) {
    //         return response()->json(['error' => 'User not found'], 404);
    //     }
        
    //     if ($user->user_type == 3) {
    //         return $this->sendResponse(__('User already has both Buyer and Seller roles.'));
    //     }   

    //     // Determine new type and active_status
    //     if ($userType == 2) {
    //         if ($user->user_type == 1) { 
    //             $user->user_type = 3; // Both Seller & Buyer
    //         } 
    //         else {
    //             $user->user_type = 2; // Only Seller
    //         }
    //         $user->active_status = 2;
    //     } elseif ($userType == 1) {
    //         if ($user->user_type == 2) { 
    //             $user->user_type = 3; // Both Seller & Buyer
    //         } else {
    //             $user->user_type = 1; // Only Buyer
    //         }
    //         $user->active_status = 1;
    //     } else {
    //         return response()->json(['error' => 'Invalid user type'], 400);
    //     }

    //     // Save updates
    //     $user->save();
    //     if($userType == 1){
    //         $users = 'Seller';
    //     }else{
    //         $users = 'Buyer';
    //     }
    //     return $this->sendResponse(__('Switched to '.$users));
    // }

    public function editProfile(Request $request): JsonResponse
    {
        $users = User::where('id',$request->user_id)->first();
        return $this->sendResponse(__('User Profile Data'), $users);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $validator = $validator = Validator::make($aVals, [
            'name' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($request->user_id), // Exclude current user's email
            ],
            'phone' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $user = User::where('id',$request->user_id)->update([
            'name'=>$request->name,
            'email'=>$request->email,
            'phone'=>$request->phone,
        ]);
        return $this->sendResponse(__('User Profile updated'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            // Remove token from `remember_token` column
            $user->update(['remember_token' => null]);

            // Revoke the current token (for Sanctum)
            $request->user()->currentAccessToken()->delete();

            // Optionally revoke all tokens (for broader logout)
            // $user->tokens()->delete();

            return response()->json([
                'message' => 'Logout successful'
            ], 200);
        }

        return response()->json([
            'error' => 'Unauthenticated'
        ], 401);
    }

    public function updateProfileImage(Request $request)
    {
        try {
            $userId = $request->user_id;
             $users = User::where('id',$userId)->first();   
             
                if ($request->hasFile('image')) {
                    $imagePath =  CustomHelper::fileUpload($request->image,'users');
                    // $imagePath = $this->uploadImage($request->file('image'), 'users');
                    $users->profile_image = $imagePath; 
                }
            

            if($users->save()){
                return $this->sendResponse(__('Profile Image Updated Successfully'));
            }else{
                return $this->sendError('Something went wrong. Please try again later!');
            }
            

        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }

    public function changePassword(Request $request){
        $userId = $request->user_id;
        $password = $request->password;
        $user = User::where('id',$userId)->first();  
        $user->update(['password' => $password]);
        return $this->sendResponse(__('Password changed Successfully'));
    }

}
