<?php

namespace App\Http\Controllers\Api\Cron;

use App\Helpers\Zoho\ZohoEmails;
use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\LeadRequest;
use App\Models\RecommendedLead;
use App\Models\User;
use App\Models\UserServiceLocation;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CronController extends Controller
{
    public function onceDayBased()
    {
        //$newLead = $this->onceADayOne();
        //$newLeadBidEnough = $this->onceADayTwo();
        $newLeadRequestReply = $this->onceADayThree();
        // $newLeadBidNotEnough = $this->onceADayFive();

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                //'new_lead_request_autobid_off' => $newLead,
                //'new_lead_bid_enough' => $newLeadBidEnough,
                'new_lead_request_reply' => $newLeadRequestReply,
                //'new_lead_bid_not_enough' => $newLeadBidNotEnough
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);


    }

     public function onceDayBidAfterDays()
    {
        $newLeadAfterdays = $this->onceADayFour();
        $newLeadAfterFewdays = $this->onceADaySix();

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'new_lead_after_days' => $newLeadAfterdays,
                'new_lead_after_few_days' => $newLeadAfterFewdays
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);


    }


    public function perMinuteBased()
    {

        $incompleteReg = $this->perMinuteBasedOne();

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'incomplete_reg_sent' => $incompleteReg
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function hourlyBased()
    {
        $encouragement = $this->hourlyBasedOne();
        $newLeadAfterTime = $this->hourlyBasedTwo();
        $newLeadAfterTimeNationWide = $this->hourlyBasedThree();

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'encouragement_sent' => $encouragement,
                'new_lead_after_time' => $newLeadAfterTime,
                'new_lead_after_time_nationwide' => $newLeadAfterTimeNationWide,
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }



    public function hourlyBasedOne() //sendEncouragementEmail
    {

        $sentCount = 0;
        $users = User::whereNotNull('zoho_record_id')
            ->whereHas('details', function ($q) {
                $q->where('is_autobid', 0);
            })
            ->with(['details', 'emailLogs' => function ($q) {
                $q->where('setting_name', 'Send Autobid Encouragement Email')
                    ->latest();
            }])
            ->get();

        foreach ($users as $user) {
            $latestLog = $user->emailLogs->first();

            if ($latestLog) {
                $hoursSinceLastEmail = Carbon::parse($latestLog->created_at)->diffInHours(now());

                if ($hoursSinceLastEmail >= 12) {
                    ZohoEmails::sendEncouragementEmail($user->id);
                    $sentCount++;
                }
            } else {

                ZohoEmails::sendEncouragementEmail($user->id);
                $sentCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "$sentCount encouragement email(s) sent.",
            'timestamp' => now()->toDateTimeString()
        ]);
    }
    public function perMinuteBasedOne()  // sendIncompleteRegEmail
    {
        $sentCount = 0;

        $users = User::whereNotNull('zoho_record_id')
            ->whereNull('form_status')
            ->with(['emailLogs' => function ($q) {
                $q->where('setting_name', 'Send Incomplete Registration Email')->latest();
            }])
            ->get();

        foreach ($users as $user) {
            $log = $user->emailLogs->first();

            // If no log entry exists
            if (!$log) {
                $minutesSinceUserCreated = Carbon::parse($user->created_at)->diffInMinutes(now());

                if ($minutesSinceUserCreated >= 15) {
                    ZohoEmails::sendIncompleteRegistrationEmail($user->id);
                    $sentCount++;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "$sentCount incomplete registration email(s) sent.",
            'timestamp' => now()->toDateTimeString()
        ]);
    }


    public function onceADayOne()  //sendNewLeadRequestAutoBidOff
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            ->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
            ->where('recommended_leads.purchase_type', 'Autobid')
            ->where('form_status', 1)
            ->where('user_type', 1)
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 1)
                     ->orWhere('is_autobid', 0);
            })
            ->select('id')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {

                foreach ($sellersChunk as $seller) {

                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id)
                        ->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();
                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    foreach ($filteredLeads as $lead) {
                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->whereDate('created_at', Carbon::today())
                            ->where('setting_name', 'Send New Lead Request Email')
                            ->exists();


                        if (!$alreadySent) {
                            ZohoEmails::sendLeadRequestEmail($seller->id, $lead->id);
                            $totalUnsentLeadEmails++;
                        }
                    }
                }
            });



        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function onceADayTwo()  //sendLeadEmailBidEnough
    {

        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            ->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
            ->where('recommended_leads.purchase_type', 'Autobid')
            ->where('form_status', 1)
            ->where('total_credit', '>', 0)
            ->where('user_type', 1)
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 0)
                    ->where('is_autobid', 1);
            })
            ->select('users.id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {
                foreach ($sellersChunk as $seller) {



                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id)
                       ->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();

                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    $finalLeads = $filteredLeads->filter(function ($lead) use ($seller) {
                        return $lead->credit_score <= $seller->total_credit;
                    });



                    foreach ($finalLeads as $lead) {

                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->whereDate('created_at', Carbon::today())
                            ->where('setting_name', 'New Lead - Auto Bid Enabled (With Credits)')
                            ->exists();


                        if (!$alreadySent) {
                            ZohoEmails::sendLeadEmailBidEnough($seller->id, $lead->id);
                            $totalUnsentLeadEmails++;
                        }

                    }
                }
            });

        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function onceADayFive() //sendLeadEmailBidNotEnough
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            //->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
            //->where('recommended_leads.purchase_type', 'Autobid')
            ->where('id',4)
            ->where('form_status', 1)
            ->where('user_type', 1)
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 0)
                    ->where('is_autobid', 1);
            })
            ->select('id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {

                foreach ($sellersChunk as $seller) {

                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id)
                        ->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();
                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    $finalLeads = $filteredLeads->filter(function ($lead) use ($seller) {
                        return $lead->credit_score > $seller->total_credit;
                    });



                    foreach ($finalLeads as $lead) {
                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->whereDate('created_at', Carbon::today())
                            ->where('setting_name', 'New Lead- Auto Bid Enabled (Without  Enough Credits)')
                            ->exists();


                        if (!$alreadySent) {
                            ZohoEmails::sendLeadEmailBidNotEnough($seller->id, $lead->id);
                            $totalUnsentLeadEmails++;
                        }
                    }
                }
            });

        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function onceADayThree() //sendLeadRequestReply
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            ->where('form_status', 1)
            ->where('user_type', 1)
            ->join('recommended_leads', 'users.id', '=', 'recommended_leads.seller_id')
            ->where('recommended_leads.purchase_type', 'Request Reply')
            ->select('users.id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {
                foreach ($sellersChunk as $seller) {

                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id)
                        ->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();
                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);



                    foreach ($filteredLeads as $lead) {
                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->whereDate('created_at', Carbon::today())
                            ->where('setting_name', 'New Lead - Request Reply')
                            ->exists();


                        if (!$alreadySent) {
                            ZohoEmails::sendLeadRequestReply($seller->id, $lead->id);
                            $totalUnsentLeadEmails++;
                        }
                    }
                }
            });

        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function onceADayFour() //sendLeadsAfter7Days
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();


        $sellerLeadSummary = [];

        User::whereNotNull('zoho_record_id')
            ->where('form_status', 1)
            ->where('user_type', 1)

            ->select('users.id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use (&$sellerLeadSummary) {
                foreach ($sellersChunk as $seller) {
                    $serviceLocations = UserServiceLocation::where('user_id', $seller->id)->get();
                    $groupedLeadStats = [];
                    $nationwideLeadIds = [];
                    foreach ($serviceLocations as $location) {
                        $leadQuery = LeadRequest::with('category')
                            ->where('service_id', $location->service_id)
                            ->where('created_at', '<=', Carbon::now()->subDays(7));

                        if ($location->nation_wide != 1) {

                            $groupKey = $location->postcode;

                            $leadQuery->whereNotIn('id', $nationwideLeadIds);
                            $leadQuery->where('postcode', $location->postcode);
                        } else {
                            $groupKey = 'nationwide';
                        }

                        $leads = $leadQuery->get();

                        foreach ($leads as $lead) {

                            $alreadyRecommended = RecommendedLead::where('seller_id', $seller->id)
                                ->where('lead_id', $lead->id)
                                ->exists();

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
                    $sellerTotalLeadCredit += $credit_sum;
                    $sellerTotalLeadCount += $count;
                    $sellerLeadData[] = array_merge($leadData, [
                        'area' => $area,
                        'service_id' => $serviceId,
                    ]);
                }
            }

            if (!empty($sellerLeadData)) {
                $emailPayload = [
                    'total_lead_count' => $sellerTotalLeadCount,
                    'total_credit_sum' => $sellerTotalLeadCredit,
                    'lead_data' => $sellerLeadData
                ];
                $settingValue = 'Send New Lead Request After 7 Days';
                $alreadySent = EmailLog::where('user_id', $sellerId)
                            ->whereDate('created_at', Carbon::today())
                            ->where('setting_name', 'Send New Lead Request After 7 Days')
                            ->exists();


                if (!$alreadySent) {
                    ZohoEmails::sendLeadsAfterDays($sellerId, $emailPayload, $settingValue);
                }
            }
        }





        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function onceADaySix() //sendLeadsAfter5Days
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        $sellerLeadSummary = [];

            User::leftJoin('user_card_details', 'users.id', '=', 'user_card_details.user_id')
            ->where('users.form_status', 1)
            ->where('users.user_type', 1)
            ->whereNotNull('users.zoho_record_id')
            ->where(function ($query) {
                $query->where(function ($q) {
                        $q->whereNull('user_card_details.id')
                        ->where('users.created_at', '<=', Carbon::now()->subDays(5));
                    })
                    ->orWhere('users.total_credit', '<', 10);
            })
            ->where(function ($query) {
                $query->whereNull('user_card_details.id') // no card details
                    ->orWhere('users.total_credit', '<', 10); // or low credit
            })
            ->select('users.id', 'users.total_credit')
            ->chunk(1000, function ($sellersChunk) use (&$sellerLeadSummary) {
                foreach ($sellersChunk as $seller) {
                    $serviceLocations = UserServiceLocation::where('user_id', $seller->id)->get();
                    $groupedLeadStats = [];
                    $nationwideLeadIds = [];
                    foreach ($serviceLocations as $location) {
                        $leadQuery = LeadRequest::with('category')
                            ->where('service_id', $location->service_id)
                            ->where('created_at', '<=', Carbon::now()->subDays(7));

                        if ($location->nation_wide != 1) {

                            $groupKey = $location->postcode;

                            $leadQuery->whereNotIn('id', $nationwideLeadIds);
                            $leadQuery->where('postcode', $location->postcode);
                        } else {
                            $groupKey = 'nationwide';
                        }

                        $leads = $leadQuery->get();

                        foreach ($leads as $lead) {

                            $alreadyRecommended = RecommendedLead::where('seller_id', $seller->id)
                                ->where('lead_id', $lead->id)
                                ->exists();

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
                    $sellerTotalLeadCredit += $credit_sum;
                    $sellerTotalLeadCount += $count;
                    $sellerLeadData[] = array_merge($leadData, [
                        'area' => $area,
                        'service_id' => $serviceId,
                    ]);
                }
            }

            if (!empty($sellerLeadData)) {
                $emailPayload = [
                    'total_lead_count' => $sellerTotalLeadCount,
                    'total_credit_sum' => $sellerTotalLeadCredit,
                    'lead_data' => $sellerLeadData,
                    'credit_purchase' => 1
                ];
                $settingValue = 'Send New Purchase Request After 5 Days';
                $alreadySent = EmailLog::where('user_id', $sellerId)
                            ->whereDate('created_at', Carbon::today())
                            ->where('setting_name', 'Send New Lead Request After 5 Days')
                            ->exists();


                if (!$alreadySent) {
                    ZohoEmails::sendLeadsAfterDays($sellerId, $emailPayload, $settingValue);
                }
            }
        }
        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function hourlyBasedTwo()
    {
        $leadPref = new LeadService();
        $totalUnsentLeadEmails = 0;
        $leads = LeadRequest::where('created_at', '<', Carbon::now()->subHours(48))->get();

        if ($leads->isEmpty()) {
            return $this->sendError(__('No leads found older than 48 hours'), 404);
        }



        foreach ($leads as $lead) {

            $result = $leadPref->getAllSellers($lead, null, 2);

            if (isset($result['response']['sellers'])) {

                foreach ($result['response']['sellers'] as $seller) {

                    $alreadySent = EmailLog::where('user_id', $seller->id)
                        ->where('lead_id', $lead->id)
                        ->where('setting_name', 'Send New Lead Request After 48hrs Email ')
                        ->whereIn('step', [1, 2, 3])
                        ->exists();


                    if (!$alreadySent) {
                        ZohoEmails::sendLeadsAfterTime($seller->id, $lead->id);
                        $totalUnsentLeadEmails++;
                    }
                }
            }
        }

        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function hourlyBasedThree()
    {
        $leadPref = new LeadService();
        $totalUnsentLeadEmails = 0;
        $leads = LeadRequest::where('created_at', '<', Carbon::now()->subHours(48))->get();

        if ($leads->isEmpty()) {
            return $this->sendError(__('No leads found older than 48 hours'), 404);
        }



        foreach ($leads as $lead) {

            $result = $leadPref->getAllSellersNationList($lead, null, 1);

            if (isset($result['response']['sellers'])) {

                foreach ($result['response']['sellers'] as $seller) {

                    $hasStep1 = EmailLog::where('user_id', $seller->id)
                        ->where('lead_id', $lead->id)
                        ->where('setting_name', 'Send New Lead Request After 48hrs Email ')
                        ->where('step', 1)
                        ->exists();

                    $hasStep2or3 = EmailLog::where('user_id', $seller->id)
                        ->where('lead_id', $lead->id)
                        ->where('setting_name', 'Send New Lead Request After 48hrs Email ')
                        ->where('created_at', '>=', Carbon::now()->subHours(12))
                        ->whereIn('step', [2, 3])
                        ->exists();

                    if ($hasStep1 && !$hasStep2or3) {
                        ZohoEmails::sendLeadsAfterTimeNationWide($seller->id, $lead->id);
                        $totalUnsentLeadEmails++;
                    }
                }
            }
        }

        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

}
