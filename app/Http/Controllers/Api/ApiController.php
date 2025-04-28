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
use App\Models\UserServiceLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use Log;
use App\Helpers\CustomHelper;
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

        $aRows = Category::where('is_home',1)->where('parent_id',0)->orderBy('id','DESC')->where('status',1)->get();
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
            ->orderBy('id', 'DESC')
            ->get();
    
        $result = [];
    
        foreach ($categories as $category) {
            $subcategories = Category::where('is_home', 1)
                ->where('parent_id', $category->id)
                ->where('status', 1)
                ->orderBy('id', 'DESC')
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

    // public function getLeadByPrefer()
    // {
    //      $userId = 53; // replace with your actual user ID
        
        
    //     $userServices = DB::table('user_services')
    //         ->where('user_id', $userId)
    //         ->pluck('service_id')
    //         ->toArray();
        
    //     // Step 2: Get flat list of all answers from lead_prefrences
    //     $searchTerms = DB::table('lead_prefrences')
    //         ->where('user_id', $userId)
    //         ->pluck('answers')
    //         ->toArray();
            
        
    //     $leadrequest = LeadRequest::with(['customer', 'category'])
    //     ->where('customer_id','!=',$userId)
    //         ->whereIn('service_id', $userServices)
            
    //         ->where(function ($query) use ($searchTerms) {
    //             foreach ($searchTerms as $term) {
    //                 $query->orWhereRaw("JSON_SEARCH(questions, 'one', ?) IS NOT NULL", [$term]);
    //             }
    //         })
    //         ->orderBy('id', 'DESC')
    //         ->get();
                
        
        
        
        

    //         dd($userServices,$searchTerms,$leadrequest);
    // }

}
