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
use App\Helpers\Zoho\ZohoQuoteRequest;
use App\Models\AbandonedUser;
use Illuminate\Support\Facades\Log;
use App\Services\D7LeadFinderService;

class CronController extends Controller
{
    public function onHourlyBasis(Request $request, LeadService $leadService)
    {

        set_time_limit(0);

        $this->UnsoldLeadsStep1($request, $leadService);
        $this->unSoldLeadsStep2($request, $leadService);
        $this->unSoldLeadsStep3($request, $leadService);
        // $this->UnsoldLeadsAfter12hrs($request, $leadService);

        $this->leadPurchaseStatusUpdate48hrs($request);
        $this->leadPurchaseStatusUpdate96hrs($request);

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function onEveningBasis()
    {
        $sendGroupedLeadEmail = $this->sendGroupedLeadEmail();
        $missedTodaySecuredToday = $this->missedTodaySecuredTodayLastChanceToBidAndSecure();


        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'new_lead_after_evening' => $sendGroupedLeadEmail,
                'your_daily_leads_report' => $missedTodaySecuredToday,
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function onMinuteBasis()
    {

        $d7Service = app(D7LeadFinderService::class);
        $d7Response = $d7Service->getSearchSuppliers();

        $sendAbandonedCartReminderEmail = $this->sendAbandonedCartReminderEmails();

        $customerReplyReminderEmail = $this->sendNotifyCustomerRequestRepliesReminderEmail();
        $updateLeadRequestExpairedStatus = $this->updateLeadRequestExpairedStatus();


        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'd7_supplier_summary' => $d7Response,
                'abandoned_cart_reminder_summary' => $sendAbandonedCartReminderEmail,
                'customerReplyReminderEmail' => $customerReplyReminderEmail,
                'updateLeadRequestExpairedStatus' => $updateLeadRequestExpairedStatus,
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }



    public function onDayBasis()
    {
        $newLeadAfter7days = $this->sendLeadsAfter7Days();

        $newLeadAfter5days = $this->checkCreditAfter5Days();

        $sendNextDayExpiredQuoteEmail = $this->sendNextDayExpiredQuoteEmail();
        $sendNewProPostcodeEmail = $this->sendnotifyCustomerNewProfessionalinPostcodeEmail();
        $sendLeadRequestStatusEmailToCustomer = $this->sendLeadRequestStatusEmailToCustomer();
        $sendCreditBelowFiftyEmail = $this->sendCreditBelowFiftyEmail();


        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'new_lead_after_7_days' => $newLeadAfter7days,
                'new_lead_after_5_days' => $newLeadAfter5days,
                'next_day_expired_quote_email' => $sendNextDayExpiredQuoteEmail,
                'pro_available_postcode_email' => $sendNewProPostcodeEmail,
                'sendLeadRequestStatusEmailToCustomer' => $sendLeadRequestStatusEmailToCustomer,
                'sendCreditBelowFiftyEmail' => $sendCreditBelowFiftyEmail
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
            ->where('created_at', '<=', Carbon::now()->subDays(2))
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
            ->whereIn('users.user_type', [1, 3])
            ->whereNotNull('users.zoho_record_id')
            ->whereBetween(
                DB::raw('COALESCE(latest_plan.last_plan_date, users.created_at)'),
                [$from, $to]
            )
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

                        $alreadySents = EmailLog::where('user_id', $seller->id)
                            ->whereDate('created_at', Carbon::today())
                            ->where('setting_name', 'Send Lead Details Email At Evening')
                            ->exists();


                        if ($alreadySents) {
                            continue;
                        }


                        // Send one email for all leads
                        $result = ZohoEmails::sendGroupedLeadDetails($seller->id, $finalLeads->pluck('id')->toArray()); // you must implement this
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

    private function UnsoldLeadsAfter12hrs(Request $request, LeadService $leadService)
    {
        $totalUnsentLeadEmails = 0;

        $now = Carbon::now();
        // Round down to last full hour if not exactly on the hour
        if ($now->minute === 0) {
            $to = $now->copy()->subHours(12); // Full hour, use this hour
        } else {
            $to = $now->copy()->subMinutes($now->minute)->subHours(12); // Not a full hour, roll back to previous full hour
        }
        $from = $to->copy()->subMinutes(59);

        $leads = LeadRequest::whereBetween('created_at', [$from, $to])->where('status', 'new')->get();

        if ($leads->isEmpty()) {
            return $this->sendError(__('No leads found older than 12 hours'), 404);
        }

        foreach ($leads as $lead) {

            $userId = $lead->customer_id;
            $result = $leadService->getAllSellers($lead);

            if (isset($result['response']['sellers'])) {

                $recommendedCount = CustomHelper::setting_value("recommended_list_count", 0);
                $w80 = (int) ($recommendedCount * 0.8);

                $sorted = $result['response']['sellers']
                    ->sortByDesc('total_credit')
                    ->values();

                $topN = $sorted->take($w80);
                $remaining = $sorted->slice($w80)
                    ->sortBy('distance')
                    ->values();


                $finalSorted = $topN->merge($remaining);

                $sellersFinalList = $finalSorted->take(10)->values()->toArray();

                $alreadySent = EmailLog::where('user_id', $userId)
                    ->where('lead_id', $lead->id)
                    ->where('setting_name', 'Unsold Leads After 12 hrs')
                    ->exists();

                $alreadyRecommended = RecommendedLead::where('lead_id', $lead->id)
                    ->exists();

                if ($alreadyRecommended) {
                    continue;
                }


                if (!$alreadySent) {
                    $dataU1['userId'] = $userId;
                    $dataU1['leadId'] = $lead->id;
                    $dataU1['sellerDetails'] = $sellersFinalList;
                    $dataU1['setting_name'] = 'Unsold Leads After 12 hrs';
                    $dataU1['subject'] = 'Opportunity Alert: A Lead Needs Your Service';
                    ZohoEmails::unsoldLeadEmailAfter12hrs($dataU1);
                    $totalUnsentLeadEmails++;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }


    public  function sendNextDayExpiredQuoteEmail()
    {
        $batchSize = 500;
        $expirationDays = CustomHelper::setting_value("close_leads_after_days", 14);
        $dayAfterExpiry = 1;
        $totalUnsentLeadEmails = 0;

        LeadRequest::with(['customer', 'category'])
            ->whereIn('status', ['new', 'pending'])
            ->whereDate('created_at', '=', now()->subDays($expirationDays + $dayAfterExpiry)->toDateString())
            ->whereNull('lead_requests.deleted_at')
            ->whereHas('customer', function ($query) {
                $query->whereNotNull('zoho_record_id')
                    ->whereNull('deleted_at');
            })
            ->orderBy('id')
            ->chunk($batchSize, function ($leads) use (&$totalUnsentLeadEmails) {

                foreach ($leads as $lead) {

                    try {
                        if (empty($lead->customer) || empty($lead->customer->email)) {
                            continue;
                        }

                        $customerName = $lead->customer->name ?? '';
                        $serviceName = $lead->category->name ?? '';
                        $userId = $lead->customer->id ?? null;

                        $alreadySent = EmailLog::where('user_id', $userId)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'Next Day Expired Quote Email')
                            ->exists();

                        if (!$alreadySent) {

                            $user = User::where('email', $lead->customer->email)->where('form_status', '1')->first();
                            $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
                            $user->update(['remember_token' => $token]);

                            $data = [
                                'userId' => $userId,
                                'leadId' => $lead->id,
                                'customerName' => $customerName,
                                'serviceName' => $serviceName,
                                'token' => $token ?? '',
                                'setting_name' => 'Next Day Expired Quote Email',
                                'subject' => "Need a hand finishing your project",
                            ];

                            ZohoEmails::sendNextDayExpiredQuoteEmail($data);

                            $totalUnsentLeadEmails++;
                        }
                    } catch (\Throwable $e) {
                        return $this->sendError(__("Failed next-day expired quote email for lead ID {$lead->id}: {$e->getMessage()}"), 404);
                    }
                }
            });

        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }




    // public function sendNoBidsOnQuote24hrsEmail()
    // {
    //     $batchSize = 500;
    //     $hoursAgo = 24;
    //     $totalEmailsSent = 0;

    //     LeadRequest::with(['customer', 'category'])
    //         ->where('status', 'new')
    //         ->whereDoesntHave('recommendedLeads')
    //         ->where('created_at', '<=', now()->subHours($hoursAgo))
    //         ->whereNull('deleted_at')
    //         ->whereHas('customer', function ($query) {
    //             $query->whereNotNull('zoho_record_id')
    //                   ->whereNull('deleted_at');
    //         })
    //         ->orderBy('id')
    //         ->chunk($batchSize, function ($leads) use (&$totalEmailsSent) {
    //              Log::info('Fetched Leads Batch', ['count' => $leads->count()]);
    //             foreach ($leads as $lead) {

    //                 $logData = [
    //                     'lead_id' => $lead->id,
    //                     'lead_status' => $lead->status,
    //                     'customer_name' => $lead->customer->name ?? null,
    //                     'customer_email' => $lead->customer->email ?? null,
    //                     'service' => $lead->category->name ?? null,
    //                     'created_at' => $lead->created_at,
    //                 ];

    //                 \Log::info('Lead found without recommended leads:', $logData);


    //                 try {
    //                     $user = $lead->customer;
    //                     if (empty($user) || empty($user->email)) {
    //                         continue;
    //                     }

    //                     $alreadySent = EmailLog::where('user_id', $user->id)
    //                         ->where('lead_id', $lead->id)
    //                         ->where('setting_name', 'No Bids On Quote Request')
    //                         ->exists();

    //                     if ($alreadySent) {
    //                         continue;
    //                     }

    //                     $user = User::where('email', $user->email)
    //                         ->where('form_status', '1')
    //                         ->first();

    //                     if (!$user) {
    //                         continue;
    //                     }

    //                     $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
    //                     $user->update(['remember_token' => $token]);

    //                     $data = [
    //                         'userId' => $user->id,
    //                         'leadId' => $lead->id,
    //                         'customerName' => $user->name ?? '',
    //                         'serviceName' => $lead->category->name ?? '',
    //                         'token' => $token,
    //                          ];

    //                     ZohoEmails::sendNoBidsOnQuote24hrsEmail($data);


    //                     $totalEmailsSent++;

    //                 } catch (\Throwable $e) {
    //                     Log::error("Failed No Bids On Quote 24hrs Email for lead ID {$lead->id}: {$e->getMessage()}");
    //                     continue;
    //                 }
    //             }
    //         });

    //     return response()->json([
    //         'status' => 'success',
    //         'emails_sent' => $totalEmailsSent,
    //         'timestamp' => now()->toDateTimeString(),
    //     ]);
    // }

    public function sendAbandonedCartReminderEmails()
    {
        $batchSize = 500;
        $totalEmailsSent = 0;

        $cutoffTime = now()->subMinutes(30);

        $query = AbandonedUser::with(['categoryData'])
            ->whereIn('user_type', [2, 3])
            ->where('form_status', 0)
            ->where('created_at', '<=', $cutoffTime)
            ->whereNotNull('email')
            ->whereNotNull('zoho_record_id')
            ->whereNull('deleted_at')
            ->orderBy('id', 'DESC');

        // Process users in chunks
        $query->chunk($batchSize, function ($users) use (&$totalEmailsSent) {

            foreach ($users as $user) {
                try {
                    // Check if reminder already sent
                    $alreadySent = EmailLog::where('user_id', $user->id)
                        ->where('setting_name', 'Send Abandoned Encouragement Email')
                        ->exists();

                    if (!$alreadySent) {

                        $serviceName = $user->categoryData->name ?? null;
                        // Send email
                        ZohoEmails::sendAbandonedEncouragementEmail($user->id, $serviceName);

                        $totalEmailsSent++;
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to send abandoned cart email to user ID {$user->id}: {$e->getMessage()}");
                    continue;
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'emails_sent' => $totalEmailsSent,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }


    public function sendnotifyCustomerNewProfessionalinPostcodeEmail()
    {
        $batchSize = 500;
        $hoursAgo = 24;
        $totalEmailsSent = 0;

        LeadRequest::with(['customer', 'category'])
            ->where('status', 'new')
            ->whereDoesntHave('recommendedLeads')
            ->where('created_at', '<=', now()->subHours($hoursAgo))
            ->whereNull('deleted_at')
            ->whereHas('customer', function ($query) {
                $query->whereNotNull('zoho_record_id')
                    ->whereNull('deleted_at');
            })
            ->orderBy('id')
            ->chunk($batchSize, function ($leads) use (&$totalEmailsSent) {

                Log::info('Fetched Leads Batch', ['count' => $leads->count()]);

                foreach ($leads as $lead) {

                    try {

                        $customer = $lead->customer;

                        if (!$customer || empty($customer->email)) {
                            continue;
                        }

                        $alreadySent = EmailLog::where('user_id', $customer->id)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'Notify Customer New Professional in Postcode added')
                            ->exists();

                        if ($alreadySent) {
                            continue;
                        }

                        // Customer must have completed form
                        $user = User::where('email', $customer->email)
                            ->where('form_status', '1')
                            ->first();

                        if (!$user) {
                            continue;
                        }

                        // -----------------------------
                        // GET SELLERS FROM LEADSERVICE
                        // -----------------------------
                        $leadService = app(LeadService::class);
                        $sellerData = $leadService->getAllSellers($lead);

                        $sellers = $sellerData['response']['sellers'] ?? [];

                        if (empty($sellers)) {
                            Log::info("No sellers found for Lead ID: {$lead->id}");
                            continue;
                        }

                        $sellerCount = collect($sellers)->count();

                        Log::info("Sellers found for Lead ID {$lead->id}: {$sellerCount}");

                        if ($sellerCount > 0) {

                            ZohoEmails::notifyCustomerNewProfessionalinPostcode($lead->id);

                            $totalEmailsSent++;
                        }
                    } catch (\Throwable $e) {
                        Log::error("Error sending 24hr seller email for Lead ID {$lead->id}: {$e->getMessage()}");
                        continue;
                    }
                }
            });

        return response()->json([
            'status' => 'success',
            'emails_sent' => $totalEmailsSent,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }


    public function sendLeadRequestStatusEmailToCustomer()
    {
        $batchSize = 500;
        $emailSchedule = [
            1 => 3,
            2 => 7,
            3 => 10,
            4 => 14,
        ];

        $totalEmailsSent = 0;

        LeadRequest::whereIn('status', ['pending'])
            ->whereHas('recommendedLeads')
            ->where('created_at', '<=', now()->subDays(3))
            ->whereNull('deleted_at')
            ->whereHas('customer', function ($query) {
                $query->whereNotNull('zoho_record_id')
                    ->whereNull('deleted_at');
            })
            ->orderBy('id')
            ->chunk($batchSize, function ($leads) use (&$totalEmailsSent, $emailSchedule) {

                foreach ($leads as $lead) {

                    try {

                        $lastStep = EmailLog::where('user_id', $lead->customer_id)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'Lead Request Hired Status Email to Customer')
                            ->max('step');

                        $lastEmailDate = EmailLog::where('user_id', $lead->customer_id)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'Lead Request Hired Status Email to Customer')
                            ->max('created_at');

                        $nextStep = $lastStep ? $lastStep + 1 : 1;


                        if ($nextStep > 4) {
                            continue;
                        }

                        $requiredDays = $emailSchedule[$nextStep];

                        $nextEligibleDate = $lastEmailDate
                            ? \Carbon\Carbon::parse($lastEmailDate)->addDays($requiredDays)
                            : $lead->created_at->addDays($requiredDays);

                        if (now()->lt($nextEligibleDate)) {
                            continue;
                        }

                        $bids = RecommendedLead::where('buyer_id', $lead->customer_id)
                            ->where('lead_id', $lead->id)
                            ->orderBy('distance', 'ASC')
                            ->get();

                        if ($bids->isEmpty()) {
                            continue;
                        }

                        $sellerIds = $bids->pluck('seller_id')->unique();

                        $sellers = User::whereIn('id', $sellerIds)->get();

                        if ($sellers->isEmpty()) {
                            continue;
                        }

                        ZohoEmails::sendLeadRequestHiredStatusEmailToCustomer($lead->id, $sellers, $nextStep);

                        $totalEmailsSent++;
                    } catch (\Throwable $e) {
                        Log::error(
                            "Lead email error | Lead ID {$lead->id} | {$e->getMessage()}"
                        );
                    }
                }
            });

        return response()->json([
            'status'        => 'success',
            'emails_sent'  => $totalEmailsSent,
            'timestamp'    => now()->toDateTimeString(),
        ]);
    }


    public function sendCreditBelowFiftyEmail()
    {

        $batchSize = 500;
        $totalEmailsSent = 0;

        User::where('user_type', 1)
            ->where('form_status', 1)
            ->where('total_credit', '<', 50)  // Current credit below 50
            ->whereNotNull('email')
            ->whereNotNull('zoho_record_id')
            ->whereNull('deleted_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('plan_histories')
                    ->whereColumn('plan_histories.user_id', 'users.id')
                    ->where('plan_histories.price', '>', 0)
                    ->whereNull('plan_histories.deleted_at');
            })
            ->orderBy('id', 'DESC')
            ->chunk($batchSize, function ($users) use (&$totalEmailsSent) {

                foreach ($users as $user) {
                    try {


                        // Last time credit was purchased (recovery point)
                        $lastRecoveredAt = DB::table('plan_histories')
                            ->where('user_id', $user->id)
                            ->where('price', '>', 0)          // paid only
                            ->whereNull('deleted_at')          // active only
                            ->orderBy('created_at', 'DESC')
                            ->value('created_at');

                        //  Emails sent after last recovery
                        $emailQuery = EmailLog::where('user_id', $user->id)
                            ->where('setting_name', 'Lead Buyer Credit Below Fifty Email');

                        if ($lastRecoveredAt) {
                            $emailQuery->where('created_at', '>', $lastRecoveredAt);
                        }

                        $emailsSentCount = $emailQuery->count();

                        // Stop after 3 emails
                        if ($emailsSentCount >= 3) {
                            continue;
                        }

                        // Weekly gap check
                        $lastEmailAt = $emailQuery->orderBy('created_at', 'DESC')->value('created_at');
                        if ($lastEmailAt && now()->diffInDays($lastEmailAt) < 7) {
                            continue;
                        }


                        ZohoEmails::sendLeadBuyerLowCreditEmail($user->id);
                        $totalEmailsSent++;
                    } catch (\Throwable $e) {
                        Log::error("Failed to send low credit email for user {$user->id}: {$e->getMessage()}");
                        continue;
                    }
                }
            });

        return response()->json([
            'status' => 'success',
            'emails_sent' => $totalEmailsSent,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }


    public function sendNotifyCustomerRequestRepliesReminderEmail()
    {
        $batchSize = 500;

        $hoursAgo = 6;       // email delay
        $recentHours = 24;
        $totalEmailsSent = 0;

        LeadRequest::with(['customer', 'category'])
            ->where('status', 'new') // or != 'purchased' if needed
            ->whereDoesntHave('recommendedLeads')
            ->where('created_at', '>=', now()->subHours($recentHours)) // only recent leads
            ->where('created_at', '<=', now()->subHours($hoursAgo))
            ->whereNull('deleted_at')

            //  ONLY FIRST LEAD PER CUSTOMER
            ->whereIn('id', function ($query) use ($recentHours) {
                $query->selectRaw('MIN(id)')
                    ->from('lead_requests')
                    ->whereNull('deleted_at')
                    ->where('status', 'new')
                    ->where('created_at', '>=', now()->subHours($recentHours)) // 🔥 IMPORTANT
                    ->groupBy('customer_id');
            })

            ->whereHas('customer', function ($query) {
                $query->whereNotNull('zoho_record_id')
                    ->whereNull('deleted_at');
            })
            ->orderBy('id')
            ->chunk($batchSize, function ($leads) use (&$totalEmailsSent) {

                foreach ($leads as $lead) {

                    try {

                        $customer = $lead->customer;

                        if (!$customer || empty($customer->email)) {
                            continue;
                        }

                        $alreadySent = EmailLog::where('user_id', $customer->id)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'Notify Customer Request Replies Reminder')
                            ->exists();

                        if ($alreadySent) {
                            continue;
                        }

                        // Customer must have completed form
                        $user = User::where('email', $customer->email)
                            ->where('form_status', '1')
                            ->first();

                        if (!$user) {
                            continue;
                        }

                        ZohoEmails::notifyCustomerRequestRepliesReminder($user->id, $lead->id);


                        $totalEmailsSent++;
                    } catch (\Throwable $e) {
                        Log::error("Error sending 6hr customer email for Lead ID {$lead->id}: {$e->getMessage()}");
                        continue;
                    }
                }
            });

        return response()->json([
            'status' => 'success',
            'emails_sent' => $totalEmailsSent,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }


    public function missedTodaySecuredTodayLastChanceToBidAndSecure()
    {
        $totalSent   = 0;
        $leadPref    = new LeadService();
        $settingName = 'Your Daily Leads Report';
        $since24Hours = now()->subDay();
        $leadSlotCount = 5;

        // PRELOAD: 5+ bids wali leads (ONLY ONCE)
        $slotFullLeadIds = DB::table('recommended_leads')
            ->select('lead_id')
            ->groupBy('lead_id')
            ->havingRaw('COUNT(*) >= ?', [$leadSlotCount])
            ->pluck('lead_id')
            ->toArray();

        User::query()
            ->whereNotNull('zoho_record_id')
            ->where('form_status', 1)
            ->where('user_type', 1)
            ->select('id', 'total_credit')
            ->chunkById(500, function ($sellers) use (&$totalSent, $leadPref, $settingName, $since24Hours, $slotFullLeadIds) {

                foreach ($sellers as $seller) {
                    try {

                        // One email per day
                        $alreadySentToday = EmailLog::where('user_id', $seller->id)
                            ->whereDate('created_at', today())
                            ->where('setting_name', $settingName)
                            ->exists();

                        if ($alreadySentToday) {
                            continue;
                        }

                        $sections = [
                            'missed_secured_lastchance' => [],
                            'credit_enough'             => [],
                            'credit_not_enough'         => [],
                        ];

                        // Already emailed lead IDs (once per seller)
                        $alreadyEmailedLeadIds = EmailLog::where('user_id', $seller->id)
                            ->where('setting_name', $settingName)
                            ->pluck('lead_id')
                            ->toArray();

                        /**
                         *  Missed / Secured / Last Chance
                         * ONLY leads with 5+ bids
                         */
                        $leads = $leadPref
                            ->getSellerLeadsBaseQuery($seller->id)
                            ->where('created_at', '>=', $since24Hours)
                            ->whereIn('id', $slotFullLeadIds)
                            ->orderByDesc('id')
                            ->get();

                        $filtered = $leadPref->leadsAccordingTOSellerPref($seller->id, $leads);

                        $sections['missed_secured_lastchance'] = $filtered
                            ->whereNotIn('id', $alreadyEmailedLeadIds)
                            ->pluck('id')
                            ->take(2)
                            ->toArray();

                        /**
                         *  AutoBid – Credit Enough
                         */

                        $sections['credit_enough'] = DB::table('recommended_leads')
                            ->where('seller_id', $seller->id)
                            ->where('created_at', '>=', $since24Hours)
                            ->where('status', 'pending')
                            ->orderByDesc('id')
                            ->pluck('lead_id')
                            ->take(2)
                            ->toArray();



                        /**
                         *  AutoBid – Not Secured (less than 5 bids)
                         */
                        $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id, null, null, null, 'Autobid');

                        $autoBidLeads = $baseQuery
                            ->where('created_at', '>=', $since24Hours)
                            ->whereNotIn('id', $alreadyEmailedLeadIds)
                            ->whereNotIn('id', $slotFullLeadIds)
                            ->orderByDesc('id')
                            ->limit(2)
                            ->get();

                        $autoBidFiltered = $leadPref->leadsAccordingTOSellerPref(
                            $seller->id,
                            $autoBidLeads
                        );

                        foreach ($autoBidFiltered as $lead) {
                            $reasons = [];

                            // Credit check
                            if ($lead->credit_score > $seller->total_credit) {
                                $reasons[] = 'low credits';
                            }

                            // Auto-bid disabled check
                            if (
                                optional($seller->details)->autobid_pause == 0 ||
                                optional($seller->details)->is_autobid == 1
                            ) {
                                $reasons[] = 'auto-bid disabled';
                            }

                            // Only push if any reason exists
                            if (count($reasons) > 0) {
                                $sections['credit_not_enough'][] = $lead->id;
                            }
                        }

                        // Nothing to send
                        if (empty($sections['missed_secured_lastchance']) && empty($sections['credit_enough']) && empty($sections['credit_not_enough'])) {
                            continue;
                        }

                        /**
                         * Autobid status
                         */
                        $details = optional($seller->details);
                        $autobidStatus = 0;

                        if (($details->autobid_pause == 0 && $details->is_autobid == 0) || ($details->autobid_pause == 1 && $details->is_autobid == 1)) {
                            $autobidStatus = 1;
                        }

                        // Send unified email
                        ZohoEmails::sendYourDailyLeadsReport(
                            $seller->id,
                            $sections,
                            $autobidStatus,
                            $seller->total_credit
                        );

                        $totalSent++;
                    } catch (\Throwable $e) {
                        Log::error('Unified Lead Digest failed', [
                            'user_id' => $seller->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }
            });

        unset($leadPref);

        return [
            'status'    => 'success',
            'emails'    => $totalSent,
            'timestamp' => now()->toDateTimeString(),
        ];
    }




    public function updateLeadRequestExpairedStatus()
    {

        $expiredCount = 0;

        $closeLeadsAfterDays = CustomHelper::setting_value("close_leads_after_days", 14);
        $leadSlotCount = CustomHelper::setting_value('lead_slot_count', 5);

        $slotFullLeadIds = DB::table('recommended_leads')
            ->select('lead_id')
            ->groupBy('lead_id')
            ->havingRaw('COUNT(*) >= ?', [$leadSlotCount])
            ->pluck('lead_id')
            ->toArray();

        $expiredDate = Carbon::now()->subDays($closeLeadsAfterDays);

        $leads = LeadRequest::whereHas('customer', function ($query) {
            $query->where('form_status', 1);
        })
            ->whereNotIn('status', ['hired', 'expired'])
            ->where(function ($query) use ($expiredDate, $slotFullLeadIds) {
                $query->where('created_at', '<=', $expiredDate);

                if (!empty($slotFullLeadIds)) {
                    $query->orWhereIn('id', $slotFullLeadIds);
                }
            })           
          
            ->get();

        if ($leads->isEmpty()) {
            return "No leads found to expire";
        }

        foreach ($leads as $lead) {

            $lead->update([
                'status' => 'expired'
            ]);

            $expiredCount++;

            $requestLeadId = $lead->id;

            if (!empty($requestLeadId)) {
                Log::info('Running Zoho status update in background', ['lead_id' => $requestLeadId]);
                CustomHelper::runInBackground(function () use ($requestLeadId) {
                    app(ZohoQuoteRequest::class)->updateZohoQuoteStatus($requestLeadId);
                });
            }
        }

        Log::info('Lead expire process completed', ['expired_count' => $expiredCount]);
    }
}
