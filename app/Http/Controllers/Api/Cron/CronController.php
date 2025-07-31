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

class CronController extends Controller
{
    public function onHourlyBasis(Request $request, LeadService $leadService){
        
        $this->UnsoldLeadsStep1($request, $leadService);
        $this->unSoldLeadsStep2($request, $leadService);
        $this->unSoldLeadsStep3($request, $leadService);

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function cronAfter7Days()
    {
        $newLeadAfter7days = $this->sendLeadsAfter7Days();

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'new_lead_after_7_days' => $newLeadAfter7days
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function cronAfter5Days()
    {
        $newLeadAfter5days = $this->checkCreditAfter5Days();

        return response()->json([
            'status' => 'success',
            'message' => 'Zoho email cron ran successfully.',
            'details' => [
                'new_lead_after_5_days' => $newLeadAfter5days
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }



    public function sendLeadsAfter7Days()
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
                                ->where('created_at', '>=', Carbon::now()->subDays(7))
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
                $settingValue = 'No Lead Purchased In 7 Days';
                $alreadySent = EmailLog::where('user_id', $sellerId)
                    ->whereDate('created_at', Carbon::today())
                    ->where('setting_name', 'No Lead Purchased In 7 Days')
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

    public function checkCreditAfter5Days()
    {
        $totalUnsentLeadEmails = 0;
        $leadPref = new LeadService();

        $sellerLeadSummary = [];

        $latestPlanHistory = PlanHistory::select('user_id', DB::raw('MAX(created_at) as last_plan_date'))
            ->groupBy('user_id')
            ->toBase();

        User::leftJoinSub($latestPlanHistory, 'latest_plan', function ($join) {
            $join->on('users.id', '=', 'latest_plan.user_id');
        })
            ->where('users.form_status', 1)
            ->where('users.user_type', 1)
            ->where('users.id', 61)
            ->whereNotNull('users.zoho_record_id')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('latest_plan.last_plan_date', '<=', Carbon::now()->subDays(5));
                })
                    ->where('users.total_credit', '<', 10);
            })
            ->select('users.id', 'users.total_credit', 'latest_plan.last_plan_date')
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
        unset($leadPref);
        return response()->json([
            'status' => 'success',
            'unsent_lead_emails' => $totalUnsentLeadEmails,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }






##################################################################################################################################################
################################################       CRON FUNCTIONS       ######################################################################
##################################################################################################################################################



    private function UnsoldLeadsStep1(Request $request, LeadService $leadService){
        $totalUnsentLeadEmails = 0;
        $leads = LeadRequest::where('created_at', '<', Carbon::now()->subHours(48))->get();

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

    private function unSoldLeadsStep2(Request $request, LeadService $leadService){
        $totalUnsentLeadEmails = 0;
        $leads = LeadRequest::where('created_at', '<', Carbon::now()->subHours(84))->get();

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


    private function unSoldLeadsStep3(Request $request, LeadService $leadService){
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
            ->where('status', 'new')
            ->get();
        $discountPercent = CustomHelper::setting_value('discount_percent_of_unsold_leads',20);

        foreach($leads as $l){
            $newCredit = floor($l->credit_score - (($discountPercent/100) * $l->credit_score));
            $l->credit_score = $newCredit;

            $dataUC['credit_score'] = $newCredit;
            LeadRequest::where('id', $l->id)->update($dataUC);

            $localSellers = $leadService->getAllSellers($l, null, 2)['response']['sellers'];
            $nationSellers = $leadService->getAllSellersNationList($l, null, 1)['response']['sellers'];
            $allSellers = $localSellers->merge($nationSellers)
                ->unique('user_id')
                ->values();
            
            foreach($allSellers as $seller){
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
                
                
                if($hasStep1 && $hasStep2 && !$hasStep3){
                    $dataU3['userId'] = $seller->id;
                    $dataU3['leadId'] = $l->id;
                    $dataU3['setting_name'] = 'Unsold Leads (Discount)';
                    $dataU3['subject'] = 'Exclusive Offer: 20% Discount on Lead Credits – Act Now!';
                    $dataU3['step'] = 3;
                    ZohoEmails::unsoldLeadEmail($dataU3);
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


}
