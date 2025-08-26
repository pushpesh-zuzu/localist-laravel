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

    public function homeServices()
    {

        $aRows = Category::where('is_home',1)->where('parent_id','<>', 0)->orderBy('id','DESC')->where('status',1)->get();
        foreach($aRows as $value){
            $value['baseurl'] = url('/').Storage::url('app/public/images/category');
        }

        return $this->sendResponse(__('Home Services'),$aRows);
    }

    public function popularServices()
    {

        $aRows = Category::where('is_popular',1)->where('parent_id','<>', 0)->orderBy('id','DESC')->where('status',1)->get();
        foreach($aRows as $value){
            $value['baseurl'] = url('/').Storage::url('app/public/images/category');
        }

        return $this->sendResponse(__('Popular Services'),$aRows);
    }

    public function popularUserServices(Request $request)
    {
        $userId = $request->user_id;

        $userServices = UserService::where('user_id', $userId)
            ->pluck('service_id')
            ->toArray();

        $aRows = Category::where('is_popular',1)->where('parent_id','<>', 0)->orderBy('id','DESC')->where('status',1)
        ->whereHas('serviceQuestions')
        ->whereNotIn('id', $userServices)
        ->get();
        foreach($aRows as $value){
            $value['baseurl'] = url('/').Storage::url('app/public/images/category');
        }

        return $this->sendResponse(__('Popular User Services'),$aRows);
    }

    private function flattenCategories($categories) {
        $result = [];

        foreach ($categories as $category) {
            // only include if show_in_search = 1
            if (isset($category['show_in_search']) && $category['show_in_search'] == 1) {
                $item = $category;
                unset($item['home_subsectors'], $item['subsectors']);
                $result[] = $item;
            }

            // recurse into children
            if (!empty($category['home_subsectors'])) {
                $result = array_merge($result, $this->flattenCategories($category['home_subsectors']));
            }

            if (!empty($category['subsectors'])) {
                $result = array_merge($result, $this->flattenCategories($category['subsectors']));
            }
        }

        return $result;
    }

    public function allServices()
    {
        $categories = Category::with(['homeSubsectors'])
            ->where('is_home', 1)
            ->where('parent_id', '0')->get()->toArray();
        $result = [];
        foreach ($categories as $category) {
            $item = $category;
            unset($item['home_subsectors'], $item['subsectors']);
            $item['subcategory'] = $this->flattenCategories($category['home_subsectors']);
            $item['baseurl'] = url('/') . Storage::url('app/public/images/category');
            $result[] = $item;
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

    public function searchAvailableServices(Request $request)
    {
    $search = $request->search;
    $serviceid = $request->serviceid;
    $userId = $request->user_id; // frontend should pass user_id

    if (empty($search)) {
        return $this->sendResponse(__('Category Data'), []);
    }

    // Fetch user's existing services
    $userServices = UserService::where('user_id', $userId)
        ->pluck('service_id')
        ->toArray();

    $query = Category::select('categories.*')
        ->join('service_questions', 'categories.id', '=', 'service_questions.category')
        ->where('categories.status', 1)
        ->where(function ($q) use ($search) {
            $q->where('categories.name', 'LIKE', "%{$search}%")
              ->orWhere('categories.description', 'LIKE', "%{$search}%");
        })
        ->where('show_in_search', '1')
        ->distinct();

    if (!empty($serviceid)) {
        $query->where('categories.id', '!=', $serviceid);
    }

    // ✅ apply exclusion only if user already has services
    if (!empty($userServices)) {
        $query->whereNotIn('categories.id', $userServices);
    }
    $categories = $query->get();

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

        $start = microtime(true);
        $lead = LeadRequest::find($request->lead_id);
        \Log::info('lead took ' . (microtime(true) - $start) . ' seconds');

        $result = $leadService->getAllSellers($lead);
        \Log::info('getAllSellers took ' . (microtime(true) - $start) . ' seconds');

        echo "<pre>";
        print_r($result);
        exit;
    }

}
