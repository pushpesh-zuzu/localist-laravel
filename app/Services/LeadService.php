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
    Auth,
    Hash,
    DB,
    Mail,
    Validator,
    Http
};
use App\Models\Postcode;
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


    public function getSellerSavedLeadsBaseQuery($user_id, $requestPostcode = null, $requestMiles = null, $filters = [])
    {
        $userServices = UserService::where('user_id', $user_id)->select('service_id')->get();
        //get all types of locations
        $ulNationWide = UserServiceLocation::where('user_id', $user_id)->where('nation_wide', '1')->get();
        $ulDistance = UserServiceLocation::where('user_id', $user_id)->where('type', 'Distance')->get()->toArray();
        $ulTravel = UserServiceLocation::where('user_id', $user_id)->where('type', 'Travel Time')->get()->toArray();
        $ulMap = UserServiceLocation::where('user_id', $user_id)->where('type', 'Draw on Map')->get()->toArray();

        //get Nation Wide services
        $nwServices = [];
        foreach ($ulNationWide as $ul) {
            array_push($nwServices, $ul->service_id);
        }

        //remove duplicate services from array
        $nwServices = array_unique($nwServices);

        //remove location if it is nation wide
        $ulDistance = array_filter($ulDistance, function ($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });
        $ulTravel = array_filter($ulTravel, function ($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });
        $ulMap = array_filter($ulMap, function ($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });


        //add other services
        $otherServices = [];
        foreach ($ulDistance as $d) {
            array_push($otherServices, $d['service_id']);
        }
        foreach ($ulTravel as $t) {
            array_push($otherServices, $t['service_id']);
        }
        foreach ($ulMap as $m) {
            array_push($otherServices, $m['service_id']);
        }

        //remove duplicate services from array
        $otherServices = array_unique($otherServices);

        //merge both arrays into one array
        $allServices = array_merge($nwServices, $otherServices);

        // Take only saved leads
        $savedLeadIds = SaveForLater::where('seller_id', $user_id)->pluck('lead_id')->toArray();
        if (empty($savedLeadIds)) {
            return LeadRequest::whereRaw('0 = 1'); // empty builder
        }

        $baseQuery = LeadRequest::with(['customer', 'category'])
            ->whereHas('customer', function ($query) {
                $query->where('form_status', 1);
            })
            ->whereIn('id', $savedLeadIds) //only saved leads
            ->where('customer_id', '<>', $user_id) //do not include self request leads

            //closure condition
            ->where('status', '!=', 'hired') // do not include hired leads
            ->where('created_at', '>', Carbon::now()->subDays(14)->toDateString()); // do not include leads which are orlder than 14 days
        $leadSlotCount = CustomHelper::setting_value("lead_slot_count", 5);
        $slotFullLeads = DB::table('recommended_leads')
            ->select('lead_id')
            ->groupBy('lead_id')
            ->havingRaw('COUNT(*) >= ?', [$leadSlotCount])
            ->pluck('lead_id')
            ->toArray();


        $baseQuery = $baseQuery->whereNotIn('id', $slotFullLeads); //do not include leads which 5 slot full

        if ($requestPostcode === null) { //select default condition for location
            //include locations
            $baseQuery = $baseQuery->where(function ($query) use ($user_id, $ulDistance, $ulTravel, $ulMap, $nwServices) {
                //for distance type


                foreach ($ulDistance as $item) {
                    // $radiusPostcode = CustomHelper::getPostcodesWithinRadius($item['postcode'], $item['miles']);
                    $radiusPostcodeQuery = CustomHelper::getPostcodesWithinRadiusQuery($item['postcode'], $item['miles']);

                    $query->orWhere(function ($q) use ($item, $radiusPostcodeQuery) {
                        $q->where('service_id', $item['service_id']);
                        if ($radiusPostcodeQuery) {
                            $q->whereIn('postcode', $radiusPostcodeQuery);
                        }
                    });
                }

                //include nation wide services
                if (!empty($nwServices)) {
                    $query->orWhereIn('service_id', $nwServices);
                }
            });
        } else {

            $baseQuery = $baseQuery->where(function ($query) use ($allServices, $requestPostcode, $requestMiles, $user_id) {
                //for distance type
                // $radiusPostcode = CustomHelper::getPostcodesWithinRadius($requestPostcode, $requestMiles);
                $radiusPostcodeQuery = CustomHelper::getPostcodesWithinRadiusQuery($requestPostcode, $requestMiles);
                foreach ($allServices as $item) {

                    $quesPref = $this->getSellerPreferenceMap($user_id, $item);

                    $query->orWhere(function ($q) use ($item, $radiusPostcodeQuery, $user_id) {
                        $q->where('service_id', $item);
                        if ($radiusPostcodeQuery) {
                            $q->whereIn('postcode', $radiusPostcodeQuery);
                        }
                    });
                }
            });
        }




        // Exclude leads from recommended table starts as a bid has been placed
        $recommendedLeadIds = RecommendedLead::where('seller_id', $user_id)
            ->pluck('lead_id')
            ->toArray();


        // Merge both exclusion arrays
        // $excludedLeadIds = array_merge($savedLeadIds, $recommendedLeadIds);
        $excludedLeadIds = $recommendedLeadIds;
        if (!empty($excludedLeadIds)) {
            $baseQuery = $baseQuery->whereNotIn('id', $excludedLeadIds);
        }



        //apply filters
        if (!empty($filters['searchName'])) {
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

        if (!empty($filters['spotlightFilter'])) {
            $splghts = explode(',', $filters['spotlightFilter']);
            $baseQuery = $baseQuery->where(function ($query) use ($splghts) {
                foreach ($splghts as $sl) {
                    $sl = strtolower(trim($sl));
                    if ($sl === 'urgent requests') {
                        $query->orWhere('is_urgent', '=', '1');
                    } elseif ($sl === 'updated requests') {
                        $query->orWhere('is_updated', '=', '1');
                    } elseif ($sl === 'has additional details') {
                        $query->orWhere('has_additional_details', '=', '1');
                    }
                }
            });
        }

        if (!empty($filters['lead_time'])) {
            if (strtolower(trim($filters['lead_time'])) === 'today') {
                $baseQuery = $baseQuery->where(function ($query) {
                    $query->whereDate('created_at', Carbon::now()->toDateString());
                });
            }
            if (strtolower(trim($filters['lead_time'])) === 'yesterday') {
                $baseQuery = $baseQuery->where(function ($query) {
                    $query->whereDate('created_at', Carbon::now()->subDay()->toDateString());
                });
            }
            if (strtolower(trim($filters['lead_time'])) === 'last 2-3 days') {
                $baseQuery = $baseQuery->where(function ($query) {
                    $query->whereDate('created_at', '>', Carbon::now()->subDay(3)->toDateString());
                });
            }
            if (strtolower(trim($filters['lead_time'])) === 'last 7 days') {
                $baseQuery = $baseQuery->where(function ($query) {
                    $query->whereDate('created_at', '>', Carbon::now()->subDay(7)->toDateString());
                });
            }
            if (strtolower(trim($filters['lead_time'])) === 'last 14+ days') {
                $baseQuery = $baseQuery->where(function ($query) {
                    $query->whereDate('created_at', '<', Carbon::now()->subDay(14)->toDateString());
                });
            }
        }
        if (!empty($filters['services'])) {
            $sIds = explode(',', $filters['services']);
            $baseQuery = $baseQuery->where(function ($query) use ($sIds) {
                $query->whereIn('service_id', $sIds);
            });
        }

        if (!empty($filters['creditFilter'])) {
            $crFs = explode(',', str_replace('Credits', '', $filters['creditFilter']));
            $creditRanges = [];
            foreach ($crFs as $crf) {
                $cc1 = explode('-', str_replace(' ', '', $crf));
                $creditRanges[] = [min($cc1),  max($cc1)];
            }

            $baseQuery = $baseQuery->where(function ($query) use ($creditRanges) {
                foreach ($creditRanges as $range) {
                    $query->orWhereRaw('CAST(credit_score AS UNSIGNED) BETWEEN ? AND ?', [$range[0], $range[1]]);
                }
            });
        }



        return $baseQuery;
    }



    public function getSellerLeadsBaseQuery($user_id, $requestPostcode = null, $requestMiles = null, $filters = [], $autobid = null)
    {
        $userServices = UserService::where('user_id', $user_id)->select('service_id')->get();
        //get all types of locations
        $ulNationWide = UserServiceLocation::where('user_id', $user_id)->where('nation_wide', '1')->get();
        $ulDistance = UserServiceLocation::where('user_id', $user_id)->where('type', 'Distance')->get()->toArray();
        $ulTravel = UserServiceLocation::where('user_id', $user_id)->where('type', 'Travel Time')->get()->toArray();
        $ulMap = UserServiceLocation::where('user_id', $user_id)->where('type', 'Draw on Map')->get()->toArray();

        //get Nation Wide services
        $nwServices = [];
        foreach ($ulNationWide as $ul) {
            array_push($nwServices, $ul->service_id);
        }

        //remove duplicate services from array
        $nwServices = array_unique($nwServices);

        //remove location if it is nation wide
        $ulDistance = array_filter($ulDistance, function ($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });
        $ulTravel = array_filter($ulTravel, function ($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });
        $ulMap = array_filter($ulMap, function ($item) use ($nwServices) {
            return !in_array($item['service_id'], $nwServices);
        });


        //add other services
        $otherServices = [];
        foreach ($ulDistance as $d) {
            array_push($otherServices, $d['service_id']);
        }
        foreach ($ulTravel as $t) {
            array_push($otherServices, $t['service_id']);
        }
        foreach ($ulMap as $m) {
            array_push($otherServices, $m['service_id']);
        }

        //remove duplicate services from array
        $otherServices = array_unique($otherServices);

        //merge both arrays into one array
        $allServices = array_merge($nwServices, $otherServices);

        $baseQuery = LeadRequest::with(['customer', 'category'])
            ->whereHas('customer', function ($query) {
                $query->where('form_status', 1);
            })
            ->where('customer_id', '<>', $user_id) //do not include self request leads

            //closure condition
            ->where('status', '!=', 'hired') // do not include hired leads
            ->where('created_at', '>', Carbon::now()->subDays(14)->toDateString()); // do not include leads which are orlder than 14 days
        $leadSlotCount = CustomHelper::setting_value("lead_slot_count", 5);
        $slotFullLeads = DB::table('recommended_leads')
            ->select('lead_id')
            ->groupBy('lead_id')
            ->havingRaw('COUNT(*) >= ?', [$leadSlotCount])
            ->pluck('lead_id')
            ->toArray();


        $baseQuery = $baseQuery->whereNotIn('id', $slotFullLeads); //do not include leads which 5 slot full

        if ($requestPostcode === null) { //select default condition for location
            //include locations
            $baseQuery = $baseQuery->where(function ($query) use ($user_id, $ulDistance, $ulTravel, $ulMap, $nwServices) {
                //for distance type


                foreach ($ulDistance as $item) {
                    // $radiusPostcode = CustomHelper::getPostcodesWithinRadius($item['postcode'], $item['miles']);

                    // check if request postcode exists in postcode table, if not then get coordinates and save
                    if (!empty($item['postcode'])) {
                        $dbPostcode = Postcode::where('postcode', $item['postcode'])->first();
                        if (empty($dbPostcode)) {
                            $tempCord = CustomHelper::getCoordinates($item['postcode']);
                            if (!empty($tempCord)) {
                                $cordArr = json_decode($tempCord, true);
                                if (!empty($cordArr['lat']) && !empty($cordArr['lng'])) {
                                    Postcode::create([
                                        'postcode' => $item['postcode'],
                                        'latitude' => $cordArr['lat'],
                                        'longitude' => $cordArr['lng'],
                                    ]);
                                }
                            }
                        }
                    }


                    $radiusPostcodeQuery = CustomHelper::getPostcodesWithinRadiusQuery($item['postcode'], $item['miles']);

                    $query->orWhere(function ($q) use ($item, $radiusPostcodeQuery) {
                        $q->where('service_id', $item['service_id']);
                        if ($radiusPostcodeQuery) {
                            $q->whereIn('postcode', $radiusPostcodeQuery);
                        }
                    });
                }

                //include nation wide services
                if (!empty($nwServices)) {
                    $query->orWhereIn('service_id', $nwServices);
                }
            });
        } else {

            $baseQuery = $baseQuery->where(function ($query) use ($allServices, $requestPostcode, $requestMiles, $user_id) {
                //for distance type
                // $radiusPostcode = CustomHelper::getPostcodesWithinRadius($requestPostcode, $requestMiles);


                // check if request postcode exists in postcode table, if not then get coordinates and save
                if (!empty($requestPostcode)) {
                    $dbPostcode = Postcode::where('postcode', $requestPostcode)->first();
                    if (empty($dbPostcode)) {
                        $tempCord = CustomHelper::getCoordinates($requestPostcode);
                        if (!empty($tempCord)) {
                            $cordArr = json_decode($tempCord, true);
                            if (!empty($cordArr['lat']) && !empty($cordArr['lng'])) {
                                Postcode::create([
                                    'postcode' => $requestPostcode,
                                    'latitude' => $cordArr['lat'],
                                    'longitude' => $cordArr['lng'],
                                ]);
                            }
                        }
                    }
                }
                $radiusPostcodeQuery = CustomHelper::getPostcodesWithinRadiusQuery($requestPostcode, $requestMiles);
                foreach ($allServices as $item) {

                    $quesPref = $this->getSellerPreferenceMap($user_id, $item);

                    $query->orWhere(function ($q) use ($item, $radiusPostcodeQuery, $user_id) {
                        $q->where('service_id', $item);
                        if ($radiusPostcodeQuery) {
                            $q->whereIn('postcode', $radiusPostcodeQuery);
                        }
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
            if (empty($autobid)) {
                $baseQuery = $baseQuery->whereNotIn('id', $excludedLeadIds);
            }
        }



        //apply filters
        if (!empty($filters['searchName'])) {
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

        if (!empty($filters['spotlightFilter'])) {
            $splghts = explode(',', $filters['spotlightFilter']);
            $baseQuery = $baseQuery->where(function ($query) use ($splghts) {
                foreach ($splghts as $sl) {
                    $sl = strtolower(trim($sl));
                    if ($sl === 'urgent requests') {
                        $query->orWhere('is_urgent', '=', '1');
                    } elseif ($sl === 'updated requests') {
                        $query->orWhere('is_updated', '=', '1');
                    } elseif ($sl === 'has additional details') {
                        $query->orWhere('has_additional_details', '=', '1');
                    }
                }
            });
        }

        if (!empty($filters['lead_time'])) {
            if (strtolower(trim($filters['lead_time'])) === 'today') {
                $baseQuery = $baseQuery->where(function ($query) {
                    $query->whereDate('created_at', Carbon::now()->toDateString());
                });
            }
            if (strtolower(trim($filters['lead_time'])) === 'yesterday') {
                $baseQuery = $baseQuery->where(function ($query) {
                    $query->whereDate('created_at', Carbon::now()->subDay()->toDateString());
                });
            }
            if (strtolower(trim($filters['lead_time'])) === 'last 2-3 days') {
                $baseQuery = $baseQuery->where(function ($query) {
                    $query->whereDate('created_at', '>', Carbon::now()->subDay(3)->toDateString());
                });
            }
            if (strtolower(trim($filters['lead_time'])) === 'last 7 days') {
                $baseQuery = $baseQuery->where(function ($query) {
                    $query->whereDate('created_at', '>', Carbon::now()->subDay(7)->toDateString());
                });
            }
            if (strtolower(trim($filters['lead_time'])) === 'last 14+ days') {
                $baseQuery = $baseQuery->where(function ($query) {
                    $query->whereDate('created_at', '<', Carbon::now()->subDay(14)->toDateString());
                });
            }
        }
        if (!empty($filters['services'])) {
            $sIds = explode(',', $filters['services']);
            $baseQuery = $baseQuery->where(function ($query) use ($sIds) {
                $query->whereIn('service_id', $sIds);
            });
        }

        if (!empty($filters['creditFilter'])) {
            $crFs = explode(',', str_replace('Credits', '', $filters['creditFilter']));
            $creditRanges = [];
            foreach ($crFs as $crf) {
                $cc1 = explode('-', str_replace(' ', '', $crf));
                $creditRanges[] = [min($cc1),  max($cc1)];
            }

            $baseQuery = $baseQuery->where(function ($query) use ($creditRanges) {
                foreach ($creditRanges as $range) {
                    $query->orWhereRaw('CAST(credit_score AS UNSIGNED) BETWEEN ? AND ?', [$range[0], $range[1]]);
                }
            });
        }



        return $baseQuery;
    }

    public function leadsAccordingTOSellerPref($user_id, $leads)
    {
        $pref = $this->getSellerPreferenceMap($user_id);
        $leads  = collect($leads);
        $groupedPrefs = collect($pref)->groupBy('service_id')->toArray();
        $filteredLeads = $this->filterSellerLeadsByGroupedPreferences($leads, $groupedPrefs);
        return $filteredLeads;
    }

    public function getSellerPreferenceMap($user_id)
    {
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
                    // return false; if strict match (all questions) is required
                    continue; // if want to include leads which has missing leads
                }

                $leadAnswers = $leadMap[$question];

                $intersect = array_intersect($expectedAnswers, $leadAnswers);

                if (empty($intersect) && !in_array(strtolower('Something else (please describe)'), array_map('strtolower', $expectedAnswers))) {
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


    ####################################################################################################################
    // CUSTOMER PANEL
    ####################################################################################################################

    public function getAllSellers($lead, $filters = [], $sendFlag = null)
    {
        $recommendedCount = CustomHelper::setting_value("recommended_list_count", 5);
        $serviceId        = $lead->service_id;
        $leadCreditScore  = (int) $lead->credit_score;
        $refPostcode      = $lead->postcode;
        $customerId       = $lead->customer_id;
        $question         = $lead->arrayed_questions;
        $serviceName      = Category::find($serviceId)->name ?? '';

        if (!is_array(json_decode($question, true))) {
            return $this->sendError('Invalid or missing lead questions', 404);
        }

        // Step 1: Get reference lat/lng in one go
        $ref = DB::table('postcodes')
            ->select('latitude', 'longitude')
            ->where('postcode', $refPostcode)
            ->first();

        if (!$ref) {
            $reqPostcode = $refPostcode;
            if (!empty($reqPostcode)) {
                $dbPostcode = Postcode::where('postcode', $reqPostcode)->first();
                if (empty($dbPostcode)) {
                    $tempCord = CustomHelper::getCoordinates($reqPostcode);
                    if (!empty($tempCord)) {
                        $cordArr = json_decode($tempCord, true);
                        if (!empty($cordArr['lat']) && !empty($cordArr['lng'])) {
                            Postcode::insertGetId([
                                'postcode' => $reqPostcode,
                                'latitude' => $cordArr['lat'],
                                'longitude' => $cordArr['lng'],
                            ]);
                        }
                    }
                }
            }

            $ref = DB::table('postcodes')
                ->select('latitude', 'longitude')
                ->where('postcode', $refPostcode)
                ->first();
        }

        $refLat = $ref->latitude;
        $refLng = $ref->longitude;

        // Already contacted sellers
        $repliesUsers = RecommendedLead::where('lead_id', $lead->id)
            ->where('service_id', $serviceId)
            ->pluck('seller_id')
            ->toArray();

        // Step 2: Query sellers (push as much filtering into SQL as possible)
        $rows = DB::table('user_service_locations as usl')
            ->join('users', function ($join) use ($repliesUsers, $customerId) {
                $join->on('users.id', '=', 'usl.user_id')
                    ->where('users.form_status', 1)
                    ->whereNotIn('users.id', $repliesUsers)
                    ->where('users.id', '<>', $customerId);
            })
            ->join('user_details', 'user_details.user_id', '=', 'users.id')
            ->join('postcodes as p', 'p.postcode', '=', 'usl.postcode')
            ->leftJoin('user_response_times as urt', 'urt.seller_id', '=', 'usl.user_id')
            ->where('usl.service_id', $serviceId)
            ->when($sendFlag != 2, function ($query) use ($leadCreditScore) {
                $query->where('users.total_credit', '>=', $leadCreditScore);
            })
            ->when(!empty($filters['rating']), function ($query) use ($filters) {
                if ($filters['rating'] === 'no_rating') {
                    $query->where('users.avg_rating', 0);
                } elseif ($filters['rating'] === 5) {
                    $query->where('users.avg_rating', '=', 5);
                } else {
                    $query->where('users.avg_rating', '>=', $filters['rating']);
                }
            })
            ->when(!empty($filters['response_time']), function ($query) use ($filters) {
                $timeThresholds = [
                    'Responds within 10 mins' => 10,
                    'Responds within 1 hour'  => 60,
                    'Responds within 6 hours' => 360,
                    'Responds within 24 hours' => 1440,
                ];
                $maxAllowed = $timeThresholds[$filters['response_time']] ?? null;
                if ($maxAllowed) {
                    $query->whereNotNull('urt.average')
                        ->where('urt.average', '<=', $maxAllowed);
                }
            })
            // Calculate distance in SQL (Haversine)
            ->select(
                'users.id',
                'users.name',
                'users.profile_image',
                'users.total_credit',
                'users.avg_rating',
                'users.about_company',
                'users.form_status',
                'users.business_profile_name',
                'users.company_logo',
                'user_details.is_autobid',
                'user_details.autobid_pause',
                'usl.user_id',
                'usl.service_id',
                'usl.miles',
                'usl.nation_wide',
                'usl.postcode',
                DB::raw('COALESCE(urt.average, 15) as response_time'),
                'p.latitude as lat',
                'p.longitude as lng',
                DB::raw("TRUNCATE(3958.8 * acos(
                    cos(radians($refLat)) * cos(radians(p.latitude)) * cos(radians(p.longitude) - radians($refLng))
                    + sin(radians($refLat)) * sin(radians(p.latitude))
                ), 3) as distance"),
                'users.created_at as user_created_time'
            )
            ->get();

        // Step 3: Group sellers by user_id + postcode
        $grouped = $rows->groupBy(fn($row) => $row->user_id . '_' . $row->postcode)
            ->map(function ($items) use ($serviceName, $leadCreditScore) {
                $r = $items->firstWhere('nation_wide', 1)
                    ?: $items->sortByDesc('miles')->first();

                $r->credit_score   = $leadCreditScore;
                $r->service_name   = $serviceName;
                $r->quicktorespond = ($r->response_time > 0 && $r->response_time <= 720) ? 1 : 0;

                return $r;
            });

        // Step 4: Apply final distance filtering in PHP
        $filteredUsers = $grouped->filter(function ($row) use ($refPostcode) {
            return $row->nation_wide == 1
                || $row->postcode == $refPostcode
                || $row->miles >= $row->distance;
        });
        // Step 5: Apply distance sorting logic
        $distanceOrder = strtolower($filters['distance_order'] ?? '');
        $final = $this->usersAccordingToPrefs($question, $filteredUsers, $serviceId)
            ->when($distanceOrder === '' || $distanceOrder === 'nearest to farthest', fn($c) => $c->sortBy('distance'))
            ->when($distanceOrder === 'farthest to nearest', fn($c) => $c->sortByDesc('distance'));

        // Step 6: Ensure unique sellers (keep nearest one per seller id)
        $seen = [];
        $finalUniqueSellers = $final->filter(function ($seller) use (&$seen) {
            if (isset($seen[$seller->id])) {
                if ($seller->distance < $seen[$seller->id]->distance) {
                    $seen[$seller->id] = $seller;
                }
            } else {
                $seen[$seller->id] = $seller;
            }
            return false;
        })->pipe(function () use (&$seen) {
            return collect(array_values($seen));
        });

        return [
            'empty'   => $finalUniqueSellers->isEmpty(),
            'response' => [
                'service_name'  => $serviceName,
                'sellersCount'  => $finalUniqueSellers->count(),
                'sellers'       => $finalUniqueSellers,
                'displayCount'  => $recommendedCount,
                'baseurl'       => url('/') . Storage::url('app/public/images/users'),
                'w80'           => (int) ($recommendedCount * 0.8),
            ]
        ];
    }



    public function getAllSellersNationList($lead, $filters = [], $sendFlag = null)
    {
        $recommendedCount = CustomHelper::setting_value("recommended_list_count", 5);
        $serviceId        = $lead->service_id;
        $leadCreditScore  = (int) $lead->credit_score;
        $refPostcode      = $lead->postcode;
        $customerId       = $lead->customer_id;
        $question         = $lead->arrayed_questions;
        $serviceName      = Category::find($serviceId)->name ?? '';

        if (!is_array(json_decode($question, true))) {
            return $this->sendError('Invalid or missing lead questions', 404);
        }

        // Step 1: Already contacted sellers
        $repliesUsers = RecommendedLead::where('lead_id', $lead->id)
            ->where('service_id', $serviceId)
            ->pluck('seller_id')
            ->toArray();

        // Step 2: Query sellers (nationwide or service-specific)
        $rows = DB::table('user_service_locations as usl')
            ->join('users', function ($join) use ($repliesUsers, $customerId) {
                $join->on('users.id', '=', 'usl.user_id')
                    ->where('users.form_status', 1)
                    ->whereNotIn('users.id', $repliesUsers)
                    ->where('users.id', '<>', $customerId);
            })
            ->join('user_details', 'user_details.user_id', '=', 'users.id')
            ->leftJoin('user_response_times as urt', 'urt.seller_id', '=', 'usl.user_id')
            ->where('usl.service_id', $serviceId)
            ->when($sendFlag == 1, function ($query) {
                $query->where('usl.nation_wide', 1);
            })
            ->select(
                'users.id',
                'users.name',
                'users.profile_image',
                'users.total_credit',
                'users.avg_rating',
                'users.about_company',
                'users.form_status',
                'users.business_profile_name',
                'users.company_logo',
                'user_details.is_autobid',
                'user_details.autobid_pause',
                'usl.user_id',
                'usl.service_id',
                'usl.miles',
                'usl.nation_wide',
                'usl.postcode',
                DB::raw('COALESCE(urt.average, 15) as response_time'),
                'users.created_at as user_created_time'
            )
            ->get();

        // Step 3: Group sellers by user_id + postcode
        $grouped = $rows->groupBy(fn($row) => $row->user_id . '_' . $row->postcode)
            ->map(function ($items) use ($serviceName, $leadCreditScore) {
                $r = $items->firstWhere('nation_wide', 1)
                    ?: $items->sortByDesc('miles')->first();

                $r->credit_score   = $leadCreditScore;
                $r->service_name   = $serviceName;
                $r->quicktorespond = ($r->response_time > 0 && $r->response_time <= 720) ? 1 : 0;

                return $r;
            });

        // Step 4: Apply preference logic
        $final = $this->usersAccordingToPrefs($question, $grouped, $serviceId);

        // Step 5: Ensure unique sellers (keep nearest one per seller id)
        $seen = [];
        $finalUniqueSellers = $final->filter(function ($seller) use (&$seen) {
            if (isset($seen[$seller->id])) {
                if ($seller->distance < $seen[$seller->id]->distance) {
                    $seen[$seller->id] = $seller;
                }
            } else {
                $seen[$seller->id] = $seller;
            }
            return false;
        })->pipe(function () use (&$seen) {
            return collect(array_values($seen));
        });

        return [
            'empty'   => $finalUniqueSellers->isEmpty(),
            'response' => [
                'service_name'  => $serviceName,
                'sellersCount'  => $finalUniqueSellers->count(),
                'sellers'       => $finalUniqueSellers,
                'displayCount'  => $recommendedCount,
                'baseurl'       => url('/') . Storage::url('app/public/images/users'),
                'w80'           => (int) ($recommendedCount * 0.8),
            ]
        ];
    }


    private function usersAccordingToPrefs($arrayed_questions, $filteredUsers, $serviceId)
    {
        $arrayedQuestions = json_decode($arrayed_questions, true);

        $userIds = $filteredUsers->pluck('user_id')->all();

        // Load preferences of filtered users
        $rawAnswers = LeadPrefrence::with(['question'])
            ->whereIn('user_id', $userIds)
            ->where('service_id', $serviceId)
            ->get();
        $prefs = [];
        foreach ($rawAnswers as $ra) {
            $temp['user_id'] = $ra->user_id;
            $temp['service_id'] = $ra->service_id;
            $temp['question'] = $ra->question->questions;
            $temp['answers'] = array_map('trim', explode(',', $ra->answers));
            $prefs[] = $temp;
        }

        // Group by user_id
        $groupedPrefs = collect($prefs)->groupBy('user_id')->toArray();

        // Run match logic
        $matchedUserIds = $this->filterMatchingUsers($arrayedQuestions, $groupedPrefs);

        // Final filtered result
        $final = $filteredUsers->filter(function ($row) use ($matchedUserIds) {
            return in_array($row->user_id, $matchedUserIds);
        });

        return $final;
    }

    private function filterMatchingUsers(array $arrayedQuestions, array $groupedPrefs): array
    {
        $matchingUserIds = [];

        foreach ($groupedPrefs as $userId => $prefs) {
            $prefMap = [];

            foreach ($prefs as $pref) {
                $question = is_object($pref) ? $pref->question : ($pref['question'] ?? null);
                $answers = is_object($pref) ? $pref->answers : ($pref['answers'] ?? []);

                if (is_string($question)) {
                    $normalizedQ = $this->normalizeQuestion($question);
                    $prefMap[$normalizedQ] = array_map(function ($a) {
                        return strtolower(trim($a));
                    }, $answers);
                }
            }

            $matchedAll = true;

            foreach ($arrayedQuestions as $q) {
                $question = $this->normalizeQuestion($q['ques']);
                $leadAnswers = array_map('strtolower', array_map('trim', $q['ans']));
                $userAnswers = $prefMap[$question] ?? [];

                // Log for debugging
                // logger("User ID: $userId | Question: {$q['ques']} => $question");
                // logger("Lead Answers: ", $leadAnswers);
                // logger("User Prefs: ", $userAnswers);

                // Case 4: match if user pref contains "other"
                if (in_array(strtolower('Something else (please describe)'), array_map('strtolower', $userAnswers))) {
                    continue;
                }

                // Case 3: exclude if no overlap
                if (empty(array_intersect($leadAnswers, $userAnswers))) {
                    // logger("❌ Mismatch on: {$q['ques']}");
                    // logger("Lead Answers: ", $leadAnswers);
                    // logger("User Prefs: ", $userAnswers);
                    $matchedAll = false;
                    break;
                }
            }

            if ($matchedAll) {
                // logger("✅ Matched user: $userId");
                $matchingUserIds[] = $userId;
            }
        }

        return $matchingUserIds;
    }
}
