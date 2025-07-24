<?php

namespace App\Http\Controllers\Api;

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

class ZohoController extends Controller
{
    public function runZohoEmailCron()
    {
        $encouragement = $this->sendEncouragementEmail();
        $incompleteReg = $this->sendIncompleteRegEmail();
        $newLead = $this->sendNewLeadRequestAutoBidOff();
        $newLeadBidEnough = $this->sendLeadEmailBidEnough();
        $newLeadRequestReply = $this->sendLeadRequestReply();
        $newLeadAfterTime = $this->sendLeadsAfterTime();
        $newLeadAfterTimeNationWide = $this->sendLeadsAfterTimeNationWide();
        $newLeadAfterdays = $this->sendLeadsAfterDays();

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'encouragement_sent' => $encouragement,
                'incomplete_reg_sent' => $incompleteReg,
                'new_lead_request_autobid_off' => $newLead,
                'new_lead_bid_enough' => $newLeadBidEnough,
                'new_lead_request_reply' => $newLeadRequestReply,
                'new_lead_after_time' => $newLeadAfterTime,
                'new_lead_after_time_nationwide' => $newLeadAfterTimeNationWide,
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
    public function sendIncompleteRegEmail()
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


    public function sendNewLeadRequestAutoBidOff()
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            ->where('form_status', 1)
            ->where('user_type', 1)
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 1);
            })
            ->select('id')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {

                foreach ($sellersChunk as $seller) {

                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id)
                        ->whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();
                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    foreach ($filteredLeads as $lead) {
                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
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

    public function sendLeadEmailBidEnough()
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
            ->where('form_status', 1)
            ->where('total_credit', '>', 0)
            ->where('user_type', 1)
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 0)
                    ->where('is_autobid', 1);
            })
            ->select('id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {

                foreach ($sellersChunk as $seller) {

                    $baseQuery = $leadPref->getSellerLeadsBaseQuery($seller->id)
                        ->whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();
                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    $finalLeads = $filteredLeads->filter(function ($lead) use ($seller) {
                        return $lead->credit_score <= $seller->total_credit;
                    });



                    foreach ($finalLeads as $lead) {
                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'Send New Lead Request Email')
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

    public function sendLeadEmailBidNotEnough()
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        User::whereNotNull('zoho_record_id')
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
                        ->whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();
                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

                    $finalLeads = $filteredLeads->filter(function ($lead) use ($seller) {
                        return $lead->credit_score > $seller->total_credit;
                    });



                    foreach ($finalLeads as $lead) {
                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'Send New Lead Request Email')
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

    public function sendLeadRequestReply()
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
                        ->whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()]);

                    $allLeads = $baseQuery->orderBy('id', 'desc')->get();
                    $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);



                    foreach ($filteredLeads as $lead) {
                        $alreadySent = EmailLog::where('user_id', $seller->id)
                            ->where('lead_id', $lead->id)
                            ->where('setting_name', 'Send New Lead Request Email')
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

    public function sendLeadsAfterTime()
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

    public function sendLeadsAfterTimeNationWide()
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
    public function sendLeadsAfterDays()
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();


        $sellerLeadSummary = [];

        User::whereNotNull('zoho_record_id')
            ->where('form_status', 1)
            ->where('user_type', 1)
            ->where('id', 61)
            ->select('users.id', 'total_credit')
            ->chunk(1000, function ($sellersChunk) use (&$sellerLeadSummary) {
                foreach ($sellersChunk as $seller) {

                    // Get all service locations for this seller
                    $serviceLocations = UserServiceLocation::where('user_id', $seller->id)->get();

                    // Prepare group result: [service_id][postcode/nationwide] = { count, credit_sum }
                    $groupedLeadStats = [];

                    foreach ($serviceLocations as $location) {

                        // Base query for this location's service
                        $leadQuery = LeadRequest::with('category')
                            ->where('service_id', $location->service_id)
                            //->whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()]);
                            ->where('created_at', '<=', Carbon::now()->subDays(7));

                        if ($location->nation_wide != 1) {
                            $leadQuery->where('postcode', $location->postcode);
                            $groupKey = $location->postcode;
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
                            $groupedLeadStats[$serviceId][$groupKey]['count'] =
                                ($groupedLeadStats[$serviceId][$groupKey]['count'] ?? 0) + 1;

                            $groupedLeadStats[$serviceId][$groupKey]['credit_sum'] =
                                ($groupedLeadStats[$serviceId][$groupKey]['credit_sum'] ?? 0) + $lead->credit_score;
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
            $sellerLeadData = []; // collect all leadData per seller

            foreach ($leadStats as $serviceId => $locations) {
                foreach ($locations as $area => $leadData) {
                    $count = $leadData['count'] ?? 0;
                    if ($count === 0) {
                        continue;
                    }

                    $sellerTotalLeadCount += $count;

                    // Collect each location data
                    $sellerLeadData[] = array_merge($leadData, [
                        'area' => $area,
                        'service_id' => $serviceId,
                    ]);
                }
            }

            if (!empty($sellerLeadData)) {
                // Prepare final payload
                $emailPayload = [
                    'total_lead_count' => $sellerTotalLeadCount,
                    'lead_data' => $sellerLeadData
                ];

                // Send a single email to the seller with all their data
                ZohoEmails::sendLeadsAfterDays($sellerId, $emailPayload);
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
