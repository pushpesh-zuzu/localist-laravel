<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Zoho\ZohoEmails;
use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\User;
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

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'encouragement_sent' => $encouragement,
                'incomplete_reg_sent' => $incompleteReg,
                'new_lead_request' => $newLead,
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
    // public function sendNewLeadRequestAutoBidOff()
    // {
    //     $totalUnsentLeadEmails = 0;
    //     $leadPref = new LeadPreferenceController();

    //     User::whereNotNull('zoho_record_id')
    //         ->where('form_status', 1)
    //         ->where('user_type', 1)
    //         ->whereHas('details', function ($query) {
    //                 $query->where('autobid_pause', 1);
    //         })
    //         ->select('id')
    //         ->chunk(1000, function ($sellersChunk) use ($leadPref, &$totalUnsentLeadEmails) {
    //             foreach ($sellersChunk as $seller) {
    //                 $baseQuery = $leadPref->getBaseQuery($seller->id)
    //                             ->whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()]);
    //                 $allLeads = $baseQuery->orderBy('id', 'desc')->get();
    //                 $filteredLeads = $leadPref->leadsAccordingTOSellerPref($seller->id, $allLeads);

    //                 foreach ($filteredLeads as $lead) {
    //                     $alreadySent = EmailLog::where('user_id', $seller->id)
    //                         ->where('lead_id', $lead->id)
    //                         ->where('setting_name', 'Send New Lead Request Email')
    //                         ->exists();
    //                     dd($alreadySent);

    //                     if (!$alreadySent) {

    //                         ZohoEmails::sendLeadRequestEmail($seller->id, $lead->id);
    //                     }
    //                 }
    //             }
    //         });
    //     dd('hi');
    //     unset($leadPref);
    //     return response()->json([
    //         'status' => 'success',
    //         'unsent_lead_emails' => $totalUnsentLeadEmails,
    //         'timestamp' => now()->toDateTimeString(),
    //     ]);
    // }

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
            ->where('total_credit','>',0)
            ->where('user_type', 1)
            ->whereHas('details', function ($query) {
                $query->where('autobid_pause', 0)
                      ->where('is_autobid', 1);
            })
            ->select('id','total_credit')
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
            ->select('id','total_credit')
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
}
