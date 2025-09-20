<?php

namespace App\Http\Controllers\Api\Cron;

use App\Helpers\Zoho\ZohoEmails;
use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\LeadRequest;
use App\Models\PlanHistory;
use App\Models\RecommendedLead;
use App\Models\User;
use App\Models\UserServiceLocation;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helpers\CustomHelper;
use App\Models\AbandonedUser;
use Illuminate\Support\Facades\Log;

class CronController extends Controller
{
    public function onHourlyBasis(Request $request, LeadService $leadService)
    {
        set_time_limit(0);

        $this->UnsoldLeadsStep1($request, $leadService);
        $this->unSoldLeadsStep2($request, $leadService);
        $this->unSoldLeadsStep3($request, $leadService);

        $this->leadPurchaseStatusUpdate48hrs($request);
        $this->leadPurchaseStatusUpdate96hrs($request);

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function onEveningBasis(){
        $sendGroupedLeadEmail = $this->sendGroupedLeadEmail();

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'new_lead_after_evening' => $sendGroupedLeadEmail,
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);

    }

    public function onDayBasis()
    {
        $newLeadAfter7days = $this->sendLeadsAfter7Days();

        $newLeadAfter5days = $this->checkCreditAfter5Days();
        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'new_lead_after_7_days' => $newLeadAfter7days,
                'new_lead_after_5_days' => $newLeadAfter5days
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
    public function onTwoDayBasis()
    {

        $sendAutoBidOff = $this->sendEncouragementEmail();

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'autobidoff' => $sendAutoBidOff
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function sendEncouragementEmail()
    {

        $sentCount = 0;
        $users = User::whereNotNull('zoho_record_id')
            ->whereHas('details', function ($q) {
                $q->where('is_autobid', 0);
            })
            ->get();

        foreach ($users as $user) {

            $userId = $user->id;
            $alreadySent = EmailLog::where('user_id', $userId)
                ->whereDate('created_at', Carbon::today())
                ->where('setting_name', 'Send Autobid Encouragement Email')
                ->exists();


            if (!$alreadySent) {
                ZohoEmails::sendEncouragementEmail($userId);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "$sentCount encouragement email(s) sent.",
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    public function sendLeadsAfter7Days()
    {

        print_r("sendLeadsAfter7Days called\n");


        $totalUnsentLeadEmails = 0;

        $now = Carbon::now();

        if ($now->minute === 0) {
            $to = $now->copy()->subHours(168);
        } else {
            $to = $now->copy()->subMinutes($now->minute)->subHours(168);
        }
        $from = $to->copy()->subMinutes(59);

        print_r("From: " . $from . " To: " . $to . "\n");

        $sellerLeadSummary = [];

        User::whereNotNull('zoho_record_id')
            ->where('form_status', 1)
            ->whereIn('user_type', [1, 3])
            ->select('users.id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use (&$sellerLeadSummary, $from, $to) {

                foreach ($sellersChunk as $seller) {
                    //var_dump($seller->id);
                    // print_r("Processing seller ID: " .$seller->id."\n");
                    $serviceLocations = UserServiceLocation::where('user_id', $seller->id)->get();


                    $groupedLeadStats = [];
                    $nationwideLeadIds = [];
                    foreach ($serviceLocations as $location) {

                        $leadQuery = LeadRequest::with('category')
                            ->where('service_id', $location->service_id)
                            ->where('status', '!=', 'hired')
                            ->orderBy('created_at', 'desc');



                        if ($location->nation_wide != 1) {

                            $groupKey = $location->postcode;

                            $leadQuery->whereNotIn('id', $nationwideLeadIds);
                            $leadQuery->where('postcode', $location->postcode);
                        } else {
                            $groupKey = 'nationwide';
                        }

                        $leads = $leadQuery->get();

                        // print_r("Leads Query: ".json_encode($leadQuery->toRawSql())."\n");
                        // print_r("Leads: ".json_encode($leads->toArray())."\n");

                        //print_r("Leads: ".json_encode($leads->toArray())."\n");

                        foreach ($leads as $lead) {

                            $latestRecommended = RecommendedLead::where('seller_id', $seller->id)
                                ->latest('created_at')   // order by created_at DESC
                                ->first();

                            $alreadyRecommended = true;

                            if ($latestRecommended && $latestRecommended->created_at->between($from, $to)) {
                                $alreadyRecommended = false;
                            }
                            if (!$latestRecommended) {
                                $checkUsers = User::where('id', $seller->id)
                                    ->whereBetween('created_at', [$from, $to])   // order by created_at DESC
                                    ->first();

                                if ($checkUsers) {
                                    $alreadyRecommended = false;
                                }
                            }

                            if ($alreadyRecommended) {
                                continue;
                            }




                            $serviceId = $location->service_id;
                            $categoryName = $lead->category?->name ?? 'N/A';
                            $groupedLeadStats[$serviceId][$groupKey]['category_name'] = $categoryName;
                            $groupedLeadStats[$serviceId][$groupKey]['lead_ids'][] = $lead->id;
                            $groupedLeadStats[$serviceId][$groupKey]['count'] =
                                ($groupedLeadStats[$serviceId][$groupKey]['count'] ?? 0) + 1;

                            $groupedLeadStats[$serviceId][$groupKey]['credit_sum'] =
                                ($groupedLeadStats[$serviceId][$groupKey]['credit_sum'] ?? 0) + $lead->credit_score;

                            if ($location->nation_wide == 1) {
                                $nationwideLeadIds[] = $lead->id;
                            }
                        }
                    }

                    $sellerLeadSummary[$seller->id] = $groupedLeadStats;
                }
            });

        $sellerLeadSummary = array_filter($sellerLeadSummary, function ($summary) {
            return !empty($summary);
        });


        //print_r("Total sellers with leads: ".json_encode($sellerLeadSummary)."\n");

        foreach ($sellerLeadSummary as $sellerId => $leadStats) {
            $sellerTotalLeadCount = 0;
            $sellerTotalLeadCredit = 0;
            $sellerLeadData = [];

            $categoryGrouped = [];
            foreach ($leadStats as $serviceId => $locations) {
                foreach ($locations as $area => $leadData) {

                    $count = $leadData['count'] ?? 0;
                    if ($count === 0) {
                        continue;
                    }
                    $credit_sum = $leadData['credit_sum'] ?? 0;
                    $categoryName = $leadData['category_name'] ?? 'N/A';
                    $leadIds = $leadData['lead_ids'] ?? [];

                    if (!isset($categoryGrouped[$categoryName])) {
                        $categoryGrouped[$categoryName] = [
                            'lead_ids'   => [],
                            'raw_count'  => 0,   // sum of incoming 'count' values (before dedupe)
                            'credit_sum' => 0,
                            'services'   => [],  // optional per-service/area detail
                        ];
                    }

                    $categoryGrouped[$categoryName]['lead_ids'] = array_merge(
                        $categoryGrouped[$categoryName]['lead_ids'],
                        $leadIds
                    );

                    $categoryGrouped[$categoryName]['raw_count'] += $count;
                    $categoryGrouped[$categoryName]['credit_sum'] += $credit_sum;

                    if (!isset($categoryGrouped[$categoryName]['services'][$serviceId])) {
                        $categoryGrouped[$categoryName]['services'][$serviceId] = [];
                    }
                    $categoryGrouped[$categoryName]['services'][$serviceId][$area] = [
                        'lead_ids'   => $leadIds,
                        'count'      => $count,

                        'credit_sum' => $credit_sum,
                    ];
                }
            }

            $sellerTotalLeadCount = 0;
            $sellerTotalLeadCredit = 0;
            $sellerLeadData = [];
            $alreadySent = EmailLog::where('user_id', $sellerId)
                ->whereDate('created_at', Carbon::today())
                ->where('setting_name', 'No Lead Purchased In 7 Days')
                ->exists();

            if ($alreadySent) {
                continue;
            }

            foreach ($categoryGrouped as $catName => $info) {
                // dedupe lead ids
                $uniqueLeadIds = array_values(array_unique($info['lead_ids']));

                // Choose how count should be derived:
                // - Option A (recommended): count = number of unique lead ids
                $finalCount = count($uniqueLeadIds);

                // - Option B (raw sum): uncomment the next line to use the raw summed count (may double-count same lead)
                // $finalCount = $info['raw_count'];

                $finalCreditSum = (int) $info['credit_sum']; // aggregated credit sum across rows

                // accumulate seller totals (based on finalCount & finalCreditSum)
                $sellerTotalLeadCount += $finalCount;
                $sellerTotalLeadCredit += $finalCreditSum;

                // prepare flattened data entry per category
                $sellerLeadData[] = [
                    'category_name' => $catName,
                    'lead_ids'      => $uniqueLeadIds,
                    'count'         => $finalCount,
                    'credit_sum'    => $finalCreditSum,
                    'services'      => $info['services'],
                ];
            }


            //print_r("seller Data: ".json_encode($sellerLeadData)."\n");

            if (!empty($sellerLeadData)) {
                $emailPayload = [
                    'total_lead_count' => $sellerTotalLeadCount,
                    'total_credit_sum' => $sellerTotalLeadCredit,
                    'lead_data'        => $sellerLeadData,
                    'credit_purchase' => 1
                ];


                $settingValue = 'No Lead Purchased In 7 Days';

                $alreadySent = EmailLog::where('user_id', $sellerId)
                    ->whereDate('created_at', Carbon::today())
                    ->where('setting_name', $settingValue)
                    ->exists();

                if (!$alreadySent) {
                    ZohoEmails::sendLeadsAfterDays($sellerId, $emailPayload, $settingValue);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function checkCreditAfter5Days()
    {
        $totalUnsentLeadEmails = 0;
        $now = Carbon::now();
        // Round down to last full hour if not exactly on the hour
        if ($now->minute === 0) {
            $to = $now->copy()->subHours(120); // Full hour, use this hour
        } else {
            $to = $now->copy()->subMinutes($now->minute)->subHours(120); // Not a full hour, roll back to previous full hour
        }
        $from = $to->copy()->subMinutes(59);



        $sellerLeadSummary = [];

        // $latestPlanHistory = PlanHistory::select('user_id', DB::raw('MAX(created_at) as last_plan_date'))
        //     ->groupBy('user_id')
        //     ->toBase();


        // User::leftJoinSub($latestPlanHistory, 'latest_plan', function ($join) {
        //     $join->on('users.id', '=', 'latest_plan.user_id');
        // })
        //     ->where('users.form_status', 1)
        //     ->where('users.user_type', 1)
        //     ->whereNotNull('users.zoho_record_id')
        //     ->where(function ($query) use ($from, $to) {
        //         $query->where(function ($q) use ($from, $to) {
        //             $q->whereBetween('latest_plan.last_plan_date', [$from, $to]);
        //         })
        //         ->where('users.total_credit', '<', 10);
        //     })
        //     ->select('users.id', 'users.total_credit', 'latest_plan.last_plan_date')
        //     ->chunk(1000, function ($sellersChunk) use (&$sellerLeadSummary) {
        $latestPlanHistory = PlanHistory::select('user_id', DB::raw('MAX(created_at) as last_plan_date'))
            ->groupBy('user_id')
            ->toBase();

        User::leftJoinSub($latestPlanHistory, 'latest_plan', function ($join) {
            $join->on('users.id', '=', 'latest_plan.user_id');
        })
            ->where('users.form_status', 1)
            ->where('users.user_type', 1)
            ->whereNotNull('users.zoho_record_id')
            ->where(function ($query) use ($from, $to) {
                $query->where(function ($q) use ($from, $to) {
                    $q->whereBetween('latest_plan.last_plan_date', [$from, $to])
                        ->orWhereNull('latest_plan.last_plan_date'); // Include users with no plan
                })
                    ->where('users.total_credit', '<', 10); // Apply credit filter to both
            })
            ->select('users.id', 'users.total_credit', 'latest_plan.last_plan_date')
            ->chunk(1000, function ($sellersChunk) use (&$sellerLeadSummary, $from, $to) {

                foreach ($sellersChunk as $seller) {
                    $serviceLocations = UserServiceLocation::where('user_id', $seller->id)->get();
                    $groupedLeadStats = [];
                    $nationwideLeadIds = [];
                    foreach ($serviceLocations as $location) {
                        $leadQuery = LeadRequest::with('category')
                            ->where('service_id', $location->service_id)
                            ->where('status', '!=', 'hired');

                        if ($location->nation_wide != 1) {

                            $groupKey = $location->postcode;

                            $leadQuery->whereNotIn('id', $nationwideLeadIds);
                            $leadQuery->where('postcode', $location->postcode);
                        } else {
                            $groupKey = 'nationwide';
                        }

                        $leads = $leadQuery->get();

                        foreach ($leads as $lead) {

                            $latestRecommended = RecommendedLead::where('seller_id', $seller->id)
                                ->latest('created_at')   // order by created_at DESC
                                ->first();

                            $alreadyRecommended = true;

                            if ($latestRecommended && $latestRecommended->created_at->between($from, $to)) {
                                $alreadyRecommended = false;
                            }
                            if (!$latestRecommended) {
                                $checkUsers = User::where('id', $seller->id)
                                    ->whereBetween('created_at', [$from, $to])   // order by created_at DESC
                                    ->first();


                                if ($checkUsers) {
                                    $alreadyRecommended = false;
                                }
                            }

                            if ($alreadyRecommended) {
                                continue;
                            }


                            $serviceId = $location->service_id;
                            $categoryName = $lead->category?->name ?? 'N/A';
                            $groupedLeadStats[$serviceId][$groupKey]['category_name'] = $categoryName;
                            $groupedLeadStats[$serviceId][$groupKey]['lead_ids'][] = $lead->id;
                            $groupedLeadStats[$serviceId][$groupKey]['count'] =
                                ($groupedLeadStats[$serviceId][$groupKey]['count'] ?? 0) + 1;

                            $groupedLeadStats[$serviceId][$groupKey]['credit_sum'] =
                                ($groupedLeadStats[$serviceId][$groupKey]['credit_sum'] ?? 0) + $lead->credit_score;

                            if ($location->nation_wide == 1) {
                                $nationwideLeadIds[] = $lead->id;
                            }
                        }
                    }

                    $sellerLeadSummary[$seller->id] = $groupedLeadStats;
                }
            });

        $sellerLeadSummary = array_filter($sellerLeadSummary, function ($summary) {
            return !empty($summary);
        });


        foreach ($sellerLeadSummary as $sellerId => $leadStats) {
            $sellerTotalLeadCount = 0;
            $sellerTotalLeadCredit = 0;
            $sellerLeadData = [];

            foreach ($leadStats as $serviceId => $locations) {
                foreach ($locations as $area => $leadData) {
                    $count = $leadData['count'] ?? 0;
                    if ($count === 0) {
                        continue;
                    }
                    $credit_sum = $leadData['credit_sum'] ?? 0;
                    $categoryName = $leadData['category_name'] ?? 'N/A';
                    $leadIds = $leadData['lead_ids'] ?? [];

                    if (!isset($categoryGrouped[$categoryName])) {
                        $categoryGrouped[$categoryName] = [
                            'lead_ids'   => [],
                            'raw_count'  => 0,   // sum of incoming 'count' values (before dedupe)
                            'credit_sum' => 0,
                            'services'   => [],  // optional per-service/area detail
                        ];
                    }

                    $categoryGrouped[$categoryName]['lead_ids'] = array_merge(
                        $categoryGrouped[$categoryName]['lead_ids'],
                        $leadIds
                    );

                    $categoryGrouped[$categoryName]['raw_count'] += $count;
                    $categoryGrouped[$categoryName]['credit_sum'] += $credit_sum;

                    if (!isset($categoryGrouped[$categoryName]['services'][$serviceId])) {
                        $categoryGrouped[$categoryName]['services'][$serviceId] = [];
                    }
                    $categoryGrouped[$categoryName]['services'][$serviceId][$area] = [
                        'lead_ids'   => $leadIds,
                        'count'      => $count,

                        'credit_sum' => $credit_sum,
                    ];
                }
            }
            $sellerTotalLeadCount = 0;
            $sellerTotalLeadCredit = 0;
            $sellerLeadData = [];
            $alreadySent = EmailLog::where('user_id', $sellerId)
                ->whereDate('created_at', Carbon::today())
                ->where('setting_name', 'No Credit Purchased In 5 Days')
                ->exists();

            if ($alreadySent) {
                continue;
            }

            foreach ($categoryGrouped as $catName => $info) {
                // dedupe lead ids
                $uniqueLeadIds = array_values(array_unique($info['lead_ids']));

                // Choose how count should be derived:
                // - Option A (recommended): count = number of unique lead ids
                $finalCount = count($uniqueLeadIds);

                // - Option B (raw sum): uncomment the next line to use the raw summed count (may double-count same lead)
                // $finalCount = $info['raw_count'];

                $finalCreditSum = (int) $info['credit_sum']; // aggregated credit sum across rows

                // accumulate seller totals (based on finalCount & finalCreditSum)
                $sellerTotalLeadCount += $finalCount;
                $sellerTotalLeadCredit += $finalCreditSum;

                // prepare flattened data entry per category
                $sellerLeadData[] = [
                    'category_name' => $catName,
                    'lead_ids'      => $uniqueLeadIds,
                    'count'         => $finalCount,
                    'credit_sum'    => $finalCreditSum,
                    'services'      => $info['services'],
                ];
            }


            if (!empty($sellerLeadData)) {
                $emailPayload = [
                    'total_lead_count' => $sellerTotalLeadCount,
                    'total_credit_sum' => $sellerTotalLeadCredit,
                    'lead_data' => $sellerLeadData,
                    'credit_purchase' => 1
                ];
                $settingValue = 'No Credit Purchased In 5 Days';
                $alreadySent = EmailLog::where('user_id', $sellerId)
                    ->whereDate('created_at', Carbon::today())
                    ->where('setting_name', $settingValue)
                    ->exists();


                if (!$alreadySent) {
                    ZohoEmails::creditsAfter5Days($sellerId, $emailPayload, $settingValue);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }






    ##################################################################################################################################################
    ################################################       CRON FUNCTIONS       ######################################################################
    ##################################################################################################################################################



    private function UnsoldLeadsStep1(Request $request, LeadService $leadService)
    {
        $totalUnsentLeadEmails = 0;

        $now = Carbon::now();
        // Round down to last full hour if not exactly on the hour
        if ($now->minute === 0) {
            $to = $now->copy()->subHours(48); // Full hour, use this hour
        } else {
            $to = $now->copy()->subMinutes($now->minute)->subHours(48); // Not a full hour, roll back to previous full hour
        }
        $from = $to->copy()->subMinutes(59);

        $leads = LeadRequest::whereBetween('created_at', [$from, $to])->where('status', 'new')->get();

        if ($leads->isEmpty()) {
            return $this->sendError(__('No leads found older than 48 hours'), 404);
        }

        foreach ($leads as $lead) {

            $result = $leadService->getAllSellers($lead, null, 2);

            if (isset($result['response']['sellers'])) {

                foreach ($result['response']['sellers'] as $seller) {

                    $alreadySent = EmailLog::where('user_id', $seller->id)
                        ->where('lead_id', $lead->id)
                        ->where('setting_name', 'Unsold Leads')
                        ->whereIn('step', [1])
                        ->exists();

                    $alreadyRecommended = RecommendedLead::where('seller_id', $seller->id)
                        ->where('lead_id', $lead->id)
                        ->exists();

                    if ($alreadyRecommended) {
                        continue;
                    }


                    if (!$alreadySent) {
                        $dataU1['userId'] = $seller->id;
                        $dataU1['leadId'] = $lead->id;
                        $dataU1['setting_name'] = 'Unsold Leads';
                        $dataU1['subject'] = 'New Opportunity in Your Area – Lead Details Inside';
                        $dataU1['step'] = 1;
                        ZohoEmails::unsoldLeadEmail($dataU1);
                        $totalUnsentLeadEmails++;
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    private function unSoldLeadsStep2(Request $request, LeadService $leadService)
    {
        $totalUnsentLeadEmails = 0;
        $now = Carbon::now();
        // Round down to last full hour if not exactly on the hour
        if ($now->minute === 0) {
            $to = $now->copy()->subHours(84); // Full hour, use this hour
        } else {
            $to = $now->copy()->subMinutes($now->minute)->subHours(84); // Not a full hour, roll back to previous full hour
        }
        $from = $to->copy()->subMinutes(59);


        $leads = LeadRequest::whereBetween('created_at', [$from, $to])->where('status', 'new')->get();

        if ($leads->isEmpty()) {
            return $this->sendError(__('No leads found older than 84 hours'), 404);
        }



        foreach ($leads as $lead) {

            $result = $leadService->getAllSellersNationList($lead, null, 1);

            if (isset($result['response']['sellers'])) {

                foreach ($result['response']['sellers'] as $seller) {

                    $alreadyRecommended = RecommendedLead::where('seller_id', $seller->id)
                        ->where('lead_id', $lead->id)
                        ->exists();

                    if ($alreadyRecommended) {
                        continue;
                    }

                    $hasStep1 = EmailLog::where('user_id', $seller->id)
                        ->where('lead_id', $lead->id)
                        ->where('setting_name', 'Unsold Leads')
                        ->where('step', 1)
                        ->exists();

                    $hasStep2 = EmailLog::where('user_id', $seller->id)
                        ->where('lead_id', $lead->id)
                        ->where('setting_name', 'Unsold Leads (Nationwide)')
                        //->where('created_at', '>=', Carbon::now()->subHours(12))
                        ->where('step', 2)
                        ->exists();

                    if ($hasStep1 && !$hasStep2) {
                        $dataU2['userId'] = $seller->id;
                        $dataU2['leadId'] = $lead->id;
                        $dataU2['setting_name'] = 'Unsold Leads (Nationwide)';
                        $dataU2['subject'] = 'Last Chance: Nationwide Lead Still Open for Bids!';
                        $dataU2['step'] = 2;
                        ZohoEmails::unsoldLeadEmail($dataU2);
                        $totalUnsentLeadEmails++;
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }


    public function unSoldLeadsStep3(Request $request, LeadService $leadService)
    {
        $totalLeadEmails = 0;
        $sentEmails = [];

        print_r("unSoldLeadsStep3 called\n");

        $now = Carbon::now();
        // Round down to last full hour if not exactly on the hour
        if ($now->minute === 0) {
            $to = $now->copy()->subHours(96); // Full hour, use this hour
        } else {
            $to = $now->copy()->subMinutes($now->minute)->subHours(96); // Not a full hour, roll back to previous full hour
        }
        $from = $to->copy()->subMinutes(59); // Subtract 59 minutes to get the start of the 1-hour window


        $leads = LeadRequest::whereBetween('created_at', [$from, $to])
            ->where('status', 'new')
            ->get();

        print_r("Found " . count($leads) . " leads\n");
        print_r("Time window from " . $from->toDateTimeString() . " to " . $to->toDateTimeString() . "\n");
        $discountPercent = CustomHelper::setting_value('discount_percent_of_unsold_leads', 20);

        foreach ($leads as $l) {
            $discount_applied = $l->discount_applied;
            if (!$discount_applied) {
                $curCredit = $l->credit_score;
                $newCredit = floor($curCredit - (($discountPercent / 100) * $curCredit));

                $dataUC['credit_score'] = $newCredit;
                $dataUC['discount_applied'] = 1;
                $dataUC['old_credit'] = $curCredit;
                LeadRequest::where('id', $l->id)->update($dataUC);

                $l->discount_applied = 1;
                $l->old_credit = $curCredit;
                $l->credit_score = $newCredit;
            }

            $localSellers = $leadService->getAllSellers($l, null, 2)['response']['sellers'];
            $nationSellers = $leadService->getAllSellersNationList($l, null, 1)['response']['sellers'];
            $allSellers = $localSellers->merge($nationSellers)
                ->unique('user_id')
                ->values();
            foreach ($allSellers as $seller) {
                $hasStep1 = EmailLog::where('user_id', $seller->id)
                    ->where('lead_id', $l->id)
                    ->where('setting_name', 'Unsold Leads')
                    ->where('step', 1)
                    ->exists();

                $hasStep2 = EmailLog::where('user_id', $seller->id)
                    ->where('lead_id', $l->id)
                    ->where('setting_name', 'Unsold Leads (Nationwide)')
                    ->where('step', 2)
                    ->exists();

                $hasStep3 = EmailLog::where('user_id', $seller->id)
                    ->where('lead_id', $l->id)
                    ->where('setting_name', 'Unsold Leads (Discount)')
                    ->where('step', 3)
                    ->exists();

                if ($hasStep1 && $hasStep2 && !$hasStep3) {
                    $dataU3['userId'] = $seller->id;
                    $dataU3['leadId'] = $l->id;
                    $dataU3['setting_name'] = 'Unsold Leads (Discount)';
                    $dataU3['subject'] = 'Exclusive Offer: ' . $discountPercent . '% Discount on This Lead – Act Now!';
                    $dataU3['step'] = 3;
                    ZohoEmails::unsoldLeadEmail($dataU3);
                    $e = User::where('id', $seller->id)->value('email');
                    array_push($sentEmails, $e);
                    $totalLeadEmails++;
                }
            }
        }
        return response()->json([
            'status' => 'success',
            'total_lead_emails' => $totalLeadEmails,
            'emails' => $sentEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }


    public function leadPurchaseStatusUpdate48hrs(Request $request)
    {
        $totalLeadEmails = 0;
        $now = Carbon::now();
        // Round down to last full hour if not exactly on the hour
        if ($now->minute === 0) {
            $to = $now->copy()->subHours(48); // Full hour, use this hour
        } else {
            $to = $now->copy()->subMinutes($now->minute)->subHours(48); // Not a full hour, roll back to previous full hour
        }
        $from = $to->copy()->subMinutes(59); // Subtract 59 minutes to get the start of the 1-hour window

        $leads = LeadRequest::whereBetween('created_at', [$from, $to])
            ->where('status', 'pending')
            ->get();

        foreach ($leads as $lead) {
            $rLeads = RecommendedLead::where('lead_id', $lead->id)
                ->get();

            foreach ($rLeads as $rLead) {
                $emailSent = EmailLog::where('user_id', $rLead->seller_id)
                    ->where('lead_id', $rLead->lead_id)
                    ->where('setting_name', 'Lead Purchase Status Update (48 hrs)')
                    ->exists();

                if (!$emailSent) {
                    ZohoEmails::leadPurchaseStatusUpdateEmail($rLead, 'Lead Purchase Status Update (48 hrs)', 'Update your lead status');
                    $totalLeadEmails++;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'total_lead_emails' => $totalLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function leadPurchaseStatusUpdate96hrs(Request $request)
    {
        $totalLeadEmails = 0;
        $now = Carbon::now();
        // Round down to last full hour if not exactly on the hour
        if ($now->minute === 0) {
            $to = $now->copy()->subHours(96); // Full hour, use this hour
        } else {
            $to = $now->copy()->subMinutes($now->minute)->subHours(96); // Not a full hour, roll back to previous full hour
        }
        $from = $to->copy()->subMinutes(59); // Subtract 59 minutes to get the start of the 1-hour window

        $leads = LeadRequest::whereBetween('created_at', [$from, $to])
            ->where('status', 'pending')
            ->get();

        foreach ($leads as $lead) {
            $rLeads = RecommendedLead::where('lead_id', $lead->id)
                ->get();

            foreach ($rLeads as $rLead) {
                $emailSent = EmailLog::where('user_id', $rLead->seller_id)
                    ->where('lead_id', $rLead->lead_id)
                    ->where('setting_name', 'Lead Purchase Status Update (96 hrs)')
                    ->exists();

                if (!$emailSent) {
                    ZohoEmails::leadPurchaseStatusUpdateEmail($rLead, 'Lead Purchase Status Update (96 hrs)', 'Confirmation! Update your lead status');
                    $totalLeadEmails++;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'total_lead_emails' => $totalLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function sendGroupedLeadEmail()
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            ->where('form_status', 1)
            ->where('user_type', 1)
            ->select('id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {

                foreach ($sellersChunk as $seller) {

                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();


                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                   $emailedLeadIds = EmailLog::where('user_id', $seller->id)
                        ->where('setting_name', 'Send Lead Details Email At Evening')
                        ->pluck('lead_id')
                        ->all();

                    $emailedLookup = array_flip($emailedLeadIds); // O(1) lookup

                    $finalLeads = $filteredLeads->filter(function ($lead) use ($emailedLookup) {
                        return !isset($emailedLookup[$lead->id]);
                    })->values();

                    if ($finalLeads->isEmpty()) {
                        continue;
                    }

                    if ($finalLeads->isNotEmpty()) {
                        // Check if email was already sent today for this seller


                            // Send one email for all leads
                            $result=ZohoEmails::sendGroupedLeadDetails($seller->id, $finalLeads->pluck('id')->toArray()); // you must implement this
                            $totalUnsentLeadEmails++;

                            Log::info('Zoho Email for bid-not-enough leads', [
                                'user_id' => $seller->id,
                                'response' => $result,
                            ]);
                            // Log one entry per seller to avoid re-sending

                    }
                }
            });

        unset($leadPref);

        return [
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
