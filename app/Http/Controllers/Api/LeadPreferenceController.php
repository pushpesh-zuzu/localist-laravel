<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserServiceLocation;
use App\Models\UserHiringHistory;
use App\Models\RecommendedLead;
use App\Models\ServiceQuestion;
use App\Models\LeadPrefrence;
use App\Models\ActivityLog;
use App\Models\LeadRequest;
use App\Models\SaveForLater;
use App\Models\LeadStatus;
use App\Models\UserService;
use App\Models\UserDetail;
use App\Models\CreditList;
use App\Models\Category;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use \Carbon\Carbon;

class LeadPreferenceController extends Controller
{
    public function getservices(Request $request){
        $user_id = $request->user_id; 
        $categories = self::getFilterservices($user_id);
        // $serviceId = UserService::where('user_id', $user_id)->pluck('service_id')->toArray();
        // $categories = Category::whereIn('id', $serviceId)->get();
        // foreach ($categories as $key => $value) {
        //     $value['locations'] = UserServiceLocation::whereIn('user_id',[$user_id])->whereIn('service_id', [$value->id])->count();
        //     $value['leadcount'] =  LeadRequest::whereIn('service_id', [$value->id])->count();
        // }
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

  

    // public function sortByCredit(Request $request)
    // {
    //     $aVals = $request->all();
    //     $sortCredit = strtolower($aVals['sort_credit'] ?? '');  // Get the 'sort_credit' parameter

    //     // Validate the 'sort_credit' parameter (it should be 'high', 'medium', or 'low')
    //     if (!in_array($sortCredit, ['high', 'medium', 'low'])) {
    //         return $this->sendResponse(__('Invalid credit category'), [], 400);
    //     }

    //     // Use basequery to get the leads (same as in your original code)
    //     $baseQuery = $this->basequery($request->user_id);

    //     // Retrieve the leads from the database
    //     $leadRequests = $baseQuery->get();

    //     // Initialize arrays to hold leads based on customer total_credit categories
    //     $highCreditLeads = [];
    //     $mediumCreditLeads = [];
    //     $lowCreditLeads = [];

    //     // Loop through the leads and classify them based on customer_id's total_credit
    //     foreach ($leadRequests as $lead) {
    //         // Get the total_credit of the customer associated with this lead
    //         $customerTotalCredit = DB::table('users')->where('id', $lead->customer_id)->value('total_credit');

    //         // Classify the customer based on total_credit
    //         if ($customerTotalCredit >= 800) {
    //             $creditCategory = 'high';
    //         } elseif ($customerTotalCredit >= 500 && $customerTotalCredit < 800) {
    //             $creditCategory = 'medium';
    //         } else {
    //             $creditCategory = 'low';
    //         }

    //         // Categorize the lead based on the customer's credit category
    //         if ($creditCategory == 'high') {
    //             $highCreditLeads[] = $lead;
    //         } elseif ($creditCategory == 'medium') {
    //             $mediumCreditLeads[] = $lead;
    //         } else {
    //             $lowCreditLeads[] = $lead;
    //         }
    //     }

    //     // Based on the 'sort_credit' parameter, return the corresponding leads
    //     switch ($sortCredit) {
    //         case 'high':
    //             $sortedLeads = $highCreditLeads;
    //             break;

    //         case 'medium':
    //             $sortedLeads = $mediumCreditLeads;
    //             break;

    //         case 'low':
    //             $sortedLeads = $lowCreditLeads;
    //             break;
    //     }

    //     // Return the sorted leads
    //     return $this->sendResponse(__('Lead Request Data Sorted by Customer Total Credit'), $sortedLeads);
    // }

    public function getLeadRequest(Request $request)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;
        $searchName = $aVals['name'] ?? null;
        $leadSubmitted = $aVals['lead_time'] ?? null;
        $unread = $aVals['unread'] ?? null;
        $distanceFilter = $aVals['distance_filter'] ?? null;
        $creditFilter = $aVals['credits'] ?? null;
        $spotlightFilter = $aVals['lead_spotlights'] ?? null;

        $requestMiles = null;
        $requestPostcode = null;
        if ($distanceFilter && preg_match('/(\d+)\s*miles\s*from\s*(\w+)/i', $distanceFilter, $matches)) {
            $requestMiles = (int)$matches[1];
            $requestPostcode = strtoupper($matches[2]);
        }

        $creditRanges = [];
        if (!empty($creditFilter)) {
            $creditParts = array_map('trim', explode(',', $creditFilter));
            foreach ($creditParts as $part) {
                if (preg_match('/(\d+)\s*-\s*(\d+)\s*Credits/', $part, $matches)) {
                    $min = (int) $matches[1];
                    $max = (int) $matches[2];
                    $creditRanges[] = [$min, $max];
                }
            }
        }

        $spotlightConditions = [];
        if (!empty($spotlightFilter)) {
            $spotlightConditions = array_map('trim', explode(',', $spotlightFilter));
        }

        $baseQuery = $this->basequery($user_id, $requestPostcode, $requestMiles);

        // Exclude saved leads
        $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();
        // $baseQuery = $baseQuery->whereNotIn('id', $savedLeadIds);

        // Exclude leads from recommended table starts
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
        ->pluck('lead_id')
        ->toArray();

        // Merge both exclusion arrays
        $excludedLeadIds = array_merge($savedLeadIds, $recommendedLeadIds);

        if (!empty($excludedLeadIds)) {
        $baseQuery = $baseQuery->whereNotIn('id', $excludedLeadIds);
        }

        // Exclude leads from recommended table ends

        if (!empty($unread) && $unread == 1) {
            $baseQuery = $baseQuery->where('is_read', 0);
        }

        if (!empty($aVals['service_id'])) {
            $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
            $baseQuery = $baseQuery->whereIn('service_id', $serviceIds);
        }

        if (!empty($creditRanges)) {
            $baseQuery = $baseQuery->where(function ($query) use ($creditRanges) {
                foreach ($creditRanges as $range) {
                    $query->orWhereBetween('credit_score', $range);
                }
            });
        }

        if (!empty($spotlightConditions)) {
            foreach ($spotlightConditions as $condition) {
                switch (strtolower($condition)) {
                    case 'urgent requests':
                        $baseQuery = $baseQuery->where('is_urgent', 1);
                        break;
                    case 'updated requests':
                        $baseQuery = $baseQuery->where('is_updated', 1);
                        break;
                    case 'has additional details':
                        $baseQuery = $baseQuery->where('has_additional_details', 1);
                        break;
                }
            }
        }

        if ($searchName) {
            $namedLeadRequest = (clone $baseQuery)
                ->whereHas('customer', function ($query) use ($searchName) {
                    $query->where('name', 'LIKE', '%' . $searchName . '%');
                })
                ->orderBy('id', 'DESC')
                ->get();

            if ($namedLeadRequest->isNotEmpty()) {
                return $this->sendResponse(__('Lead Request Data (Filtered by Name)'), $namedLeadRequest);
            }
        }

        if ($leadSubmitted && $leadSubmitted != 'Any time') {
            $baseQuery = $baseQuery->where(function ($query) use ($leadSubmitted) {
                $now = Carbon::now();
                switch ($leadSubmitted) {
                    case 'Today':
                        $query->whereDate('created_at', $now->toDateString());
                        break;
                    case 'Yesterday':
                        $query->whereDate('created_at', $now->subDay()->toDateString());
                        break;
                    case 'Last 2-3 days':
                        $query->whereDate('created_at', '>=', Carbon::now()->subDays(3));
                        break;
                    case 'Last 7 days':
                        $query->whereDate('created_at', '>=', Carbon::now()->subDays(7));
                        break;
                    case 'Last 14+ days':
                        $query->whereDate('created_at', '<', Carbon::now()->subDays(14));
                        break;
                }
            });
        }

        // Strict matching on Questions & Answers
        $allLeads = $baseQuery->orderBy('id', 'DESC')->get();

        $preferenceMap = $this->getUserPreferenceMap($user_id);

        $filteredLeads = $allLeads->filter(function ($lead) use ($preferenceMap) {
            $leadQuestions = json_decode($lead->questions, true);
            if (!is_array($leadQuestions)) return false;

            foreach ($leadQuestions as $q) {
                $buyerAnswers = (array) $q['ans'];

                foreach ($buyerAnswers as $buyerAnswer) {
                    $buyerAnswer = trim($buyerAnswer);

                    // If buyer selected something that seller has NOT selected, reject
                    if (!isset($preferenceMap[$buyerAnswer])) {
                        return false;
                    }
                }
            }

            return true;
        });

        return $this->sendResponse(__('Lead Request Data'), $filteredLeads->values());
    }
    
    public function sortByCreditValue(Request $request)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;
        $creditFilter = $request->credit_filter;//High, Medium, Low
        $sortType = $request->sort_type; //newest,oldest
        $requestPostcode = $request->postcode ?? null;
        $requestMiles = $request->miles ?? null;
        $baseQuery = $this->basequery($user_id);

        // Exclude saved leads
        $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
        ->pluck('lead_id')
        ->toArray();

        // Merge both exclusion arrays
        $excludedLeadIds = array_merge($savedLeadIds, $recommendedLeadIds);

        if (!empty($excludedLeadIds)) {
        $baseQuery = $baseQuery->whereNotIn('id', $excludedLeadIds);
        }

        // Apply credit score filter using WHERE conditions
        if ($creditFilter) {
            $baseQuery = match ($creditFilter) {
                'High'   => $baseQuery->where('credit_score', '>=', 21),
                'Medium' => $baseQuery->whereBetween('credit_score', [5, 20]),
                'Low'    => $baseQuery->where('credit_score', '<', 5),
                // 'High'   => $baseQuery->where('credit_score', '>=', 40),
                // 'Medium' => $baseQuery->whereBetween('credit_score', [20, 39]),
                // 'Low'    => $baseQuery->where('credit_score', '<', 20),
                default  => $baseQuery,
            };
        }
        // Sort by ID direction based on sort_type
        $orderDirection = ($sortType === 'Oldest') ? 'ASC' : 'DESC';
        // Strict matching on Questions & Answers
        $allLeads = $baseQuery->orderBy('id', $orderDirection)->get();
        $preferenceMap = $this->getUserPreferenceMap($user_id);

        $filteredLeads = $allLeads->filter(function ($lead) use ($preferenceMap) {
            $leadQuestions = json_decode($lead->questions, true);
            if (!is_array($leadQuestions)) return false;

            foreach ($leadQuestions as $q) {
                $buyerAnswers = (array) $q['ans'];

                foreach ($buyerAnswers as $buyerAnswer) {
                    $buyerAnswer = trim($buyerAnswer);

                    // If buyer selected something that seller has NOT selected, reject
                    if (!isset($preferenceMap[$buyerAnswer])) {
                        return false;
                    }
                }
            }

            return true;
        });

          // Filter by credit level
        // if ($creditFilter) {
        //     $filteredLeads = $filteredLeads->filter(function ($lead) use ($creditFilter) {
        //         return match ($creditFilter) {
        //             'High' => $lead->credit_score >= 40,
        //             'Medium' => $lead->credit_score >= 20 && $lead->credit_score < 30,
        //             'Low' => $lead->credit_score < 10,
        //             default => true,
        //         };
        //     });
        // }

        return $this->sendResponse(__('Lead Request Data'), $filteredLeads->values());
    }

    public function getPendingLeads(Request $request)
    {
        $aVals = $request->all();
        $user_id = $request->user_id;
        // $baseQuery = $this->basequery($user_id);

        // Exclude saved leads
        // $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
        ->pluck('lead_id')
        ->toArray();

        $allLeads = LeadRequest::with(['customer', 'category'])
        // ->where('customer_id', '!=', $user_id)
        ->whereIn('id',$recommendedLeadIds)
        ->where('closed_status',0) //added new condition to fetched only open leads
        ->whereHas('customer', function($query) {
            $query->where('form_status', 1);
        })->where('status','pending')
        ->orderBy('id', 'DESC')
        ->get();

        // Merge both exclusion arrays
        // $excludedLeadIds = array_merge($savedLeadIds, $recommendedLeadIds);

        // if (!empty($savedLeadIds)) {
        // $baseQuery = $baseQuery->whereNotIn('id', $savedLeadIds);
        // }

        
        // Strict matching on Questions & Answers
        // $allLeads = $baseQuery->where('status','pending')->orderBy('id', 'DESC')->get();
        foreach ($allLeads as $key => $value) {
            $isActivity = ActivityLog::where('to_user_id',$user_id) 
                                 ->where('from_user_id',$value->customer_id)
                                 ->latest() 
                                 ->first(); 
            if(!empty($isActivity)){
                if($isActivity->activity_name == 'Requested a callback'){
                    $value['profile_view'] = "Requested a callback";
                    $value['profile_view_time'] = $isActivity->created_at->diffForHumans();
                }else{
                    $value['profile_view'] = $value['customer']->name." viewed your profile";
                    $value['profile_view_time'] = $isActivity->created_at->diffForHumans();
                }
                
            }else{
                $value['profile_view'] = [];
                $value['profile_view_time'] = [];
            }                     
           
        }
        $preferenceMap = $this->getUserPreferenceMap($user_id);

        $filteredLeads = $allLeads->filter(function ($lead) use ($preferenceMap) {
            $leadQuestions = json_decode($lead->questions, true);
            if (!is_array($leadQuestions)) return false;

            foreach ($leadQuestions as $q) {
                $buyerAnswers = (array) $q['ans'];

                foreach ($buyerAnswers as $buyerAnswer) {
                    $buyerAnswer = trim($buyerAnswer);

                    // If buyer selected something that seller has NOT selected, reject
                    if (!isset($preferenceMap[$buyerAnswer])) {
                        return false;
                    }
                }
            }

            return true;
        });

        return $this->sendResponse(__('Lead Request Data'), $filteredLeads->values());
    }
    
    public function getHiredLeads(Request $request)
    {
        $user_id = $request->user_id;
    
        // Get leads marked as "hired" by this seller
        $hiredLeadIds = LeadStatus::where('user_id', $user_id)
            ->where('status', 'hired')
            ->pluck('lead_id')
            ->toArray();
    
        if (empty($hiredLeadIds)) {
            return $this->sendResponse(__('No Hired Leads'), []);
        }
    
        $baseQuery = $this->basequery($user_id);
        $allLeads = $baseQuery->whereIn('id', $hiredLeadIds)
            ->orderBy('id', 'DESC')
            ->get();
    
        // Apply question-answer strict matching
        $preferenceMap = $this->getUserPreferenceMap($user_id);
        $filteredLeads = $allLeads->filter(function ($lead) use ($preferenceMap) {
            $leadQuestions = json_decode($lead->questions, true);
            if (!is_array($leadQuestions)) return false;
    
            foreach ($leadQuestions as $q) {
                $buyerAnswers = (array) $q['ans'];
                foreach ($buyerAnswers as $buyerAnswer) {
                    if (!isset($preferenceMap[trim($buyerAnswer)])) {
                        return false;
                    }
                }
            }
            return true;
        });
    
        return $this->sendResponse(__('Hired Lead Request Data'), $filteredLeads->values());
    }

    public function getHiredLeads11(Request $request)
    {
        $aVals = $request->all();
        $user_id = $aVals['user_id'];

          // Get only those leads where this seller was hired
         $hiredLeadIds = RecommendedLead::where('seller_id', $user_id)
                                        ->where('status', 'hired') // assuming this column tracks hiring
                                        ->pluck('lead_id')
                                        ->toArray();

        if (empty($hiredLeadIds)) {
            return $this->sendResponse(__('No Hired Leads'), []);
        }
        $baseQuery = $this->basequery($user_id);

        // Exclude saved leads
        // $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();
        // $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
        // ->pluck('lead_id')
        // ->toArray();

        // // Merge both exclusion arrays
        // $excludedLeadIds = array_merge($savedLeadIds, $recommendedLeadIds);

        // if (!empty($excludedLeadIds)) {
        // $baseQuery = $baseQuery->whereNotIn('id', $excludedLeadIds);
        // }

        
        // Strict matching on Questions & Answers
        $allLeads = $baseQuery->whereIn('id', $hiredLeadIds)
        ->orderBy('id', 'DESC')
        ->get();
        $preferenceMap = $this->getUserPreferenceMap($user_id);

        $filteredLeads = $allLeads->filter(function ($lead) use ($preferenceMap) {
            $leadQuestions = json_decode($lead->questions, true);
            if (!is_array($leadQuestions)) return false;

            foreach ($leadQuestions as $q) {
                $buyerAnswers = (array) $q['ans'];

                foreach ($buyerAnswers as $buyerAnswer) {
                    $buyerAnswer = trim($buyerAnswer);

                    // If buyer selected something that seller has NOT selected, reject
                    if (!isset($preferenceMap[$buyerAnswer])) {
                        return false;
                    }
                }
            }

            return true;
        });

        return $this->sendResponse(__('Lead Request Data'), $filteredLeads->values());
    }

    public function addHiredLeads(Request $request)
    {
        $aVals = $request->all();
        $leads = LeadRequest::where('id',$aVals['lead_id'])->first();
        $users = User::where('id',$leads->customer_id)->pluck('name')->first();
        $isDataExists = LeadStatus::where('lead_id',$aVals['lead_id'])->where('status',$aVals['status_type'])->first();
        $statustype = $aVals['status_type'];
        if (!empty($leads)) {
            if ($leads->status == "hired" && $aVals['status_type'] == "hired") {
                return $this->sendError(__("You already hired this buyer, now you can't change this status"), 404);
            }
        
            // Allow updating to 'hired' or re-assigning the same status
            LeadRequest::where('id',$aVals['lead_id'])->update(['status' => $statustype]);
        
            $sendmessage = 'You hired ' . $users;
        
            if (empty($isDataExists)) {
                LeadStatus::create([
                    'lead_id' => $aVals['lead_id'],
                    'user_id' => $aVals['user_id'],
                    'status' => $statustype,
                    'clicked_from' => 1,
                ]);
            }
        } else {
            $sendmessage = 'No Leads found';
        }
        
        return $this->sendResponse($sendmessage, []);
    }

    public function submitLeads(Request $request)
    {
        $aVals = $request->all();
        $leadsreq = LeadRequest::where('id',$aVals['lead_id'])->first();
        $leads = UserHiringHistory::where('lead_id',$aVals['lead_id'])
                                  ->where('user_id',$aVals['seller_id'])
                                  ->where('name',$aVals['name'])
                                  ->first();
        if (empty($leads)) {
            UserHiringHistory::create([
                'lead_id' => $aVals['lead_id'],
                'user_id' => $aVals['seller_id'],
                'name' => $aVals['name']
            ]);
            LeadRequest::where('id',$aVals['lead_id'])->update(['status'=>'hired']);
            // $sendmessage = 'You hired this job';
            $sendmessage = 'Request submited sucessfully';
        } else {
            $sendmessage = 'Already you hired this user';
        }
        return $this->sendResponse($sendmessage, []);
    }

    // public function sortByLeadsEntries(Request $request)
    // {
    //     $user_id = $request->user_id;
    //     $sortType = $request->sort_type; // 'newest' or 'oldest'
    //     $requestPostcode = $request->postcode ?? null;
    //     $requestMiles = $request->miles ?? null;

    //     $baseQuery = $this->basequery($user_id, $requestPostcode, $requestMiles);

    //     // Exclude saved and recommended leads
    //     $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();
    //     $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)->pluck('lead_id')->toArray();
    //     $excludedLeadIds = array_merge($savedLeadIds, $recommendedLeadIds);

    //     if (!empty($excludedLeadIds)) {
    //         $baseQuery = $baseQuery->whereNotIn('id', $excludedLeadIds);
    //     }

    //     // Fetch all leads (default order newest first)
    //     $orderDirection = ($sortType === 'oldest') ? 'ASC' : 'DESC';
    //     $allLeads = $baseQuery->orderBy('id', $orderDirection)->get();

    //     $preferenceMap = $this->getUserPreferenceMap($user_id);

    //     // Match preferences strictly
    //     $filteredLeads = $allLeads->filter(function ($lead) use ($preferenceMap) {
    //         $leadQuestions = json_decode($lead->questions, true);
    //         if (!is_array($leadQuestions)) return false;

    //         foreach ($leadQuestions as $q) {
    //             $buyerAnswers = (array) $q['ans'];
    //             foreach ($buyerAnswers as $buyerAnswer) {
    //                 if (!isset($preferenceMap[trim($buyerAnswer)])) {
    //                     return false;
    //                 }
    //             }
    //         }
    //         return true;
    //     });

    //     return $this->sendResponse(__('Lead Request Data'), $filteredLeads->values());
    // }


    // ------------------------

    public function basequery($user_id, $requestPostcode = null, $requestMiles = null)
    {
        $userServices = DB::table('user_services')
            ->where('user_id', $user_id)
            ->pluck('service_id')
            ->toArray();

        $baseQuery = LeadRequest::with(['customer', 'category'])
            // ->where('customer_id', '!=', $user_id)
            ->whereIn('service_id', $userServices)
            ->where('closed_status',0) //added new condition to fetched only open leads
            ->whereHas('customer', function($query) {
                $query->where('form_status', 1);
            });

        if ($requestPostcode && $requestMiles) {
            $leadIdsWithinDistance = [];
            $leads = LeadRequest::select('id', 'postcode')
                ->where('customer_id', '!=', $user_id)
                ->where('closed_status',0) //added new condition to fetched only open leads
                ->get();
                foreach ($leads as $lead) {
                    if ($lead->postcode) {
                        $distance = $this->getDistance($requestPostcode, $lead->postcode);
                        if ($distance && ($distance <= $requestMiles)) { // <= DIRECT comparison
                            $leadIdsWithinDistance[] = $lead->id;
                        }
                    }
                }
            // foreach ($leads as $lead) {
            //     if ($lead->postcode) {
            //         $distance = $this->getDistance($requestPostcode, $lead->postcode);
            //         if ($distance && ($distance <= ($requestMiles * 1.60934))) {
            //             $leadIdsWithinDistance[] = $lead->id;
            //         }
            //     }
            // }
            $baseQuery->whereIn('id', $leadIdsWithinDistance);
        }

        return $baseQuery;
    }

    // ------------------------

    private function getUserPreferenceMap($user_id)
    {
        $rawAnswers = DB::table('lead_prefrences')
            ->where('user_id', $user_id)
            ->pluck('answers')
            ->toArray();
    
        $preferenceMap = [];
    
        foreach ($rawAnswers as $answer) {
            // Split the comma-separated string into array
            $answerArray = array_map('trim', explode(',', $answer));
            foreach ($answerArray as $ans) {
                // Assume we already know which question this answer is related to
                // For now mapping hard-coded (or you can enhance to dynamic)
                $preferenceMap[$ans] = true;
            }
        }
    
        return $preferenceMap; 
    }
    
    public function getLeadRequest_with_single_question_match(Request $request)
    {
        $aVals = $request->all();
        // $user_id = 207;
        $user_id = $request->user_id;
        $searchName = $aVals['name'] ?? null;
        $leadSubmitted = $aVals['lead_time'] ?? null;
        $unread = $aVals['unread'] ?? null;
         // Extract miles and postcode if provided
        $distanceFilter = $aVals['distance_filter'] ?? null;
        $requestMiles = null;
        $requestPostcode = null;

        if ($distanceFilter && preg_match('/(\d+)\s*miles\s*from\s*(\w+)/i', $distanceFilter, $matches)) {
            $requestMiles = (int)$matches[1];       // e.g., 10
            $requestPostcode = strtoupper($matches[2]); // e.g., SS21
        }

         // Handle credit filter input
        $creditFilter = $aVals['credits'] ?? null;
        $creditValues = [];

        if (!empty($creditFilter)) {
            // Split multiple filters by comma
            $creditParts = array_map('trim', explode(',', $creditFilter));
            foreach ($creditParts as $part) {
                if (preg_match('/(\d+)\s*-\s*(\d+)\s*Credits/', $part, $matches)) {
                    $min = (int) $matches[1];
                    $max = (int) $matches[2];
                    $creditRanges[] = [$min, $max];
                }
            }
        }
       
          // Parse Lead Spotlights filter input (Urgent, Updated, Additional Details)
        $spotlightFilter = $aVals['lead_spotlights'] ?? null;
        $spotlightConditions = [];
        if (!empty($spotlightFilter)) {
            // Example format: "Urgent requests, Updated requests, Has additional details"
            $spotlightConditions = array_map('trim', explode(',', $spotlightFilter));
        }

       
    
        $baseQuery = self::basequery($user_id, $requestPostcode, $requestMiles);

        // Exclude leads already saved by the seller
        $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();
        $baseQuery = $baseQuery->whereNotIn('id', $savedLeadIds);
        
         if (!empty($unread) && $unread == 1) {
            $baseQuery = $baseQuery->where('is_read', 0);
        }
        // Fix: use $aVals, not $aValues
        $serviceIds = [];
        if (!empty($aVals['service_id'])) {
            $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
        }

         // Apply service_id filter if provided
        if (!empty($serviceIds)) {
            $baseQuery = $baseQuery->whereIn('service_id', $serviceIds);
        }

        // Apply credit range filters if any
        if (!empty($creditRanges)) {
            $baseQuery = $baseQuery->where(function ($query) use ($creditRanges) {
                foreach ($creditRanges as $range) {
                    $query->orWhereBetween('credit_score', $range);
                }
            });
        }
         // Apply lead spotlight filters if provided
        if (!empty($spotlightConditions)) {
            foreach ($spotlightConditions as $condition) {
                switch (strtolower($condition)) {
                    case 'urgent requests':
                        $baseQuery = $baseQuery->where('is_urgent', 1);
                        break;

                    case 'updated requests':
                        $baseQuery = $baseQuery->where('is_updated', 1);
                        break;

                    case 'has additional details':
                        $baseQuery = $baseQuery->where('has_additional_details', 1);
                        break;
                }
            }
        }
    
        // If name is provided, search based on user name first
        if ($searchName) {
            $namedLeadRequest = (clone $baseQuery)
                ->whereHas('customer', function ($query) use ($searchName) {
                    $query->where('name', 'LIKE', '%' . $searchName . '%');
                })
                ->orderBy('id', 'DESC')
                ->get();
    
            // If matching data found by name, return it
            if ($namedLeadRequest->isNotEmpty()) {
                return $this->sendResponse(__('Lead Request Data (Filtered by Name)'), $namedLeadRequest);
            }
        }

         // Apply lead_time filter if provided
        if ($leadSubmitted && $leadSubmitted != 'Any time') {
            $baseQuery = $baseQuery->where(function ($query) use ($leadSubmitted) {
                $now = Carbon::now();
                switch ($leadSubmitted) {
                    case 'Today':
                        $query->whereDate('created_at', $now->toDateString());
                        break;

                    case 'Yesterday':
                        $query->whereDate('created_at', $now->subDay()->toDateString());
                        break;

                    case 'Last 2-3 days':
                        $query->whereDate('created_at', '>=', Carbon::now()->subDays(3));
                        break;

                    case 'Last 7 days':
                        $query->whereDate('created_at', '>=', Carbon::now()->subDays(7));
                        break;

                    case 'Last 14+ days':
                        $query->whereDate('created_at', '<', Carbon::now()->subDays(14));
                        break;
                }
            });
        }

    
        // If no matching data found by name or name not given, return all
        $leadrequest = $baseQuery->orderBy('id', 'DESC')->get();
        return $this->sendResponse(__('Lead Request Data'), $leadrequest);
    }

    public function basequery_old($user_id, $requestPostcode = null, $requestMiles = null){
        $userServices = DB::table('user_services')
            ->where('user_id', $user_id)
            ->pluck('service_id')
            ->toArray();
    
        // $searchTerms = DB::table('lead_prefrences')
        //     ->where('user_id', $user_id)
        //     ->pluck('answers')
        //     ->toArray();
        
        $rawAnswers = DB::table('lead_prefrences')
                            ->where('user_id', $user_id)
                            ->pluck('answers')
                            ->toArray();
                        
        $searchTerms = [];
                        
        foreach ($rawAnswers as $answer) {
            $decoded = json_decode($answer, true);
            if (is_array($decoded)) {
                $searchTerms = array_merge($searchTerms, $decoded);
            }
        }
                            
        // Base leadrequest query
        $baseQuery = LeadRequest::with(['customer', 'category'])
                                ->where('customer_id', '!=', $user_id)
                                ->whereIn('service_id', $userServices)
                                // ->where(function ($query) use ($searchTerms) {
                                //     foreach ($searchTerms as $term) {
                                //         $query->whereRaw("JSON_SEARCH(questions, 'one', ?) IS NOT NULL", [$term]);
                                //     }
                                // });
                                ->where(function ($query) use ($searchTerms) {
                                    foreach ($searchTerms as $term) {
                                        $query->orWhereRaw("JSON_SEARCH(questions, 'one', ?) IS NOT NULL", [$term]);
                                    }
                                });
        if ($requestPostcode && $requestMiles) {
            $leadIdsWithinDistance = [];
            $leads = LeadRequest::select('id', 'postcode')
                ->where('customer_id', '!=', $user_id)
                ->get();
        
            foreach ($leads as $lead) {
                if ($lead->postcode) {
                    $distance = $this->getDistance($requestPostcode, $lead->postcode); // returns in km
                    if ($distance && ($distance <= ($requestMiles * 1.60934))) {
                        $leadIdsWithinDistance[] = $lead->id;
                    }
                }
            }
        
            $baseQuery->whereIn('id', $leadIdsWithinDistance);
        }                        
        return $baseQuery;                        
    }

    public function getDistance($postcode1, $postcode2)
    {
        $encodedPostcode1 = urlencode($postcode1);
        $encodedPostcode2 = urlencode($postcode2);
        $apiKey = "AIzaSyB29PyyFmCsm_nw8ELavLskRzMPd3XEIac"; // Replace with your API key

        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$encodedPostcode1}&destinations={$encodedPostcode2}&key={$apiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] == 'OK' && isset($data['rows'][0]['elements'][0]['distance'])) {
            $distanceText = $data['rows'][0]['elements'][0]['distance']['text']; // e.g., "12.5 km"
            return floatval(str_replace(['km', ','], '', $distanceText)); // return distance as float (km)
        } else {
            return null;
        }
    }

    public function getLeadRequest1(Request $request)
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

    // public function addUserService(Request $request): JsonResponse
    // {
    //     $aVals = $request->all();
    //     $userId = $request->user_id;
    //     $validator = Validator::make($aVals, [
    //         //'service_id' => 'required|exists:services,id',
    //         'service_id' => [
    //             'required',
    //             'exists:categories,id',
    //             Rule::unique('user_services', 'service_id')->where(function ($query) use ($userId ) {
    //                 return $query->where('user_id', $userId );
    //             })
    //         ],
    //         'user_id' => 'required|exists:users,id',
    //       ],
    //       [
    //         'user_id.exists' => 'The selected user does not exist.',
    //         'service_id.exists' => 'The selected service does not exist.',
    //         'service_id.unique' => 'You have already added this service to your profile.',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors());
    //     }
        
    //     $service = UserService::createUserService($aVals['user_id'],$aVals['service_id'],0);
    //     return $this->sendResponse(__('this service added to your profile successfully'));
    // }

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
        $serviceIds = is_array($aVals['service_id']) ? $aVals['service_id'] : explode(',', $aVals['service_id']);
        if ($serviceIds) {
            foreach ($serviceIds as $serviceId) {
                //  $userService = UserService::where('user_id', $userId)
                //                     ->where('service_id', $serviceId)
                //                     ->first();

                UserService::createUserService($aVals['user_id'],$serviceId,0);
            }
        // $service = UserService::createUserService($aVals['user_id'],$aVals['service_id'],0);
            return $this->sendResponse(__('Service added to your profile successfully'));
        }else{
            return $this->sendResponse(__('Select Service to proceed'));
        }
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
        $uniqueRows = self::getFilterLocations($userId);
        // Get all locations for the user
        // $aRows = UserServiceLocation::where('user_id', $userId)
        //     ->orderBy('postcode')
        //     ->get();

        // // Group by postcode to remove duplicates (only first entry per postcode)
        // $uniqueRows = $aRows->unique('postcode')->values();

        // // Add total services per postcode
        // foreach ($uniqueRows as $value) {
        //     $value['total_services'] = $aRows->where('postcode', $value->postcode)->count();
        //     $value['leadcount'] =  LeadRequest::where('postcode', $value->postcode)->count();
        // }

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

    // public function getCreditList(): JsonResponse
    // {
    //     $aRows = CreditList::get();
    //     foreach ($aRows as $key => $value) {
    //         // Extract min and max from the credit label like "1-5 Credits"
    //         if (preg_match('/(\d+)\s*-\s*(\d+)/', $value->credits, $matches)) {
    //             $min = (int)$matches[1];
    //             $max = (int)$matches[2];
    
    //             // Count leads in leadrequest table where credit_score falls in the range
    //             $leadCount = LeadRequest::whereBetween('credit_score', [$min, $max])->count();
    //             $value['leadcount'] = $leadCount;
    //         } else {
    //             $value['leadcount'] = 0; // Default if no valid range
    //         }
    //     }
    //     return $this->sendResponse(__('Credit Data'), $aRows);
    // }

    public function leadsByFilter(Request $request){
        $aVals = $request->all();
        $user_id = $aVals['user_id'];
        $spotlights = [
            'All lead spotlights' => 'all',
            'Urgent requests' => 'is_urgent',
            'Updated requests' => 'is_updated',
            'Has additional details' => 'has_additional_details',
        ];
        
        $leadSpotlights = self::filterCount($spotlights, $user_id);
        $leadTimeCounts = self::getLeadTimeData($user_id);
        $services = self::getFilterservices1($user_id);
        $location = self::getFilterLocations1($user_id);
        $credits = self::getFilterCreditList1($user_id);
        $unread = LeadRequest::where('customer_id', '!=', $user_id)->where('is_read',0)->count();
        
        return $this->sendResponse(__('Filter Data'), [
            [
                'leadSpotlights' => $leadSpotlights,
                'leadTime' => $leadTimeCounts,
                'services' => $services,
                'location' => $location,
                'credits' => $credits,
                'unread' => $unread,
            ]
        ]);
        // return $this->sendResponse(__('Filter Data'),$datas);
    }

    public function filterCount($spotlights, $user_id){
        $leadSpotlights = [];
        $baseQuery = $this->basequery($user_id); // Apply full user filters
    
        foreach ($spotlights as $label => $column) {
            $query = clone $baseQuery; // Clone so each one is fresh
            if ($column !== 'all') {
                $query = $query->where($column, 1);
            }
            $leadSpotlights[] = [
                'spotlight' => $label,
                'count' => $query->count(),
            ];
        }
    
        return $leadSpotlights;
    }

    public static function getLeadTimeData($user_id = null)
    {
        $now = Carbon::now();
        $instance = new self(); // because basequery is not static
        $baseQuery = $instance->basequery($user_id);

        $timeFilters = [
            'Any time' => function () use ($baseQuery) {
                return (clone $baseQuery)->count();
            },
            'Today' => function () use ($baseQuery, $now) {
                return (clone $baseQuery)->whereDate('created_at', $now->toDateString())->count();
            },
            'Yesterday' => function () use ($baseQuery, $now) {
                return (clone $baseQuery)->whereDate('created_at', $now->copy()->subDay()->toDateString())->count();
            },
            'Last 2-3 days' => function () use ($baseQuery, $now) {
                return (clone $baseQuery)->whereDate('created_at', '>=', $now->copy()->subDays(3))->count();
            },
            'Last 7 days' => function () use ($baseQuery, $now) {
                return (clone $baseQuery)->whereDate('created_at', '>=', $now->copy()->subDays(7))->count();
            },
        ];

        $result = [];
        foreach ($timeFilters as $label => $callback) {
            $result[] = [
                'time' => $label,
                'count' => $callback(),
            ];
        }

        return $result;
    }

    public function getFilterservices1($user_id)
    {
        $serviceIds = UserService::where('user_id', $user_id)->pluck('service_id')->toArray();
        $categories = Category::whereIn('id', $serviceIds)->get();

        foreach ($categories as $category) {
            // Use basequery to get all lead IDs matching filters
            $leads = $this->basequery($user_id)->where('service_id', $category->id)->get();
            $category['locations'] = UserServiceLocation::where('user_id', $user_id)->where('service_id', $category->id)->count();
            $category['leadcount'] = $leads->count();
        }

        return $categories;
    }

    public function getFilterLocations1($user_id)
    {
        $aRows = UserServiceLocation::where('user_id', $user_id)->orderBy('postcode')->get();
        $uniqueRows = $aRows->unique('postcode')->values();

        foreach ($uniqueRows as $row) {
            // Use basequery and apply postcode match
            $leadCount = $this->basequery($user_id)
                            ->where('postcode', $row->postcode)
                            ->count();

            $row['total_services'] = $aRows->where('postcode', $row->postcode)->count();
            $row['leadcount'] = $leadCount;
        }

        return $uniqueRows;
    }

    public function getFilterCreditList1($user_id = null)
    {
        $creditList = CreditList::get();
    
        foreach ($creditList as $creditItem) {
            if (preg_match('/(\d+)\s*-\s*(\d+)/', $creditItem->credits, $matches)) {
                $min = (int)$matches[1];
                $max = (int)$matches[2];
    
                // Cast credit_score to integer if stored as string
                $creditItem['leadcount'] = $this->basequery($user_id)
                    ->whereRaw('CAST(credit_score AS UNSIGNED) BETWEEN ? AND ?', [$min, $max])
                    ->count();
            } else {
                $creditItem['leadcount'] = 0;
            }
        }
    
        return $creditList;
    }
    

    // public function filterCount($spotlights, $user_id){
    //     $leadSpotlights = [];
    //     foreach ($spotlights as $label => $column) {
    //         if($column == 'all'){
    //             $count = LeadRequest::where('customer_id', '!=', $user_id)->count();
    //         }else{
    //             $count = LeadRequest::where($column, 1)->where('customer_id', '!=', $user_id)->count();
    //         }
    //         $leadSpotlights[] = [
    //             'spotlight' => $label,
    //             'count' => $count,
    //         ];
    //     }
    //     return $leadSpotlights;
    // }

    // public static function getLeadTimeData($user_id = null)
    // {
    //     $now = Carbon::now();

    //     $timeFilters = [
    //         'Any time' => function () {
    //             return LeadRequest::count();
    //         },
    //         'Today' => function () use ($now) {
    //             return LeadRequest::whereDate('created_at', $now->toDateString())->count();
    //         },
    //         'Yesterday' => function () use ($now) {
    //             return LeadRequest::whereDate('created_at', $now->copy()->subDay()->toDateString())->count();
    //         },
    //         'Last 2-3 days' => function () use ($now) {
    //             return LeadRequest::whereDate('created_at', '>=', $now->copy()->subDays(3))->count();
    //         },
    //         'Last 7 days' => function () use ($now) {
    //             return LeadRequest::whereDate('created_at', '>=', $now->copy()->subDays(7))->count();
    //         },
    //     ];

    //     $result = [];
    //     foreach ($timeFilters as $label => $callback) {
    //         $result[] = [
    //             'time' => $label,
    //             'count' => $callback(),
    //         ];
    //     }

    //     return $result;
    // }

    public function getFilterservices($user_id){
        $serviceId = UserService::where('user_id', $user_id)->pluck('service_id')->toArray();
        $categories = Category::whereIn('id', $serviceId)->get();
        foreach ($categories as $key => $value) {
            $value['locations'] = UserServiceLocation::whereIn('user_id',[$user_id])->whereIn('service_id', [$value->id])->count();
            $value['leadcount'] =  LeadRequest::whereIn('service_id', [$value->id])->count();
        }
        return $categories;
    }

    public function getFilterLocations($user_id)
    {
        $aRows = UserServiceLocation::where('user_id', $user_id)->orderBy('postcode')->get();

        // Group by postcode to remove duplicates (only first entry per postcode)
        $uniqueRows = $aRows->unique('postcode')->values();

        // Add total services per postcode
        foreach ($uniqueRows as $value) {
            $value['total_services'] = $aRows->where('postcode', $value->postcode)->count();
            $value['leadcount'] =  LeadRequest::where('postcode', $value->postcode)->count();
        }

        return $uniqueRows;
    }

    // public function getFilterCreditList()
    // {
    //     $aRows = CreditList::get();
    //     foreach ($aRows as $key => $value) {
    //         // Extract min and max from the credit label like "1-5 Credits"
    //         if (preg_match('/(\d+)\s*-\s*(\d+)/', $value->credits, $matches)) {
    //             $min = (int)$matches[1];
    //             $max = (int)$matches[2];
    
    //             // Count leads in leadrequest table where credit_score falls in the range
    //             $leadCount = LeadRequest::whereBetween('credit_score', [$min, $max])->count();
    //             $value['leadcount'] = $leadCount;
    //         } else {
    //             $value['leadcount'] = 0; // Default if no valid range
    //         }
    //     }
    //     return $aRows;
    // }

    public function getLeadProfile(Request $request){
        $aVals = $request->all();
        $users = User::where('id',$aVals['customer_id'])->first();  
        if ($users) {
            // Update is_read = 1 for all lead requests of this user (or filter as needed)
            LeadRequest::where('customer_id', $users->id)->update(['is_read' => 1]);
        
            // Fetch updated lead request with relationships
            $leads = LeadRequest::with(['customer', 'category'])
                                ->where('id', $aVals['lead_id'])
                                ->where('customer_id', $users->id)
                                ->first();
        
            $users->leads = $leads;
        }
        return $this->sendResponse('Profile Data', $users);                    
    }

    public function saveForLater(Request $request){
        $aVals = $request->all();
        $isDataExists = SaveForLater::where('seller_id',$aVals['user_id'])
                                    ->where('user_id',$aVals['buyer_id'])    
                                    ->where('lead_id',$aVals['lead_id'])   
                                    ->first();
        if(empty($isDataExists)){
            $bids = SaveForLater::create([
                'seller_id' => $aVals['user_id'], //loggedin user id
                'user_id' => $aVals['buyer_id'], //buyer
                'lead_id' => $aVals['lead_id']
            ]); 
            return $this->sendResponse('Data added Sucessfully', []);   
        }      
        return $this->sendError('Data already added for this user');                                              
    }

    public function getSaveForLaterList(Request $request)
    {
        $userId = $request->user_id; // seller_id

        // Step 1: Get all lead_ids saved by this seller
        $savedLeadIds = SaveForLater::where('seller_id', $userId)
                                    ->pluck('lead_id')
                                    ->toArray();

        // Step 2: Fetch the actual lead data from LeadRequest
        $savedLeads = LeadRequest::with(['customer', 'category'])
                                ->whereIn('id', $savedLeadIds)
                                ->orderBy('id', 'DESC')
                                ->get();

        if ($savedLeads->isEmpty()) {
            return $this->sendResponse(__('Saved Leads'), [
                [
                    'savedLeads' => []
                ]
            ]);
        }else{
            return $this->sendResponse(__('Saved Leads'), [
                [
                    'savedLeads' => $savedLeads
                ]
            ]);
        }

        // return $this->sendResponse(__('Saved Leads'), $savedLeads);
    }

    public function onlineRemoteSwitch(Request $request){ 
        $aVals = $request->all();
    
        $isDataExists = User::where('id',$aVals['user_id'])->first();
        if(!empty($isDataExists)){
            $bids =  $isDataExists->update(['is_online' => $aVals['is_online']]);
            $isonline  = $aVals['is_online'];
            // return $this->sendResponse('Switched update', $isonline);   
            return $this->sendResponse(__('Switched update'), []);
        }      
        return $this->sendError('User not found');                                              
    }

    public function getOnlineRemoteSwitch(Request $request){ 
        $aVals = $request->all();
        $isDataExists = User::where('id',$aVals['user_id'])->first();
        if(!empty($isDataExists)){
            return $this->sendResponse(__('Online Switch Data'), [
                'isonline' => $isDataExists->is_online
            ]);
        }      
        return $this->sendError('User not found');                                              
    }

    public function totalCredit(Request $request){ 
        $aVals = $request->all();
    
        $isDataExists = User::where('id',$aVals['user_id'])->pluck('total_credit')->first();
        return $this->sendResponse('Switched update', $isDataExists);                                               
    }

    public function getSellerRecommendedLeads(Request $request)
    {
        $seller_id = $request->user_id; 
        $result = [];
            // Fetch all matching bids
            $bids = RecommendedLead::where('seller_id', $seller_id)
                ->orderBy('distance','ASC')
                ->get();

            // Get seller IDs and unique service IDs
            $sellerIds = $bids->pluck('buyer_id')->toArray();
            $serviceIds = $bids->pluck('service_id')->unique()->toArray();

            // Get users and categories
            $leads = LeadRequest::whereIn('customer_id', $sellerIds)
                        ->whereIn('service_id', $serviceIds)
                        ->with(['customer', 'category'])
                        ->get();
            if(!empty($leads)){
                return $this->sendResponse(__('AutoBid Data'), [
                    [
                        'leads' => $leads
                    ]
                ]);
            }else{
                return $this->sendResponse(__('AutoBid Data'), [
                    [
                        'leads' => []
                    ]
                ]);
            }
        
    }

    public function sevenDaysAutobidPause(Request $request){ 
        $aVals = $request->all();
        $userdetails = UserDetail::where('user_id',$aVals['user_id'])->first();
        if(isset($userdetails) && $userdetails != ''){
            $userdetails->update(['autobid_pause' => $aVals['autobid_pause']]);
        }else{
            $userdetails = UserDetail::create([
                'user_id'  => $aVals['user_id'],
                'autobid_pause' => $aVals['autobid_pause']
            ]);
        }
        
        if($aVals['autobid_pause'] == 1){
            $modes = 'Paused Autobid for 7 days';
        }else{
            $modes = 'Now Autobid is in active state';
        }
        $autobidpause = $aVals['autobid_pause'];
        // return $this->sendResponse($modes, $autobidpause); 
        return $this->sendResponse($modes, [
            'autobidpause' => $autobidpause
        ]);                                          
    }

    public function getSevenDaysAutobidPause(Request $request){ 
        $aVals = $request->all();
        $userdetails = UserDetail::where('user_id',$aVals['user_id'])->first();
        if(isset($userdetails) && $userdetails != ''){
            return $this->sendResponse('Seven Days autobid pause data', [
                'autobidpause' => $userdetails->autobid_pause
            ]);  
        }
            return $this->sendResponse('Data not found', []);                                  
    }
}
