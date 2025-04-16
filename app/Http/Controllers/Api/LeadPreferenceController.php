<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserServiceLocation;
use App\Models\ServiceQuestion;
use App\Models\LeadPrefrence;
use App\Models\LeadRequest;
use App\Models\UserService;
use App\Models\Category;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;

class LeadPreferenceController extends Controller
{
    public function getservices(Request $request){
        $user_id = $request->user_id; 
        $serviceId = UserService::where('user_id', $user_id)->pluck('service_id')->toArray();
        $categories = Category::whereIn('id', $serviceId)->get();
        foreach ($categories as $key => $value) {
            $value['locations'] = UserServiceLocation::whereIn('user_id',[$user_id])->whereIn('service_id', [$value->id])->count();
        }
        return $this->sendResponse(__('Service Data'), $categories);
    }

    public function questionAnswer(Request $request)
    {
        $service_id = $request->service_id; 
    
        if (empty($service_id)) {
            return $this->sendResponse(__('Category Data'), []);
        }
    
        // Fetch all records where 'category' matches the given service_id
        $categories = ServiceQuestion::where('category', $service_id)
                                 ->where('status', 1)
                                 ->orderBy('id', 'DESC')
                                 ->get();
    
        return $this->sendResponse(__('Category Data'), $categories);
    }

    public function getServiceWiseLocation(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        // Get all locations for the user
        $aRows = UserServiceLocation::where('user_id', $userId)
                                    ->whereIn('service_id', [$aVals['service_id']])
                                    ->get();


        return $this->sendResponse(__('User Service Data'), $aRows);
    }

    public function getleadpreferences(Request $request): JsonResponse
    {
        $user_id = $request->user_id; 
        $service_id = $request->service_id; 
        $leadPreference = ServiceQuestion::where('category', $service_id)->get();
        if(count($leadPreference)>0){
            $questions = [];
            foreach($leadPreference as $value){
                $value['answers'] = LeadPrefrence::where('question_id', $value->id)
                                                    ->where('user_id', $user_id)
                                                    ->pluck('answers')
                                                    ->first();
            }
            $leadPreferences = $leadPreference;
        }else{
            $leadPreferences = ServiceQuestion::where('category', $service_id)->get();
            
        }                          
        return $this->sendResponse(__('Lead Preferences Data'), $leadPreferences);                              
    }

    public function leadpreferences(Request $request): JsonResponse
    {
        $request->validate([
            'service_id'   => 'required',
            'user_id'      => 'required|integer',
            'question_id'  => 'required|array', // Expecting multiple question IDs
            'answers'      => 'required|array', // Expecting multiple answers
        ]);

        $insertedOrUpdatedData = [];

        foreach ($request->question_id as $index => $questionId) {
            $answers = $request->answers[$index] ?? '';

            // Clean and format answers (comma-separated)
            $cleanedAnswer = preg_replace('/\s*,\s*/', ',', $answers);
            $cleanedAnswer = rtrim($cleanedAnswer, ','); // Remove trailing comma

            // Check if an entry exists
            $leadPreference = LeadPrefrence::where('service_id', $request->service_id)
                ->where('user_id', $request->user_id)
                ->where('question_id', $questionId)
                ->first();

            if ($leadPreference) {
                // Update existing record
                $leadPreference->update(['answers' => $cleanedAnswer]);
            } else {
                // Create a new record
                $leadPreference = LeadPrefrence::create([
                    'service_id'  => $request->service_id,
                    'question_id' => $questionId,
                    'user_id'     => $request->user_id,
                    'answers'     => $cleanedAnswer,
                ]);
            }

            $insertedOrUpdatedData[] = $leadPreference;
        }

     
        return $this->sendResponse(__('Data processed successfully'), $insertedOrUpdatedData);   
    }

    public function removeService(Request $request){
        $user_id = $request->user_id; 
        $serviceid = $request->service_id; 
        UserService::where('user_id',$user_id)->where('service_id',$serviceid)->delete();
        return $this->sendResponse(__('Service deleted Sucessfully')); 
    }

    public function getLeadRequest(Request $request)
    {
        $user_id = $request->user_id;
        $userServices = DB::table('user_services')
            ->where('user_id', $user_id)
            ->pluck('service_id')
            ->toArray();
        
        $searchTerms = DB::table('lead_prefrences')
            ->where('user_id', $user_id)
            ->pluck('answers')
            ->toArray();
            
        
        $leadrequest = LeadRequest::with(['customer', 'category'])
        ->where('customer_id','!=',$user_id)
        ->whereIn('service_id', $userServices)
        
        ->where(function ($query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $query->orWhereRaw("JSON_SEARCH(questions, 'one', ?) IS NOT NULL", [$term]);
            }
        })
        ->orderBy('id', 'DESC')
        ->get();
            
            return $this->sendResponse(__('Lead Request Data'), $leadrequest);

    }

    public function pendingLeads(Request $request)
    {
        $aValues = $request->all();
        $serviceIds = is_array($aValues['service_id']) ? $aValues['service_id'] : explode(',', $aValues['service_id']);
        $leadcount = LeadRequest::whereIn('service_id', $serviceIds)
                            ->get()->count();
        return $this->sendResponse('Pending Leads', $leadcount);
    }

    public function addUserService(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $request->user_id;
        $validator = Validator::make($aVals, [
            //'service_id' => 'required|exists:services,id',
            'service_id' => [
                'required',
                'exists:categories,id',
                Rule::unique('user_services', 'service_id')->where(function ($query) use ($userId ) {
                    return $query->where('user_id', $userId );
                })
            ],
            'user_id' => 'required|exists:users,id',
          ],
          [
            'user_id.exists' => 'The selected user does not exist.',
            'service_id.exists' => 'The selected service does not exist.',
            'service_id.unique' => 'You have already added this service to your profile.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $service = UserService::createUserService($aVals['user_id'],$aVals['service_id'],0);
        return $this->sendResponse(__('this service added to your profile successfully'));
    }

    public function getUserServices(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        $aRows = UserService::where('user_id',$userId)
        ->join('categories', 'categories.id', '=', 'user_services.service_id')
        ->select('user_services.*', 'categories.name')
        ->get();
        return $this->sendResponse(__('User Service Data'),$aRows);

    }

    public function addUserLocation(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];
        $validator = Validator::make($aVals, [
            //'service_id' => 'required|exists:services,id',
            'service_id' => [
                'required',
                'exists:categories,id',
            ],
            'user_id' => 'required|exists:users,id',
          ],
          [
            'user_id.exists' => 'The selected user does not exist.',
            'service_id.exists' => 'The selected service does not exist.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $userlocations = UserServiceLocation::where('user_id',$userId)->where('postcode',$aVals['postcode'])->first();
        
        if(isset($userlocations) && $userlocations !=''){
            return $this->sendError('Postcode with the same user already exists');
        }
        $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
        if ($serviceIds) {
            foreach ($serviceIds as $serviceId) {
                 $userService = UserService::where('user_id', $userId)
                                    ->where('service_id', $serviceId)
                                    ->first();

                    if (!$userService) {
                        continue; // skip if user_service does not exist
                    }
        
                    $userServiceId = $userService->id;
                    $postcode = isset($aVals['postcode']) && $aVals['postcode'] !== '' ? $aVals['postcode'] : '000000';
                    $miles = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 0 : $aVals['miles'];
                    $nationWide = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 1 : 0;

           
                $aLocations['service_id'] = $serviceId;
                $aLocations['user_service_id'] = $userServiceId;
                $aLocations['user_id'] = $aVals['user_id'];
                $aLocations['postcode'] =$postcode;
                $aLocations['miles'] = $miles;
                $aLocations['nation_wide'] = $nationWide;
                UserServiceLocation::createUserServiceLocation($aLocations);
            }
            return $this->sendResponse(__('Location updated successfully'));
        }else{
            return $this->sendResponse(__('Select Service to proceed'));
        }
    }

    public function getUserLocations(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        // Get all locations for the user
        $aRows = UserServiceLocation::where('user_id', $userId)
            ->orderBy('postcode')
            ->get();

        // Group by postcode to remove duplicates (only first entry per postcode)
        $uniqueRows = $aRows->unique('postcode')->values();

        // Add total services per postcode
        foreach ($uniqueRows as $value) {
            $value['total_services'] = $aRows->where('postcode', $value->postcode)->count();
        }

        return $this->sendResponse(__('User Service Data'), $uniqueRows);
    }

    public function editUserLocation(Request $request): JsonResponse
    {
        $aVals = $request->all();
        $userId = $aVals['user_id'];

        $validator = Validator::make($aVals, [
            'service_id' => [
                'required',
                'exists:categories,id',
            ],
            'user_id' => 'required|exists:users,id',
        ],
        [
            'user_id.exists' => 'The selected user does not exist.',
            'service_id.exists' => 'The selected service does not exist.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
        
        
        // Step 1: Remove entries not in the new list
        UserServiceLocation::where('user_id', $userId)
            ->whereIn('postcode', [$aVals['postcode_old']])
            ->delete();
        
        // Step 2: Update or create the rest
        foreach ($serviceIds as $serviceId) {
            $userService = UserService::where('user_id', $userId)
                                    ->where('service_id', $serviceId)
                                    ->first();

            if (!$userService) {
                continue; // skip if user_service does not exist
            }
            
            $userServiceId = $userService->id;
            $postcode = isset($aVals['postcode']) && $aVals['postcode'] !== '' ? $aVals['postcode'] : '000000';
            $miles = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 0 : $aVals['miles'];
            $nationWide = isset($aVals['nation_wide']) && $aVals['nation_wide'] == 1 ? 1 : 0;
           
            $aLocations['service_id'] = $serviceId;
            $aLocations['user_service_id'] = $userServiceId;
            $aLocations['user_id'] = $aVals['user_id'];
            $aLocations['postcode'] =$postcode;
            $aLocations['miles'] = $miles;
            $aLocations['nation_wide'] = $nationWide;
            UserServiceLocation::createUserServiceLocation($aLocations);
        }

        return $this->sendResponse(__('Location updated successfully'));
    }

    public function removeLocation(Request $request)
    {
        $aValues = $request->all();
        UserServiceLocation::whereIn('postcode', [$aValues['postcode']])
                            ->where('user_id', $aValues['user_id'])
                            ->delete();
        return $this->sendResponse('Location deleted sucessfully', []);
    }

    
}
