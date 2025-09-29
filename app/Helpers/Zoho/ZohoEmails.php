<?php

namespace App\Helpers\Zoho;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Illuminate\Support\Facades\Http;
use App\Helpers\CustomHelper;
use App\Models\AbandonedUser;
use App\Models\User;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Models\LeadRequest;
use App\Models\RecommendedLead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZohoEmails
{
    // protected string $accessToken;

    public static function sendWelcomeEmail($userId, $password)
    {
        $sendWelcomeEmail = EmailSetting::where('setting_name', 'Send Welcome Email')->value('setting_value');
        if ($sendWelcomeEmail) {
            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::with(['services.category', 'services.locations'])->where('id', $userId)->first();
                if (!empty($user)) {
                    $services = [];
                    foreach ($user->services as $s) {
                        $sl = $s->category->name . ' - ';
                        foreach ($s->locations as $index => $l) {
                            if ($index > 0) {
                                $sl .= ', ';
                            }
                            $sl .= $l->miles . ' miles from ' . $l->postcode;
                        }
                        array_push($services, $sl);
                    }
                    $htmlView = view('emails.lead_buyers.registration.lead_buyer_registration',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $password,
                        'jobs' => rand(1, 50),
                        'services' => $services
                    ])->render();
                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Welcome to Localists';

                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'sendWelcomeEmail',
                        'ipaddress' => request()->ip(),
                        'created_at' => now(),
                    ]);

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);

                    $rel = self::getZohoMailResponse($response);
                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send Welcome Email';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    public static function sendWelcomeEmailQuoteCustomer($userId, $password, $phoneOtp)
    {
        $sendWelcomeEmail = EmailSetting::where('setting_name', 'Send Welcome Email For Customer')->value('setting_value');

        if ($sendWelcomeEmail) {
            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();
                if (!empty($user)) {


                    $htmlView = view('emails.customers.registration.quote_customer_registration',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $password,
                        'phone_otp' => $phoneOtp

                    ])->render();
                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_QUOTE_CUSTOMERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Welcome to Localists';

                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'sendWelcomeEmailQuoteCustomer',
                        'ipaddress' => request()->ip(),
                        'created_at' => now(),
                    ]);

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);

                    $rel = self::getZohoMailResponse($response);
                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send Welcome Email For Customer';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    public static function sendEncouragementEmail($userId)
    {

        $sendEncouragementEmail = EmailSetting::where('setting_name', 'Send Autobid Encouragement Email')->value('setting_value');

        if ($sendEncouragementEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                if (!empty($user)) {


                    $htmlView = view('emails.lead_buyers.registration.lead_buyer_encouragement',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                     DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'sendEncouragementEmail',
                        'ipaddress' => request()->ip(),
                        'created_at' => now(),
                    ]);
                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Boost Your Sales with Auto Buy !';

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);


                    $rel = self::getZohoMailResponse($response);
                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send Autobid Encouragement Email';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    public static function sendAbandonedEncouragementEmail($userId)
    {

        $sendEncouragementEmail = EmailSetting::where('setting_name', 'Send Abandoned Encouragement Email')->value('setting_value');

        if ($sendEncouragementEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoAbandonedQuoteCustomerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = AbandonedUser::where('id', $userId)->first();

                if (!empty($user)) {


                    $htmlView = view('emails.customers.registration.customer_encouragement',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_QUOTE_CUSTOMERS_API_URL, $zohoId);

                     DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'sendAbandonedEncouragementEmail',
                        'ipaddress' => request()->ip(),
                        'created_at' => now(),
                    ]);
                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Complete your registration – we’ve saved your spot!';

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);

                    $rel = self::getZohoMailResponse($response);
                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send Abandoned Encouragement Email';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }


    public static function sendIncompleteRegistrationEmail($userId)
    {

        $sendIncompleteRegEmail = EmailSetting::where('setting_name', 'Send Incomplete Registration Email')->value('setting_value');

        if ($sendIncompleteRegEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoAbandonLeadBuyerId($accessToken, $userId);
Log::info('Incomplete registrtion p1',[
            'message'=>$zohoId
        ]);
            if (!empty($zohoId)) {
                $user = AbandonedUser::where('id', $userId)->first();

                if (!empty($user)) {

Log::info('Incomplete registrtion p2',[
            'message'=>$user
        ]);
                    $htmlView = view('emails.lead_buyers.registration.lead_buyer_incomplete_registration',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Complete your registration – We’ve saved your spot!';

                     DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'sendIncompleteRegistrationEmail',
                        'ipaddress' => request()->ip(),
                        'created_at' => now(),
                    ]);

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);

                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send Incomplete Registration Email';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    // public static function sendLeadNotBid($userId, $leadId)
    // {


    //     $sendLeadRequestEmail = EmailSetting::where('setting_name', 'New Lead-Auto Bid Disable (Check Credit)')->value('setting_value');

    //     if ($sendLeadRequestEmail) {
    //         $accessToken = ZohoHelper::getAccessToken();

    //         $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

    //         if (!empty($zohoId)) {
    //             $user = User::where('id', $userId)->first();

    //             if (!empty($user)) {


    //                 $lead = LeadRequest::with([
    //                     'category',
    //                     'customer'
    //                 ])
    //                     ->where('id', $leadId)
    //                     ->first();

    //                 $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
    //                     ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
    //                     ->map(fn($item) => [
    //                         'question' => $item['ques'],
    //                         'answer' => implode(', ', $item['ans'])
    //                     ])
    //                     ->toArray();


    //                 $htmlView = view('emails.lead_buyers.leads.lead_buyer_request',  [
    //                     'baseUrl' => config('app.react_base_url'),
    //                     'name' => $user->name,
    //                     'lead_name' => $lead->customer->name ?? '',
    //                     'postcode' => $lead->postcode ?? '',
    //                     'masked_phone' => $lead->customer?->phone ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2) : 'N/A',
    //                     'masked_email' => $lead->customer?->email ? (function ($email) {
    //                         [$name, $domain] = explode('@', $email);
    //                         $visible = substr($name, 0, 2);
    //                         $masked = str_repeat('*', max(strlen($name) - 2, 0));
    //                         return $visible . $masked . '@' . $domain;
    //                     })($lead->customer->email) : 'N/A',

    //                     'service_name' => $lead->category->name ?? '',
    //                     'has_additional_details' => $lead->has_additional_details ?? '',
    //                     'credit_score' => $lead->credit_score ?? '',
    //                     'is_frequent_user' => $lead->is_frequent_user ?? '',
    //                     'is_urgent' => $lead->is_urgent ?? '',
    //                     'is_high_hiring' => $lead->is_high_hiring ?? '',
    //                     'phone_verified' => $lead->is_phone_verified ?? '',
    //                     'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
    //                     'questionsAndAnswers' => $questionsAndAnswers,
    //                 ])->render();

    //                 $htmlContent = (new CssToInlineStyles())->convert($htmlView);
    //                 $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

    //                 $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
    //                 $toEmail = $user->email;
    //                 $subject = 'Don’t Let This Lead Slip – Enable Auto Bid Today ';

    //                  DB::table('zoho_logs')->insert([
    //                     'url' => $url,
    //                     'function_name' => 'sendLeadNotBid',
    //                     'ipaddress' => request()->ip(),
    //                     'created_at' => now(),
    //                 ]);

    //                 $response = Http::withToken($accessToken)
    //                     ->post($url, [
    //                         'data' => [
    //                             [
    //                                 'from' => [
    //                                     'email' => $fromEmail,
    //                                     'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
    //                                 ],
    //                                 'to' => [
    //                                     [
    //                                         'email' => $toEmail
    //                                     ]
    //                                 ],
    //                                 'subject' => $subject,
    //                                 'content' => $htmlContent,
    //                                 'mail_format' => 'html',
    //                                 'org_email' => true
    //                             ]
    //                         ]
    //                     ]);

    //                 $rel = self::getZohoMailResponse($response);
    //                 $dataE['user_id'] = $user->id;
    //                 $dataE['from_email'] = $fromEmail;
    //                 $dataE['lead_id'] = $leadId;
    //                 $dataE['to_email'] = $toEmail;
    //                 $dataE['message_id'] = $rel['message_id'];
    //                 $dataE['subject'] = $subject;
    //                 $dataE['setting_name'] = 'New Lead-Auto Bid Disable (Check Credit)';
    //                 $dataE['content'] = $htmlContent;
    //                 $dataE['zoho_url'] = $url;
    //                 $dataE['response'] = json_encode($rel);
    //                 EmailLog::insertGetId($dataE);
    //             }
    //         }
    //     }
    // }

    public static function sendLeadNotBidMultiple($userId, $leads)
    {
        if (empty($leads)) return;

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'New Lead-Auto Bid Disable (Check Credit)')->value('setting_value');

        if (!$sendLeadRequestEmail) return;

        $accessToken = ZohoHelper::getAccessToken();
        $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

        if (empty($zohoId)) return;

        $user = User::find($userId);
        if (!$user) return;

        $formattedLeads = [];

        foreach ($leads as $lead) {
            $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
                ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                ->map(fn($item) => [
                    'question' => $item['ques'],
                    'answer' => implode(', ', $item['ans']),
                ])
                ->toArray();

            $formattedLeads[] = [
                'lead_name' => $lead->customer->name ?? '',
                'postcode' => $lead->postcode ?? '',
                'masked_phone' => $lead->customer?->phone
                    ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2)
                    : 'N/A',
                'masked_email' => $lead->customer?->email
                    ? (function ($email) {
                        [$name, $domain] = explode('@', $email);
                        $visible = substr($name, 0, 2);
                        $masked = str_repeat('*', max(strlen($name) - 2, 0));
                        return $visible . $masked . '@' . $domain;
                    })($lead->customer->email)
                    : 'N/A',
                'service_name' => $lead->category->name ?? '',
                'has_additional_details' => $lead->has_additional_details ?? '',
                'credit_score' => $lead->credit_score ?? '',
                'is_frequent_user' => $lead->is_frequent_user ?? '',
                'is_urgent' => $lead->is_urgent ?? '',
                'is_high_hiring' => $lead->is_high_hiring ?? '',
                'phone_verified' => $lead->is_phone_verified ?? '',
                'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
                'questionsAndAnswers' => $questionsAndAnswers,
            ];
        }



        // Email view for multiple leads
        $htmlView = view('emails.lead_buyers.leads.lead_buyer_request', [
            'baseUrl' => config('app.react_base_url'),
            'name' => $user->name,
            'leadDetailsList' => $formattedLeads,
        ])->render();

        $htmlContent = (new CssToInlineStyles())->convert($htmlView);
        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
        $toEmail = $user->email;
        $subject = 'You Have New Leads – Enable Auto Bid to Save Time!';

        DB::table('zoho_logs')->insert([
            'url' => $url,
            'function_name' => 'sendLeadNotBidMultiple',
            'ipaddress' => request()->ip(),
            'created_at' => now(),
        ]);



        $response = Http::withToken($accessToken)
            ->post($url, [
                'data' => [[
                    'from' => [
                        'email' => $fromEmail,
                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                    ],
                    'to' => [[ 'email' => $toEmail ]],
                    'subject' => $subject,
                    'content' => $htmlContent,
                    'mail_format' => 'html',
                    'org_email' => true
                ]]
            ]);

        $rel = self::getZohoMailResponse($response);

        Log::info('Zoho Email for autobidoff', [
            'user_id' => $userId,
            'response' => $response->json(),
        ]);

        foreach ($leads as $lead) {
            EmailLog::create([
                'user_id' => $user->id,
                'from_email' => $fromEmail,
                'lead_id' => $lead->id,
                'to_email' => $toEmail,
                'message_id' => $rel['message_id'],
                'subject' => $subject,
                'setting_name' => 'New Lead-Auto Bid Disable (Check Credit)',
                'content' => $htmlContent,
                'zoho_url' => $url,
                'response' => json_encode($rel),
            ]);
        }
    }


    // public static function sendLeadEmailBidEnough($userId, $leadId)
    // {

    //     $sendLeadRequestEmail = EmailSetting::where('setting_name', 'New Lead - Auto Bid Enabled (With Credits)')->value('setting_value');

    //     if ($sendLeadRequestEmail) {
    //         $accessToken = ZohoHelper::getAccessToken();

    //         $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

    //         if (!empty($zohoId)) {
    //             $user = User::where('id', $userId)->first();

    //             if (!empty($user)) {


    //                 $lead = LeadRequest::with([
    //                     'category',
    //                     'customer'
    //                 ])
    //                     ->where('id', $leadId)
    //                     ->first();

    //                 $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
    //                     ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
    //                     ->map(fn($item) => [
    //                         'question' => $item['ques'],
    //                         'answer' => implode(', ', $item['ans'])
    //                     ])
    //                     ->toArray();


    //                 $htmlView = view('emails.lead_buyers.leads.lead_buyer_autobidenough',  [
    //                     'baseUrl' => config('app.react_base_url'),
    //                     'name' => $user->name,
    //                     'lead_name' => $lead->customer->name ?? '',
    //                     'postcode' => $lead->postcode ?? '',
    //                     'masked_phone' => $lead->customer?->phone ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2) : 'N/A',
    //                     'masked_email' => $lead->customer?->email ? (function ($email) {
    //                         [$name, $domain] = explode('@', $email);
    //                         $visible = substr($name, 0, 2);
    //                         $masked = str_repeat('*', max(strlen($name) - 2, 0));
    //                         return $visible . $masked . '@' . $domain;
    //                     })($lead->customer->email) : 'N/A',

    //                     'service_name' => $lead->category->name ?? '',
    //                     'has_additional_details' => $lead->has_additional_details ?? '',
    //                     'credit_score' => $lead->credit_score ?? '',
    //                     'is_frequent_user' => $lead->is_frequent_user ?? '',
    //                     'is_urgent' => $lead->is_urgent ?? '',
    //                     'is_high_hiring' => $lead->is_high_hiring ?? '',
    //                     'phone_verified' => $lead->is_phone_verified ?? '',
    //                     'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
    //                     'remaining_credit' => intval($user->total_credit - $lead->credit_score),
    //                     'questionsAndAnswers' => $questionsAndAnswers,
    //                 ])->render();

    //                 $htmlContent = (new CssToInlineStyles())->convert($htmlView);
    //                 $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

    //                 $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
    //                 $toEmail = $user->email;

    //                 $subject = 'New Lead Secured – Auto Bid Active & Contact Details Inside';

    //                  DB::table('zoho_logs')->insert([
    //                     'url' => $url,
    //                     'function_name' => 'sendLeadEmailBidEnough',
    //                     'ipaddress' => request()->ip(),
    //                     'created_at' => now(),
    //                 ]);

    //                 $response = Http::withToken($accessToken)
    //                     ->post($url, [
    //                         'data' => [
    //                             [
    //                                 'from' => [
    //                                     'email' => $fromEmail,
    //                                     'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
    //                                 ],
    //                                 'to' => [
    //                                     [
    //                                         'email' => $toEmail
    //                                     ]
    //                                 ],
    //                                 'subject' => $subject,
    //                                 'content' => $htmlContent,
    //                                 'mail_format' => 'html',
    //                                 'org_email' => true
    //                             ]
    //                         ]
    //                     ]);

    //                 $rel = self::getZohoMailResponse($response);

    //                 $dataE['user_id'] = $user->id;
    //                 $dataE['from_email'] = $fromEmail;
    //                 $dataE['lead_id'] = $leadId;
    //                 $dataE['to_email'] = $toEmail;
    //                 $dataE['message_id'] = $rel['message_id'];
    //                 $dataE['subject'] = $subject;
    //                 $dataE['setting_name'] = 'New Lead - Auto Bid Enabled (With Credits)';
    //                 $dataE['content'] = $htmlContent;
    //                 $dataE['zoho_url'] = $url;
    //                 $dataE['response'] = json_encode($rel);
    //                 EmailLog::insertGetId($dataE);
    //             }
    //         }
    //     }
    // }
    public static function sendLeadBidEnoughMultiple(array $sellerLeadMap)
    {
        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'New Lead - Auto Bid Enabled (With Credits)')->value('setting_value');

        if (!$sendLeadRequestEmail) {
            return;
        }

        $accessToken = ZohoHelper::getAccessToken();

        foreach ($sellerLeadMap as $userId => $leadIds) {
            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
            if (empty($zohoId)) continue;

            $user = User::where('id', $userId)->first();
            if (empty($user)) continue;

            $leadDetailsList = [];

            foreach ($leadIds as $leadId) {
                $lead = LeadRequest::with(['category', 'customer'])->find($leadId);
                if (!$lead) continue;

                $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
                    ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                    ->map(fn($item) => [
                        'question' => $item['ques'],
                        'answer' => implode(', ', $item['ans'])
                    ])
                    ->toArray();

                $leadDetailsList[] = [
                    'lead_id' => $lead->id,
                    'lead_name' => $lead->customer->name ?? '',
                    'postcode' => $lead->postcode ?? '',
                    'masked_phone' => $lead->customer?->phone ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2) : 'N/A',
                    'masked_email' => $lead->customer?->email ? (function ($email) {
                        [$name, $domain] = explode('@', $email);
                        $visible = substr($name, 0, 2);
                        $masked = str_repeat('*', max(strlen($name) - 2, 0));
                        return $visible . $masked . '@' . $domain;
                    })($lead->customer->email) : 'N/A',
                    'service_name' => $lead->category->name ?? '',
                    'has_additional_details' => $lead->has_additional_details ?? '',
                    'credit_score' => $lead->credit_score ?? '',
                    'is_frequent_user' => $lead->is_frequent_user ?? '',
                    'is_urgent' => $lead->is_urgent ?? '',
                    'is_high_hiring' => $lead->is_high_hiring ?? '',
                    'phone_verified' => $lead->is_phone_verified ?? '',
                    'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
                    'remaining_credit' => intval($user->total_credit - $lead->credit_score),
                    'questionsAndAnswers' => $questionsAndAnswers,
                ];
            }

            // Skip if no valid leads for this seller
            if (empty($leadDetailsList)) {
                continue;
            }

            $htmlView = view('emails.lead_buyers.leads.lead_buyer_autobidenough', [
                'baseUrl' => config('app.react_base_url'),
                'name' => $user->name,
                'leadDetailsList' => $leadDetailsList,
            ])->render();

            $htmlContent = (new CssToInlineStyles())->convert($htmlView);
            $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);
            $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
            $toEmail = $user->email;
            $subject = 'New Leads Secured – Auto Bid Active & Contact Details Inside';

            DB::table('zoho_logs')->insert([
                'url' => $url,
                'function_name' => 'sendLeadEmailBidEnoughMultiple',
                'ipaddress' => request()->ip(),
                'created_at' => now(),
            ]);

            $response = Http::withToken($accessToken)->post($url, [
                'data' => [
                    [
                        'from' => [
                            'email' => $fromEmail,
                            'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                        ],
                        'to' => [
                            ['email' => $toEmail]
                        ],
                        'subject' => $subject,
                        'content' => $htmlContent,
                        'mail_format' => 'html',
                        'org_email' => true
                    ]
                ]
            ]);

            $rel = self::getZohoMailResponse($response);

            // Log all lead IDs in separate records or as a single comma-separated string
            foreach ($leadIds as $leadId) {
                EmailLog::insert([
                    'user_id' => $user->id,
                    'from_email' => $fromEmail,
                    'lead_id' => $leadId,
                    'to_email' => $toEmail,
                    'message_id' => $rel['message_id'],
                    'subject' => $subject,
                    'setting_name' => 'New Lead - Auto Bid Enabled (With Credits)',
                    'content' => $htmlContent,
                    'zoho_url' => $url,
                    'response' => json_encode($rel),
                    'created_at' => now(),
                ]);
            }
        }
    }



    public static function sendGroupedLeadEmailBidNotEnough($userId, $leads)
    {

        if (empty($leads) || empty($userId)) {
            return;
        }

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'New Lead- Auto Bid Enabled (Without  Enough Credits)')->value('setting_value');
        if (!$sendLeadRequestEmail) {
            return;
        }

        $accessToken = ZohoHelper::getAccessToken();
        if (!$accessToken) {
            return;
        }

        $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
        if (empty($zohoId)) {
            return;
        }

        $user = User::find($userId);
        if (empty($user)) {
            return;
        }

        $leadViews = [];

        foreach ($leads as $leadId) {
            if (!$leadId) continue;
            $lead = LeadRequest::with(['category', 'customer'])->find($leadId);
            if (!$lead) continue;

            $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
                ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                ->map(fn($item) => [
                    'question' => $item['ques'],
                    'answer' => implode(', ', $item['ans'])
                ])
                ->toArray();

            $leadViews[] = [
                'lead_name' => $lead->customer->name ?? '',
                'postcode' => $lead->postcode ?? '',
                'masked_phone' => $lead->customer?->phone ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2) : 'N/A',
                'masked_email' => $lead->customer?->email ? (function ($email) {
                    [$name, $domain] = explode('@', $email);
                    $visible = substr($name, 0, 2);
                    $masked = str_repeat('*', max(strlen($name) - 2, 0));
                    return $visible . $masked . '@' . $domain;
                })($lead->customer->email) : 'N/A',
                'service_name' => $lead->category->name ?? '',
                'has_additional_details' => $lead->has_additional_details ?? '',
                'credit_score' => $lead->credit_score ?? '',
                'is_frequent_user' => $lead->is_frequent_user ?? '',
                'is_urgent' => $lead->is_urgent ?? '',
                'is_high_hiring' => $lead->is_high_hiring ?? '',
                'phone_verified' => $lead->is_phone_verified ?? '',
                'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
                'questionsAndAnswers' => $questionsAndAnswers,
            ];
        }

        if (empty($leadViews)) {
            return;
        }

        $htmlView = view('emails.lead_buyers.leads.lead_buyer_request', [
            'baseUrl' => config('app.react_base_url'),
            'name' => $user->name,
            'leadDetailsList' => $leadViews,
        ])->render();

        $htmlContent = (new CssToInlineStyles())->convert($htmlView);

        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);
        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
        $toEmail = $user->email;
        $subject = 'You Missed a New Lead – Not Enough Credits for Your New Leads';

        DB::table('zoho_logs')->insert([
            'url' => $url,
            'function_name' => 'sendGroupedLeadEmailBidNotEnough',
            'ipaddress' => request()->ip(),
            'created_at' => now(),
        ]);

        $response = Http::withToken($accessToken)->post($url, [
            'data' => [
                [
                    'from' => [
                        'email' => $fromEmail,
                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                    ],
                    'to' => [
                        ['email' => $toEmail]
                    ],
                    'subject' => $subject,
                    'content' => $htmlContent,
                    'mail_format' => 'html',
                    'org_email' => true
                ]
            ]
        ]);

        Log::info('htmlview', [
                            'content' => $response->json(),

                        ]);
        $rel = self::getZohoMailResponse($response);

        foreach ($leads as $leadId) {
            EmailLog::insertGetId([
                'user_id' => $user->id,
                'from_email' => $fromEmail,
                'lead_id' => $leadId, // multiple leads
                'to_email' => $toEmail,
                'message_id' => $rel['message_id'],
                'subject' => $subject,
                'setting_name' => 'New Lead- Auto Bid Enabled (Without  Enough Credits)',
                'content' => $htmlContent,
                'zoho_url' => $url,
                'response' => json_encode($rel),
            ]);
        }


    }


    public static function sendGroupedLeadDetails($userId, $leads)
    {
        if (empty($leads) || empty($userId)) {
            return;
        }

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Send Lead Details Email At Evening')->value('setting_value');
        if (!$sendLeadRequestEmail) {
            return;
        }

        $accessToken = ZohoHelper::getAccessToken();
        if (!$accessToken) {
            return;
        }

        $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
        if (empty($zohoId)) {
            return;
        }

        $user = User::find($userId);
        if (empty($user)) {
            return;
        }

        $leadViews = [];

        foreach ($leads as $leadId) {
            if (!$leadId) continue;
            $lead = LeadRequest::with(['category', 'customer'])->find($leadId);
            if (!$lead) continue;

            $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
                ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                ->map(fn($item) => [
                    'question' => $item['ques'],
                    'answer'   => implode(', ', $item['ans'])
                ])
                ->toArray();

            $leadViews[] = [
                'id'                  => $lead->id,
                'lead_name'           => $lead->customer->name ?? '',
                'postcode'            => $lead->postcode ?? '',
                'masked_phone'        => $lead->customer?->phone ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2) : 'N/A',
                'masked_email'        => $lead->customer?->email ? (function ($email) {
                    [$name, $domain] = explode('@', $email);
                    $visible = substr($name, 0, 2);
                    $masked  = str_repeat('*', max(strlen($name) - 2, 0));
                    return $visible . $masked . '@' . $domain;
                })($lead->customer->email) : 'N/A',
                'service_name'        => $lead->category->name ?? '',
                'has_additional_details' => $lead->has_additional_details ?? '',
                'credit_score'        => $lead->credit_score ?? '',
                'is_frequent_user'    => $lead->is_frequent_user ?? '',
                'is_urgent'           => $lead->is_urgent ?? '',
                'is_high_hiring'      => $lead->is_high_hiring ?? '',
                'phone_verified'      => $lead->is_phone_verified ?? '',
                'hasEnoughCredits'    => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
                'questionsAndAnswers' => $questionsAndAnswers,
            ];
        }

        if (empty($leadViews)) {
            return;
        }

        $htmlView = view('emails.lead_buyers.leads.lead_buyer_grouped_leads', [
            'baseUrl'         => config('app.react_base_url'),
            'name'            => $user->name,
            'leadDetailsList' => $leadViews,
        ])->render();

        // Extract CSS from <style> blocks
         // (Assume $htmlView already built with Blade earlier)

// 1) Extract CSS from <style> blocks (the same as you already do)
        $allStyleContents = '';
        if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $htmlView, $matches)) {
            foreach ($matches[1] as $cssText) {
                $allStyleContents .= $cssText . "\n";
            }
        }

        // 2) Extract accordion-related rules (keep only small set)
        $accordionRules = '';
        if (!empty($allStyleContents)) {
            if (preg_match_all('/[^}]*?(ac-toggle|accordion-content|accordion-header|:checked)[^}]*}/i', $allStyleContents, $ruleMatches)) {
                foreach ($ruleMatches[0] as $rule) {
                    $accordionRules .= trim($rule) . "\n";
                }
            }
        }
        if (empty($accordionRules) && !empty($allStyleContents)) {
            $accordionRules = $allStyleContents;
        }

        $styleBlockToKeep = '';
        if (!empty(trim($accordionRules))) {
            // Ensure the rules include input.ac-toggle + :checked rules
            $styleBlockToKeep = "<style type=\"text/css\">\n" . $accordionRules . "\n</style>\n";
        }

        // ===== IMPORTANT: remove ALL <style> blocks before inlining =====
        $htmlForInlining = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $htmlView);

        // 3) Inline CSS (now the accordion rules are not present to be inlined)
        $converter   = new CssToInlineStyles();
        $htmlInlined = $converter->convert($htmlForInlining);

        // 4) Defensive: ensure accordion-content fallback is expanded (prevent collapse forced inline)
        $htmlInlined = preg_replace(
            '/class="accordion-content"([^>]*)style="[^"]*"/i',
            'class="accordion-content"$1style="max-height:none; overflow:visible; background:#ffffff; border-bottom-left-radius:8px; border-bottom-right-radius:8px;"',
            $htmlInlined
        );

        // 5) Reinsert the small accordion <style> block so supporting clients can collapse
        if (!empty($styleBlockToKeep)) {
            if (stripos($htmlInlined, '</head>') !== false) {
                $htmlFinal = preg_replace('/<\/head>/i', $styleBlockToKeep . '</head>', $htmlInlined, 1);
            } elseif (stripos($htmlInlined, '<body') !== false) {
                $htmlFinal = preg_replace('/(<body[^>]*>)/i', "$1\n" . $styleBlockToKeep, $htmlInlined, 1);
            } else {
                $htmlFinal = $styleBlockToKeep . $htmlInlined;
            }
        } else {
            $htmlFinal = $htmlInlined;
        }


        // Send via Zoho API
        $url      = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);
        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
        $toEmail   = $user->email;
        $subject   = 'Today’s Missed Leads – Take Action Before It’s Too Late';
        $hasAccordionStyle = stripos($htmlFinal, 'input.ac-toggle') !== false;
        $hasInputs = stripos($htmlFinal, '<input type="checkbox"') !== false;
        DB::table('zoho_logs')->insert([
            'url'          => $url,
            'function_name'=> 'sendGroupedLeadEmailBidNotEnough',
            'ipaddress'    => request()->ip(),
            'created_at'   => now(),
        ]);

        $response = Http::withToken($accessToken)->post($url, [
            'data' => [
                [
                    'from' => [
                        'email'     => $fromEmail,
                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com'),
                    ],
                    'to' => [
                        ['email' => $toEmail],
                    ],
                    'subject'    => $subject,
                    'content'    => $htmlFinal,
                    'mail_format'=> 'html',
                    'org_email'  => true,
                ]
            ]
        ]);

        $rel = self::getZohoMailResponse($response);

        foreach ($leads as $leadId) {
            EmailLog::insertGetId([
                'user_id'     => $user->id,
                'from_email'  => $fromEmail .'-'.$hasAccordionStyle . '-' .$hasInputs,
                'lead_id'     => $leadId,
                'to_email'    => $toEmail,
                'message_id'  => $rel['message_id'],
                'subject'     => $subject,
                'setting_name'=> 'Send Lead Details Email At Evening',
                'content'     => $htmlFinal,
                'zoho_url'    => $url,
                'response'    => json_encode($rel),
            ]);
        }
    }



    // public static function sendLeadRequestReply($userId, $leadId)
    // {

    //     $sendLeadRequestEmail = EmailSetting::where('setting_name', 'New Lead - Request Reply')->value('setting_value');

    //     if ($sendLeadRequestEmail) {
    //         $accessToken = ZohoHelper::getAccessToken();

    //         $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

    //         if (!empty($zohoId)) {
    //             $user = User::where('id', $userId)->first();

    //             if (!empty($user)) {


    //                 $lead = LeadRequest::with([
    //                     'category',
    //                     'customer'
    //                 ])
    //                     ->where('id', $leadId)
    //                     ->first();

    //                 $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
    //                     ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
    //                     ->map(fn($item) => [
    //                         'question' => $item['ques'],
    //                         'answer' => implode(', ', $item['ans'])
    //                     ])
    //                     ->toArray();


    //                 $htmlView = view('emails.lead_buyers.leads.lead_buyer_requestreply',  [
    //                     'baseUrl' => config('app.react_base_url'),
    //                     'name' => $user->name,
    //                     'lead_name' => $lead->customer->name ?? '',
    //                     'postcode' => $lead->postcode ?? '',
    //                     'masked_phone' => $lead->customer?->phone ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2) : 'N/A',
    //                     'masked_email' => $lead->customer?->email ? (function ($email) {
    //                         [$name, $domain] = explode('@', $email);
    //                         $visible = substr($name, 0, 2);
    //                         $masked = str_repeat('*', max(strlen($name) - 2, 0));
    //                         return $visible . $masked . '@' . $domain;
    //                     })($lead->customer->email) : 'N/A',

    //                     'service_name' => $lead->category->name ?? '',
    //                     'has_additional_details' => $lead->has_additional_details ?? '',
    //                     'credit_score' => $lead->credit_score ?? '',
    //                     'is_frequent_user' => $lead->is_frequent_user ?? '',
    //                     'is_urgent' => $lead->is_urgent ?? '',
    //                     'is_high_hiring' => $lead->is_high_hiring ?? '',
    //                     'phone_verified' => $lead->is_phone_verified ?? '',
    //                     'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
    //                     'remaining_credit' => intval($user->total_credit - $lead->credit_score),
    //                     'questionsAndAnswers' => $questionsAndAnswers,
    //                 ])->render();

    //                 $htmlContent = (new CssToInlineStyles())->convert($htmlView);
    //                 $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

    //                 $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
    //                 $toEmail = $user->email;
    //                 $subject = 'New Lead Request – Prompt Reply Appreciated';

    //                 DB::table('zoho_logs')->insert([
    //                     'url' => $url,
    //                     'function_name' => 'sendLeadRequestReply',
    //                     'ipaddress' => request()->ip(),
    //                     'created_at' => now(),
    //                 ]);

    //                 $response = Http::withToken($accessToken)
    //                     ->post($url, [
    //                         'data' => [
    //                             [
    //                                 'from' => [
    //                                     'email' => $fromEmail,
    //                                     'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
    //                                 ],
    //                                 'to' => [
    //                                     [
    //                                         'email' => $toEmail
    //                                     ]
    //                                 ],
    //                                 'subject' => $subject,
    //                                 'content' => $htmlContent,
    //                                 'mail_format' => 'html',
    //                                 'org_email' => true
    //                             ]
    //                         ]
    //                     ]);
    //                 $rel = self::getZohoMailResponse($response);

    //                 $dataE['user_id'] = $user->id;
    //                 $dataE['from_email'] = $fromEmail;
    //                 $dataE['lead_id'] = $leadId;
    //                 $dataE['to_email'] = $toEmail;
    //                 $dataE['message_id'] = $rel['message_id'];
    //                 $dataE['subject'] = $subject;
    //                 $dataE['setting_name'] = 'New Lead - Request Reply';
    //                 $dataE['content'] = $htmlContent;
    //                 $dataE['zoho_url'] = $url;
    //                 $dataE['response'] = json_encode($rel);
    //                 EmailLog::insertGetId($dataE);
    //             }
    //         }
    //     }
    // }

    public static function sendGroupedRequestReplyLeads($userId, $leadIds)
    {
        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'New Lead - Request Reply')->value('setting_value');

        if (!$sendLeadRequestEmail || empty($leadIds)) return;

        $accessToken = ZohoHelper::getAccessToken();
        $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
        $user = User::where('id', $userId)->first();

        if (!$accessToken || !$zohoId || !$user) return;

        // Fetch all leads together
        $leads = LeadRequest::with(['category', 'customer'])
            ->whereIn('id', $leadIds)
            ->get();

        $groupedLeadsData = $leads->map(function ($lead) use ($user) {
            $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
                ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                ->map(fn($item) => [
                    'question' => $item['ques'],
                    'answer' => implode(', ', $item['ans'])
                ])
                ->toArray();

            return [
                'lead_name' => $lead->customer->name ?? '',
                'postcode' => $lead->postcode ?? '',
                'masked_phone' => $lead->customer?->phone ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2) : 'N/A',
                'masked_email' => $lead->customer?->email ? (function ($email) {
                    [$name, $domain] = explode('@', $email);
                    $visible = substr($name, 0, 2);
                    $masked = str_repeat('*', max(strlen($name) - 2, 0));
                    return $visible . $masked . '@' . $domain;
                })($lead->customer->email) : 'N/A',
                'service_name' => $lead->category->name ?? '',
                'has_additional_details' => $lead->has_additional_details ?? '',
                'credit_score' => $lead->credit_score ?? '',
                'is_frequent_user' => $lead->is_frequent_user ?? '',
                'is_urgent' => $lead->is_urgent ?? '',
                'is_high_hiring' => $lead->is_high_hiring ?? '',
                'phone_verified' => $lead->is_phone_verified ?? '',
                'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
                'remaining_credit' => intval($user->total_credit - $lead->credit_score),
                'questionsAndAnswers' => $questionsAndAnswers,
            ];
        });

        // Render single email with all leads grouped
        $htmlView = view('emails.lead_buyers.leads.lead_buyer_requestreply', [
            'baseUrl' => config('app.react_base_url'),
            'name' => $user->name,
            'leads' => $groupedLeadsData
        ])->render();

        $htmlContent = (new CssToInlineStyles())->convert($htmlView);
        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
        $toEmail = $user->email;
        $subject = 'You Have New Leads – Reply Promptly';

        DB::table('zoho_logs')->insert([
            'url' => $url,
            'function_name' => 'sendGroupedRequestReplyLeads',
            'ipaddress' => request()->ip(),
            'created_at' => now(),
        ]);

        $response = Http::withToken($accessToken)
            ->post($url, [
                'data' => [
                    [
                        'from' => [
                            'email' => $fromEmail,
                            'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                        ],
                        'to' => [
                            [
                                'email' => $toEmail
                            ]
                        ],
                        'subject' => $subject,
                        'content' => $htmlContent,
                        'mail_format' => 'html',
                        'org_email' => true
                    ]
                ]
            ]);

        $rel = self::getZohoMailResponse($response);

        // Log the grouped email with all lead IDs
        foreach ($leadIds as $leadId) {
            EmailLog::insert([
                'user_id' => $user->id,
                'from_email' => $fromEmail,
                'lead_id' => $leadId,
                'to_email' => $toEmail,
                'message_id' => $rel['message_id'],
                'subject' => $subject,
                'setting_name' => 'New Lead - Request Reply',
                'content' => $htmlContent,
                'zoho_url' => $url,
                'response' => json_encode($rel),
                'created_at' => now()
            ]);
        }
    }


    public static function unsoldLeadEmail($data)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name', $data['setting_name'])->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $data['userId']);

            if (!empty($zohoId)) {
                $user = User::where('id', $data['userId'])->first();

                if (!empty($user)) {


                    $lead = LeadRequest::with([
                        'category',
                        'customer'
                    ])
                        ->where('id', $data['leadId'])
                        ->first();

                    $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
                        ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                        ->map(fn($item) => [
                            'question' => $item['ques'],
                            'answer' => implode(', ', $item['ans'])
                        ])
                        ->toArray();


                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_request_aftertime',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name,
                        'lead_name' => $lead->customer->name ?? '',
                        'postcode' => $lead->postcode ?? '',
                        'masked_phone' => $lead->customer?->phone ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2) : 'N/A',
                        'masked_email' => $lead->customer?->email ? (function ($email) {
                            [$name, $domain] = explode('@', $email);
                            $visible = substr($name, 0, 2);
                            $masked = str_repeat('*', max(strlen($name) - 2, 0));
                            return $visible . $masked . '@' . $domain;
                        })($lead->customer->email) : 'N/A',

                        'service_name' => $lead->category->name ?? '',
                        'has_additional_details' => $lead->has_additional_details ?? '',
                        'credit_score' => $lead->credit_score ?? '',
                        'old_credit_score' => $lead->old_credit ?? '',
                        'step' => $data['step'],
                        'is_frequent_user' => $lead->is_frequent_user ?? '',
                        'is_urgent' => $lead->is_urgent ?? '',
                        'is_high_hiring' => $lead->is_high_hiring ?? '',
                        'phone_verified' => $lead->is_phone_verified ?? '',
                        'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
                        'questionsAndAnswers' => $questionsAndAnswers,
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = $data['subject'];

                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'unsoldLeadEmail',
                        'ipaddress' => request()->ip(),
                        'created_at' => now(),
                    ]);

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['lead_id'] = $data['leadId'];
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = $data['setting_name'];
                    $dataE['step'] = $data['step'];
                    $dataE['lead_id'] = $data['leadId'];
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }



    public static function sendLeadsAfterDays($userId, $leadData, $settingValue)
    {



        $sendLeadRequestEmail = EmailSetting::where('setting_name', $settingValue)->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            $totalLeadCount = $leadData['total_lead_count'];
            $totalCreditSum = $leadData['total_credit_sum'];
            $leadDataList = $leadData['lead_data'];
            $creditPurchase = isset($leadData['credit_purchase']) ?? $leadData['credit_purchase'];
            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                if (!empty($user)) {
                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_request_afterdays',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name,
                        'total_count' => $totalLeadCount,
                        'total_credt_sum' => (int) ($totalCreditSum / $totalLeadCount),
                        'leadDataList' => $leadDataList,
                        'credit_purchase' => false,
                        'credit_value' => 0,
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = "7 Days, 0 Leads – Let’s Fix That";
                    if($creditPurchase){
                        $subject = "7 Days, 0 Leads – Let’s Fix That";
                    }

                     DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'sendLeadsAfterDays',
                        'ipaddress' => request()->ip(),
                        'created_at' => now(),
                    ]);

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;

                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = $settingValue;

                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    public static function creditsAfter5Days($userId, $leadData, $settingValue)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name', $settingValue)->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            $totalLeadCount = $leadData['total_lead_count'];
            $totalCreditSum = $leadData['total_credit_sum'];
            $leadDataList = $leadData['lead_data'];
            $creditPurchase = isset($leadData['credit_purchase']) ?? $leadData['credit_purchase'];
            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();


                if (!empty($user)) {
                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_request_afterdays',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name,
                        'total_count' => $totalLeadCount,
                        'total_credt_sum' => (int) ($totalCreditSum / $totalLeadCount),
                        'leadDataList' => $leadDataList,
                        'credit_value' => 1,
                        'credit_purchase' => $creditPurchase
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = "Jobs Matching Your Preferences Are Waiting – You're Just 1 Top-Up Away";
                    if($creditPurchase){
                        $subject = "Jobs Matching Your Preferences Are Waiting – You're Just 1 Top-Up Away";
                    }

                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'creditsAfter5Days',
                        'ipaddress' => request()->ip(),
                        'created_at' => now(),
                    ]);

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;

                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = $settingValue;

                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    public static function leadPurchaseStatusUpdateEmail($rlead, $setting_name, $subject){
        $sendLeadRequestEmail = EmailSetting::where('setting_name', $setting_name)->value('setting_value');
        if ($sendLeadRequestEmail) {
            $userId = $rlead->seller_id;
            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();
                if(!empty($user)){

                    $lead = LeadRequest::with(['category','customer'])
                        ->where('id', $rlead->lead_id)
                        ->first();

                    $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
                        ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                        ->map(fn($item) => [
                            'question' => $item['ques'],
                            'answer' => implode(', ', $item['ans'])
                        ])
                        ->toArray();

                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_status_update',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name,
                        'service' => $lead->category->name ?? '',
                        'seller_id' => $rlead->seller_id,
                        'buyer_id' => $rlead->buyer_id,
                        'lead_id' => $lead->id,
                        'has_additional_details' => $lead->has_additional_details ?? '',
                        'credit_score' => $lead->credit_score ?? '',
                        'is_frequent_user' => $lead->is_frequent_user ?? '',
                        'is_urgent' => $lead->is_urgent ?? '',
                        'is_high_hiring' => $lead->is_high_hiring ?? '',
                        'phone_verified' => $lead->is_phone_verified ?? '',
                        'postcode' => $lead->postcode ?? '',
                        'phone' => $lead->customer?->phone ?? 'N/A',
                        'email' => $lead->customer?->email ?? 'N/A',
                        'questionsAndAnswers' => $questionsAndAnswers,
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;

                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'leadPuchaseStatusUpdateEmail',
                        'ipaddress' => request()->ip(),
                        'created_at' => now(),
                    ]);


                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = $setting_name;
                    $dataE['content'] = $htmlContent;
                    $dataE['lead_id'] = $rlead->lead_id;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }

    }

    //

    public static function newLeadClosedEmail($leadId, $sellerId){

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Lead Buyer - New Lead Closed (Email Notification)')->value('setting_value');

        $allSellers = RecommendedLead::where('lead_id',$leadId)->where('seller_id','!=',$sellerId)->get();

        foreach ($allSellers as $user) {
            $userId =$user->seller_id;
            $sendEmailSettings = CustomHelper::getSingleNotificationSetting($userId,'buyer_email_customer_closing_leads','buyer','email');

            if ($sendLeadRequestEmail && $sendEmailSettings) {
                $accessToken = ZohoHelper::getAccessToken();


                $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

                if (!empty($zohoId)) {
                    $user = User::where('id', $userId)->first();
                    if(!empty($user)){
                        $lead = LeadRequest::with([
                            'category',
                            'customer'
                        ])
                            ->where('id', $leadId)
                            ->first();

                        $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
                            ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                            ->map(fn($item) => [
                                'question' => $item['ques'],
                                'answer' => implode(', ', $item['ans'])
                            ])
                            ->toArray();


                        $htmlView = view('emails.lead_buyers.leads.lead_buyer_closed',  [
                            'baseUrl' => config('app.react_base_url'),
                            'name' => $user->name,
                            'lead_name' => $lead->customer->name ?? '',
                            'postcode' => $lead->postcode ?? '',
                            'masked_phone' => $lead->customer?->phone ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2) : 'N/A',
                            'masked_email' => $lead->customer?->email ? (function ($email) {
                                [$name, $domain] = explode('@', $email);
                                $visible = substr($name, 0, 2);
                                $masked = str_repeat('*', max(strlen($name) - 2, 0));
                                return $visible . $masked . '@' . $domain;
                            })($lead->customer->email) : 'N/A',

                            'service_name' => $lead->category->name ?? '',
                            'has_additional_details' => $lead->has_additional_details ?? '',
                            'credit_score' => $lead->credit_score ?? '',
                            'is_frequent_user' => $lead->is_frequent_user ?? '',
                            'is_urgent' => $lead->is_urgent ?? '',
                            'is_high_hiring' => $lead->is_high_hiring ?? '',
                            'phone_verified' => $lead->is_phone_verified ?? '',
                            'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
                            'questionsAndAnswers' => $questionsAndAnswers,
                        ])->render();

                        $htmlContent = (new CssToInlineStyles())->convert($htmlView);


                        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                        $toEmail = $user->email;
                        $subject = 'Lead Closed: ' . $lead->category->name .' request has been taken';


                        DB::table('zoho_logs')->insert([
                            'url' => $url,
                            'function_name' => 'newLeadClosedEmail',
                            'created_at' => now(),
                        ]);



                        $response = Http::withToken($accessToken)
                            ->post($url, [
                                'data' => [
                                    [
                                        'from' => [
                                            'email' => $fromEmail,
                                            'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                        ],
                                        'to' => [
                                            [
                                                'email' => $toEmail
                                            ]
                                        ],
                                        'subject' => $subject,
                                        'content' => $htmlContent,
                                        'mail_format' => 'html',
                                        'org_email' => true
                                    ]
                                ]
                            ]);
                        $rel = self::getZohoMailResponse($response);
                        Log::info('New Lead Closed -when pending to hired', [
                            'user_id' => $sellerId,
                            'response' => $response->json(),
                        ]);
                        $dataE['user_id'] = $user->id;
                        $dataE['from_email'] = $fromEmail;
                        $dataE['to_email'] = $toEmail;
                        $dataE['message_id'] = $rel['message_id'];
                        $dataE['subject'] = $subject;
                        $dataE['setting_name'] = 'Lead Buyer - New Lead Closed (Email Notification)';
                        $dataE['lead_id'] = $leadId;
                        $dataE['content'] = $htmlContent;
                        $dataE['zoho_url'] = $url;

                        $dataE['response'] = json_encode($rel);
                        EmailLog::insertGetId($dataE);
                    }
                }
            }
        }
    }

    public static function newLeadHiredEmail($leadId,$userId){

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Lead Buyer - New Lead Hired (Email Notification)')->value('setting_value');
            $sendEmailSettings = CustomHelper::getSingleNotificationSetting($userId,'buyer_email_customer_hiring_me','buyer','email');
            if ($sendLeadRequestEmail && $sendEmailSettings) {
                $accessToken = ZohoHelper::getAccessToken();


                $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

                if (!empty($zohoId)) {
                    $user = User::where('id', $userId)->first();
                    if(!empty($user)){
                        $lead = LeadRequest::with([
                            'category',
                            'customer'
                        ])
                            ->where('id', $leadId)
                            ->first();

                        $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
                            ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                            ->map(fn($item) => [
                                'question' => $item['ques'],
                                'answer' => implode(', ', $item['ans'])
                            ])
                            ->toArray();


                        $htmlView = view('emails.lead_buyers.leads.lead_buyer_hired',  [
                            'baseUrl' => config('app.react_base_url'),
                            'name' => $user->name,
                            'lead_name' => $lead->customer->name ?? '',
                            'postcode' => $lead->postcode ?? '',
                            'phone' => $lead->customer?->phone ?? 'N/A',
                            'email' => $lead->customer?->email ?? 'N/A',

                            'service_name' => $lead->category->name ?? '',
                            'has_additional_details' => $lead->has_additional_details ?? '',
                            'credit_score' => $lead->credit_score ?? '',
                            'is_frequent_user' => $lead->is_frequent_user ?? '',
                            'is_urgent' => $lead->is_urgent ?? '',
                            'is_high_hiring' => $lead->is_high_hiring ?? '',
                            'phone_verified' => $lead->is_phone_verified ?? '',
                            'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
                            'questionsAndAnswers' => $questionsAndAnswers,
                        ])->render();

                        $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                        $toEmail = $user->email;
                        $subject = 'You have purchased a new lead!';

                        DB::table('zoho_logs')->insert([
                            'url' => $url,
                            'function_name' => 'newLeadHiredEmail',
                            'created_at' => now(),
                        ]);

                        $response = Http::withToken($accessToken)
                            ->post($url, [
                                'data' => [
                                    [
                                        'from' => [
                                            'email' => $fromEmail,
                                            'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                        ],
                                        'to' => [
                                            [
                                                'email' => $toEmail
                                            ]
                                        ],
                                        'subject' => $subject,
                                        'content' => $htmlContent,
                                        'mail_format' => 'html',
                                        'org_email' => true
                                    ]
                                ]
                            ]);
                        $rel = self::getZohoMailResponse($response);
                         Log::info('New Lead Hired -when pending to hired', [
                            'user_id' => $userId,
                            'response' => $response->json(),
                        ]);
                        $dataE['user_id'] = $user->id;
                        $dataE['from_email'] = $fromEmail;
                        $dataE['to_email'] = $toEmail;
                        $dataE['message_id'] = $rel['message_id'];
                        $dataE['subject'] = $subject;
                        $dataE['setting_name'] = 'Lead Buyer - New Lead Hired (Email Notification)';
                        $dataE['lead_id'] = $leadId;
                        $dataE['content'] = $htmlContent;
                        $dataE['zoho_url'] = $url;
                        $dataE['response'] = json_encode($rel);

                        EmailLog::insertGetId($dataE);
                    }
                }

        }
    }


    public static function newLeadPoolOf7LeadBuyerEmail($leadId, $userId){
        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'New Lead Pool of 7 Lead Buyer')->value('setting_value');
        $sendEmailSettings = CustomHelper::getSingleNotificationSetting($userId,'buyer_email_new_lead','buyer','email');
        if ($sendLeadRequestEmail && $sendEmailSettings) {
            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();
                if(!empty($user)){
                    $lead = LeadRequest::with([
                        'category',
                        'customer'
                    ])
                        ->where('id', $leadId)
                        ->first();

                    $questionsAndAnswers = collect(json_decode($lead->arrayed_questions, true))
                        ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                        ->map(fn($item) => [
                            'question' => $item['ques'],
                            'answer' => implode(', ', $item['ans'])
                        ])
                        ->toArray();


                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_pool_of_7_lead_buyer',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name,
                        'lead_name' => $lead->customer->name ?? '',
                        'postcode' => $lead->postcode ?? '',
                        'masked_phone' => $lead->customer?->phone ? substr($lead->customer->phone, 0, 2) . str_repeat('*', strlen($lead->customer->phone) - 2) : 'N/A',
                        'masked_email' => $lead->customer?->email ? (function ($email) {
                            [$name, $domain] = explode('@', $email);
                            $visible = substr($name, 0, 2);
                            $masked = str_repeat('*', max(strlen($name) - 2, 0));
                            return $visible . $masked . '@' . $domain;
                        })($lead->customer->email) : 'N/A',

                        'service_name' => $lead->category->name ?? '',
                        'has_additional_details' => $lead->has_additional_details ?? '',
                        'credit_score' => $lead->credit_score ?? '',
                        'is_frequent_user' => $lead->is_frequent_user ?? '',
                        'is_urgent' => $lead->is_urgent ?? '',
                        'is_high_hiring' => $lead->is_high_hiring ?? '',
                        'phone_verified' => $lead->is_phone_verified ?? '',
                        'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
                        'questionsAndAnswers' => $questionsAndAnswers,
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Hey! You have got a new lead!';


                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'newLeadPoolOf7LeadBuyerEmail',
                        'created_at' => now(),
                    ]);

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'New Lead Pool of 7 Lead Buyer';
                    $dataE['lead_id'] = $leadId;
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;

                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }


    public static function sendLoginMagicLinkEmail($user, $token)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name','Send Login Magic Link')->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();
            if($user->user_type == 1){
                $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $user->id);
            }
            elseif($user->user_type == 2){
                $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $user->id);
            }


            if(!empty($zohoId)){
                if(!empty($user)){
                    $htmlView = view('emails.login.login_with_magic_link',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name,
                        'token' => $token,
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    if($user->user_type == 1){
                        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);
                    }
                    elseif($user->user_type == 2){
                        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_QUOTE_CUSTOMERS_API_URL, $zohoId);
                    }

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Here is your magic link';


                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'sendLoginMagicLinkEmail',
                        'created_at' => now(),
                    ]);

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);
                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send Login Magic Link';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    public static function switchEmailToQuoteAccount($userId){

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Switch Account From Lead Buyer')->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_switch_to_quote',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = "You're Now a Quote Customer — Start Requesting Quotes Today!";


                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'switchEmailToQuoteAccount',
                        'created_at' => now(),
                    ]);

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Switch Account From Lead Buyer';

                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;

                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }



    }

     public static function switchEmailToLeadAccount($userId){

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Switch Account From Quote Customer')->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();
                    $htmlView = view('emails.customers.quote_customer_switch_account',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'noreply@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Your Account Has Been Transitioned to a Lead Buyer Account';


                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'switchEmailToLeadAccount',
                        'created_at' => now(),
                    ]);

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localists.com') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html',
                                    'org_email' => true
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Switch Account From Quote Customer';

                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;

                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }



    }
    private static function getZohoMailResponse($response)
    {
        $zohoMailResult = [];
        // print_r($response->json());
        if ($response->successful()) {
            $zohoMailResult = [
                'status' => 'success',
                'message_id' => $response->json()['data'][0]['details']['message_id'] ?? null,
                'message' => $response->json()['data'][0]['message'] ?? 'Unknown',
                'details' => $response->json(),
            ];
        } else {
            $zohoMailResult = [
                'status' => 'error',
                'message_id' => '',
                'message' => $response->json()['data'][0]['message'] ?? 'Unknown error',
                'details' => $response->json(),
            ];
        }

        return $zohoMailResult;
    }
}
