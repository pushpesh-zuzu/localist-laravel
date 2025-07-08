<?php

namespace App\Services;


use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserServiceLocation;
use App\Models\UserResponseTime;
use App\Models\RecommendedLead;
use App\Models\ServiceQuestion;
use App\Models\LeadPrefrence;
use App\Models\UniqueVisitor;
use App\Models\SaveForLater;
use App\Models\ActivityLog;
use App\Models\LeadRequest;
use App\Models\UserService;
use App\Models\UserDetail;
use App\Models\CreditList;
use App\Models\SellerNote;
use App\Models\Category;
use App\Models\PlanHistory;
use App\Models\User;
use App\Models\AutobidStatusLog;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator, Http
};

use Illuminate\Support\Facades\Storage;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helpers\CustomHelper;
use App\Models\NotificationSetting;
use App\Models\NotificationLog;
use App\Services\ZohoService;

class LeadService
{
    public function getSellerLeads($user_id, $requestPostcode = null, $requestMiles = null, $filters = [])
    {
        
        $baseQuery = $this->getSellerLeadsBaseQuery($user_id, $requestPostcode, $requestMiles, $filters);

        $allLeads = $baseQuery->orderBy('id', 'desc')->get();

        //Macting as per seller pref
        $allLeads = $this->leadsAccordingTOSellerPref($user_id, $allLeads);

        return $allLeads;
    }

    public function getSellerLeadsBaseQuery($user_id, $requestPostcode = null, $requestMiles = null, $filters = []){
        $userServices = UserService::where('user_id',$user_id)->select('service_id')->get();
        //get all types of locations
        $ulNationWide = UserServiceLocation::where('user_id', $user_id)->where('nation_wide','1')->get();
        $ulDistance = UserServiceLocation::where('user_id', $user_id)->where('type','Distance')->get()->toArray();
        $ulTravel = UserServiceLocation::where('user_id', $user_id)->where('type','Travel Time')->get()->toArray();
        $ulMap = UserServiceLocation::where('user_id', $user_id)->where('type','Draw on Map')->get()->toArray();

        //get Nation Wide services
        $nwServices = [];
        foreach($ulNationWide as $ul){
            array_push($nwServices, $ul->service_id);
        }

        //remove duplicate services from array
        $nwServices = array_unique($nwServices);

        //remove location if it is nation wide
        $ulDistance = array_filter($ulDistance, function($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });
        $ulTravel = array_filter($ulTravel, function($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });
        $ulMap = array_filter($ulMap, function($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });


        //add other services
        $otherServices = [];
        foreach($ulDistance as $d){
            array_push($otherServices, $d['service_id']);
        }
        foreach($ulTravel as $t){
            array_push($otherServices, $t['service_id']);
        }
        foreach($ulMap as $m){
            array_push($otherServices, $m['service_id']);
        }

        //remove duplicate services from array
        $otherServices = array_unique($otherServices);

        //merge both arrays into one array
        $allServices = array_merge($nwServices,$otherServices);

        $baseQuery = LeadRequest::with(['customer', 'category'])
            ->whereHas('customer', function ($query) {
                $query->where('form_status', 1);
            })
            ->where('customer_id', '<>', $user_id) //do not include self request leads

            //closure condition
            ->where('status','!=','hired') // do not include hired leads
            ->where('created_at', '>', Carbon::now()->subDays(14)->toDateString()); // do not include leads which are orlder than 14 days
        $leadSlotCount = CustomHelper::setting_value("lead_slot_count", 5);
        $slotFullLeads = DB::table('recommended_leads')
            ->select('lead_id')
            ->groupBy('lead_id')
            ->havingRaw('COUNT(*) >= ?', [$leadSlotCount])
            ->pluck('lead_id')
            ->toArray();

        $baseQuery = $baseQuery->whereNotIn('id', $slotFullLeads);

        if($requestPostcode === null){ //select default condition for location
            //include locations
            $baseQuery = $baseQuery->where(function ($query) use ($user_id, $ulDistance, $ulTravel, $ulMap, $nwServices) {
                //for distance type


                foreach ($ulDistance as $item) {
                    $radiusPostcode = CustomHelper::getPostcodesWithinRadius($item['postcode'], $item['miles']);


                    $query->orWhere(function ($q) use ($item, $radiusPostcode) {
                        $q->where('service_id', $item['service_id'])
                            ->whereIn('postcode', array_column($radiusPostcode, 'postcode'));
                    });
                }

                //include nation wide services
                if (!empty($nwServices)) {
                    $query->orWhereIn('service_id', $nwServices);
                }

            });
        }else{

            $baseQuery = $baseQuery->where(function ($query) use ($allServices, $requestPostcode, $requestMiles, $user_id) {
                //for distance type
                $radiusPostcode = CustomHelper::getPostcodesWithinRadius($requestPostcode, $requestMiles);
                foreach($allServices as $item){

                    $quesPref = $this->getUserPreferenceMap($user_id, $item);
                    print_r($quesPref);

                    $query->orWhere(function ($q) use ($item, $radiusPostcode, $user_id) {
                        $q->where('service_id', $item)
                            ->whereIn('postcode', array_column($radiusPostcode, 'postcode'));
                    });
                }
            });
        }


        // Exclude saved leads
        $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();

        // Exclude leads from recommended table starts as a bid has been placed
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
            ->pluck('lead_id')
            ->toArray();

        // Merge both exclusion arrays
        $excludedLeadIds = array_merge($savedLeadIds, $recommendedLeadIds);
        if (!empty($excludedLeadIds)) {
            $baseQuery = $baseQuery->whereNotIn('id', $excludedLeadIds);
        }


        //apply filters
        if(!empty($filters['searchName'])){
            $baseQuery = $baseQuery->where(function ($query) use ($filters) {
                $query->whereHas('customer', function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['searchName'] . '%');
                    // ->orWhere('city', 'like', '%' . $searchTerm . '%');
                })
                ->orWhereHas('category', function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['searchName'] . '%');
                })
                ->orWhere('city', 'like', '%' .  $filters['searchName'] . '%')
                ->orWhere('postcode', 'like', '%' .  $filters['searchName'] . '%')
                ->orWhere('phone', 'like', '%' .  $filters['searchName'] . '%');
            });
        }

        if(!empty($filters['spotlightFilter'])){
            $splghts = explode(',', $filters['spotlightFilter']);
            foreach($splghts as $sl){
                if(strtolower(trim($sl)) === 'all lead spotlights'){
                        $baseQuery = $baseQuery->where(function ($query){
                            $query->where('is_urgent', '=', '1')
                                ->where('is_updated', '=', '1')
                                ->where('has_additional_details', '=', '1');
                        });
                }else{
                    if(strtolower(trim($sl)) === 'urgent requests'){
                        $baseQuery = $baseQuery->where(function ($query){
                            $query->where('is_urgent', '=', '1');
                        });
                    }
                    if(strtolower(trim($sl)) === 'updated requests'){
                        $baseQuery = $baseQuery->where(function ($query){
                            $query->where('is_updated', '=', '1');
                        });
                    }
                    if(strtolower(trim($sl)) === 'has additional details'){
                        $baseQuery = $baseQuery->where(function ($query){
                            $query->where('has_additional_details', '=', '1');
                        });
                    }
                }

            }
        }

        if(!empty($filters['lead_time'])){
            if(strtolower(trim($filters['lead_time'])) === 'today'){
                $baseQuery = $baseQuery->where(function ($query){
                    $query->whereDate('created_at', Carbon::now()->toDateString());
                });
            }
            if(strtolower(trim($filters['lead_time'])) === 'yesterday'){
                $baseQuery = $baseQuery->where(function ($query){
                    $query->whereDate('created_at', Carbon::now()->subDay()->toDateString());
                });
            }
            if(strtolower(trim($filters['lead_time'])) === 'last 2-3 days'){
                $baseQuery = $baseQuery->where(function ($query){
                    $query->whereDate('created_at', '>' , Carbon::now()->subDay(3)->toDateString());
                });
            }
            if(strtolower(trim($filters['lead_time'])) === 'last 7 days'){
                $baseQuery = $baseQuery->where(function ($query){
                    $query->whereDate('created_at', '>', Carbon::now()->subDay(7)->toDateString());
                });
            }
            if(strtolower(trim($filters['lead_time'])) === 'last 14+ days'){
                $baseQuery = $baseQuery->where(function ($query){
                    $query->whereDate('created_at', '<' ,Carbon::now()->subDay()->toDateString());
                });
            }
        }
        if(!empty($filters['services'])){
            $sIds = explode(',', $filters['services']);
            $baseQuery = $baseQuery->where(function ($query) use ($sIds){
                $query->whereIn('service_id', $sIds);
            });
        }

        if(!empty($filters['creditFilter'])){
            $crFs = explode(',', str_replace('Credits','',$filters['creditFilter']));
            $creditRanges = [];
            foreach($crFs as $crf){
                $cc1 = explode('-',str_replace(' ','',$crf));
                $creditRanges[] = [ min($cc1),  max($cc1)];
            }
            $baseQuery = $baseQuery->where(function ($query) use ($creditRanges) {
                foreach ($creditRanges as $range) {
                    $query->orWhereRaw('CAST(credit_score AS UNSIGNED) BETWEEN ? AND ?', [$range[0], $range[1]]);
                }
            });
        }

        return $baseQuery;

    }

    public function leadsAccordingTOSellerPref($user_id, $leads){
        $pref = $this->getSellerPreferenceMap($user_id);
        $leads  = collect($leads);
        $groupedPrefs = collect($pref)->groupBy('service_id')->toArray();
        $filteredLeads = $this->filterSellerLeadsByGroupedPreferences($leads, $groupedPrefs);
        return $filteredLeads;
    }

    private function getSellerPreferenceMap($user_id){
        $rawAnswers = LeadPrefrence::with(['question'])
            ->where('user_id', $user_id)
            ->get();
        $prefs = [];
        foreach ($rawAnswers as $ra) {
            $temp['service_id'] = $ra->service_id;
            $temp['question'] = $ra->question->questions;
            $temp['answers'] = array_map('trim', explode(',', $ra->answers));
            $prefs[] = $temp;
        }
        return $prefs;

    }

    private function filterSellerLeadsByGroupedPreferences(\Illuminate\Support\Collection $leads, array $groupedPrefs)
    {
        return $leads->filter(function ($lead) use ($groupedPrefs) {
            $serviceId = $lead->service_id;

            if (!isset($groupedPrefs[$serviceId])) {
                // logger("No preferences for service_id: $serviceId");
                return false;
            }

            $prefs = $groupedPrefs[$serviceId];
            $leadQuestions = json_decode($lead->arrayed_questions, true);

            if (!is_array($leadQuestions)) {
                // logger("Invalid questions JSON for lead ID: {$lead->id}");
                return false;
            }

            $leadMap = [];
            foreach ($leadQuestions as $q) {
                $normalized = $this->normalizeQuestion($q['ques']);
                $leadMap[$normalized] = $q['ans'];
            }

            foreach ($prefs as $pref) {
                $question = $this->normalizeQuestion($pref['question']);
                $expectedAnswers = $pref['answers'];

                if (!isset($leadMap[$question])) {
                    // logger("Lead ID {$lead->id} missing question: $question");
                    return false;
                }

                $leadAnswers = $leadMap[$question];

                $intersect = array_intersect($expectedAnswers, $leadAnswers);

                if (empty($intersect) && !in_array('Something else (please describe)', $expectedAnswers)) {
                    // logger("Lead ID {$lead->id} failed on question: $question");
                    // logger("Lead answers: " . json_encode($leadAnswers));
                    // logger("Expected answers: " . json_encode($expectedAnswers));
                    return false;
                }
            }

            // logger("Matched Lead ID: {$lead->id}, Service ID: $serviceId");
            return true;
        });
    }

    private function normalizeQuestion(string $question): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9 ]/', '', $question)));
    }
}
