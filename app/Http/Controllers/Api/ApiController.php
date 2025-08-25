<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\Category;
use App\Models\Bid;
use App\Models\UserDetail;
use App\Models\UserAccreditation;
use App\Models\UserServiceDetail;
use App\Models\ProfileQuestion;
use App\Models\ProfileQA;
use App\Models\UserCardDetail;
use App\Models\UserService;
use App\Models\LeadRequest;
use App\Models\PurchaseHistory;
use App\Models\Plan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Otp;
use App\Models\UserServiceLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use Log;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoLeadBuyers;
use \Carbon\Carbon;

class ApiController extends Controller
{
    public function getCategories()
    {
        $aRows = Category::where('status',1)->get();
        return $this->sendResponse(__('Category Data'),$aRows);
    }

    public function popularServices()
    {

        $aRows = Category::where('is_home',1)->where('parent_id','<>', 0)->orderBy('id','DESC')->where('status',1)->get();
        foreach($aRows as $value){
            $value['baseurl'] = url('/').Storage::url('app/public/images/category');
        }

        return $this->sendResponse(__('Category Data'),$aRows);
    }

    public function allServices()
    {
        $categories = Category::where('is_home', 1)
            ->where('parent_id', 0)
            ->where('status', 1)
            ->get();

        $result = [];

        foreach ($categories as $category) {
            $subcategories = Category::where('is_home', 1)
                ->where('parent_id', $category->id)
                ->where('status', 1)
                ->get();

            // Only add the category if subcategories exist
            if ($subcategories->isNotEmpty()) {
                $category['subcategory'] = $subcategories;
                $category['baseurl'] = url('/') . Storage::url('app/public/images/category');
                $result[] = $category;
            }
        }

        return $this->sendResponse(__('Category Data'), $result);
    }
    public function leadsSearchServices(Request $request)
    {
        $search = $request->search; // Get search keyword from request
        $serviceid = $request->serviceid; // Get search keyword from request

        // Check if search keyword is provided; otherwise, return empty
        if (empty($search)) {
            $categories = [];
            return $this->sendResponse(__('Category Data'), $categories);
        }
        if(!empty($serviceid)){
            // Convert serviceid into an array
            $serviceIds = explode(',', $serviceid);
            $categories = Category::where('status', 1)
                            ->whereNotIn('id', $serviceIds)
                            ->where(function ($query) use ($search) {
                                $query->where('name', 'LIKE', "%{$search}%")
                                    ->orWhere('description', 'LIKE', "%{$search}%");
                            })
                            ->get();
        }else{
            $categories = Category::where('status', 1)
                              ->where(function ($query) use ($search) {
                                  $query->where('name', 'LIKE', "%{$search}%")
                                        ->orWhere('description', 'LIKE', "%{$search}%");
                              })
                              ->get();
        }



        return $this->sendResponse(__('Category Data'), $categories);
    }
    public function searchServices(Request $request)
    {
        $search = $request->search; // Get search keyword from request
        $serviceid = $request->serviceid; // Get search keyword from request

        // Check if search keyword is provided; otherwise, return empty
        if (empty($search)) {
            $categories = [];
            return $this->sendResponse(__('Category Data'), $categories);
        }
        if(!empty($serviceid)){
            $categories = Category::where('status', 1)
            ->where('id', '!=', $serviceid)
            ->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
            })
            ->where('show_in_search', '1')
            ->get();
        }else{
            $categories = Category::where('status', 1)
                              ->where(function ($query) use ($search) {
                                  $query->where('name', 'LIKE', "%{$search}%")
                                        ->orWhere('description', 'LIKE', "%{$search}%");
                              })
                              ->where('show_in_search', '1')
                              ->get();
        }



        return $this->sendResponse(__('Category Data'), $categories);
    }

    //

    public function requestOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|min:10',
        ], [
            'phone_number.required' => 'Phone number is required.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $data['otp'] = random_int(1000, 9999);
        $data['phone_number'] = $request->phone_number;

        Otp::create($data);

        $user_id = $request->user_id;

        if ($user_id) {
            $updated = User::where('id', $user_id)->update(['otp' => $data['otp']]);

            if ($updated) {
                // fetch the updated user
                $user = User::find($user_id);

                if ($user && $user->otp) {
                    return ZohoHelper::dispatchAfterResponse(function () use ($user_id) {
                        app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($user_id);
                    }, [
                        'success' => true,
                        'message' => 'OTP created successfully'
                    ]);
                }
                return $this->sendError('OTP creation failed');
            }
            return $this->sendError('OTP creation failed');

        }
        return $this->sendError('OTP creation failed');
    }


    public function verifyOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|min:10',
            'otp' => 'required|min:4',
        ], [
            'phone_number.required' => 'Phone number is required.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $dbOtp = Otp::where('phone_number', $request->phone_number)
            ->where('otp_used', '0')
            ->orderBy('id','desc')->first();
        if(!empty($dbOtp)){
            if($dbOtp->otp == $request->otp){
                $data['otp_used'] = 1;
                Otp::where('id', $dbOtp->id)->update($data);
                return $this->sendResponse('OTP verified successfully');
            }else{
                return $this->sendError('Wrong OTP, try again!');
            }
        }

        return $this->sendError('Otp not found! Please generate OTP first.');
    }

    public function testApi(Request $request, \App\Services\LeadService $leadService)
    {
        // $user_id = 13;
        // $baseQuery = $leadService->getSellerLeadsBaseQuery($user_id);
        // $allLeads = $baseQuery->orderBy('id', 'desc')->get();
        // print_r($allLeads->toArray());

        $reactBaseUrl = config('react_base_url');
        print_r($reactBaseUrl);
        exit;

        $postcode = 'WC2H 9JQ';
        $miles = 10.1;
        $radiusPostcode = CustomHelper::getPostcodesWithinRadius($postcode, $miles);
        $radiusPostcodeQuery = CustomHelper::getPostcodesWithinRadiusQuery($postcode, $miles);

        echo "<pre>";
        print_r($radiusPostcode);
        print_r($radiusPostcodeQuery->toRawSql());
        exit;
    }

}
