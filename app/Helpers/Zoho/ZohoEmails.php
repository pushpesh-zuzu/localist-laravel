<?php

namespace App\Helpers\Zoho;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Illuminate\Support\Facades\Http;
use App\Helpers\CustomHelper;
use App\Models\User;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Models\LeadRequest;

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
                        'baseUrl' => env('REACT_BASE_URL'),
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $password,
                        'jobs' => rand(1, 50),
                        'services' => $services
                    ])->render();
                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);
                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com');
                    $toEmail = $user->email;
                    $subject = 'Welcome to Localist';
                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html'
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
                        'baseUrl' => env('REACT_BASE_URL'),
                        'name' => $user->name
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com');
                    $toEmail = $user->email;
                    $subject = 'Boost Your Sales with Auto-Buy !';

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html'
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


    public static function sendIncompleteRegistrationEmail($userId)
    {

        $sendIncompleteRegEmail = EmailSetting::where('setting_name', 'Send Incomplete Registration Email')->value('setting_value');

        if ($sendIncompleteRegEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                if (!empty($user)) {


                    $htmlView = view('emails.lead_buyers.registration.lead_buyer_incomplete_registration',  [
                        'baseUrl' => env('REACT_BASE_URL'),
                        'name' => $user->name
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com');
                    $toEmail = $user->email;
                    $subject = 'Complete your registration!';
                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html'
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

    public static function sendLeadRequestEmail($userId, $leadId)
    {


        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'New Lead-Auto Bid Disable (Check Credit)')->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                if (!empty($user)) {


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


                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_request',  [
                        'baseUrl' => env('REACT_BASE_URL'),
                        'name' => $user->name,
                        'lead_name' => $lead->customer->name ?? '',
                        'postcode' => $lead->customer->zipcode ?? '',
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
                    $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com');
                    $toEmail = $user->email;
                    $subject = 'New Lead! Turn On Auto Bid Now !';

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html'
                                ]
                            ]
                        ]);

                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['lead_id'] = $leadId;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'New Lead-Auto Bid Disable (Check Credit)';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    public static function sendLeadEmailBidEnough($userId, $leadId)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Send New Lead Request Email')->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                if (!empty($user)) {


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


                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_autobidenough',  [
                        'baseUrl' => env('REACT_BASE_URL'),
                        'name' => $user->name,
                        'lead_name' => $lead->customer->name ?? '',
                        'postcode' => $lead->customer->zipcode ?? '',
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
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com');
                    $toEmail = $user->email;
                    $subject = 'New lead opportunity just for you!';

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html'
                                ]
                            ]
                        ]);

                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['lead_id'] = $leadId;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send New Lead Request Email';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    public static function sendLeadEmailBidNotEnough($userId, $leadId)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Send New Lead Request Email')->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                if (!empty($user)) {


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


                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_request',  [
                        'baseUrl' => env('REACT_BASE_URL'),
                        'name' => $user->name,
                        'lead_name' => $lead->customer->name ?? '',
                        'postcode' => $lead->customer->zipcode ?? '',
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
                    $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com');
                    $toEmail = $user->email;
                    $subject = 'New lead opportunity just for you!';

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html'
                                ]
                            ]
                        ]);

                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['lead_id'] = $leadId;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send New Lead Request Email';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }


    public static function sendLeadRequestReply($userId, $leadId)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Send New Lead Request Email')->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                if (!empty($user)) {


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


                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_requestreply',  [
                        'baseUrl' => env('REACT_BASE_URL'),
                        'name' => $user->name,
                        'lead_name' => $lead->customer->name ?? '',
                        'postcode' => $lead->customer->zipcode ?? '',
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
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com');
                    $toEmail = $user->email;
                    $subject = 'New lead opportunity just for you!';

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html'
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['lead_id'] = $leadId;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send New Lead Request Email';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    public static function sendLeadsAfterTime($userId, $leadId)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Send New Lead Request After 48hrs Email')->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                if (!empty($user)) {


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


                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_request_aftertime',  [
                        'baseUrl' => env('REACT_BASE_URL'),
                        'name' => $user->name,
                        'lead_name' => $lead->customer->name ?? '',
                        'postcode' => $lead->customer->zipcode ?? '',
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
                    $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com');
                    $toEmail = $user->email;
                    $subject = 'New lead opportunity just for you!';

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html'
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['lead_id'] = $leadId;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send New Lead Request After 48hrs Email';
                    $dataE['step'] = 1;
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }

    public static function sendLeadsAfterTimeNationWide($userId, $leadId)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Send New Lead Request After 48hrs Email')->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();

            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                if (!empty($user)) {


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


                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_request_aftertime',  [
                        'baseUrl' => env('REACT_BASE_URL'),
                        'name' => $user->name,
                        'lead_name' => $lead->customer->name ?? '',
                        'postcode' => $lead->customer->zipcode ?? '',
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
                    $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com');
                    $toEmail = $user->email;
                    $subject = 'New lead opportunity just for you!';

                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html'
                                ]
                            ]
                        ]);
                    $rel = self::getZohoMailResponse($response);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['lead_id'] = $leadId;
                    $dataE['to_email'] = $toEmail;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Send New Lead Request After 48hrs Email';
                    $dataE['step'] = 2;
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
                        'baseUrl' => env('REACT_BASE_URL'),
                        'name' => $user->name,
                        'total_count' => $totalLeadCount,
                        'total_credt_sum' => $totalCreditSum,
                        'leadDataList' => $leadDataList,
                        'credit_purchase' => $creditPurchase
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com');
                    $toEmail = $user->email;
                    $subject = 'New lead opportunity just for you!';
                    if($creditPurchase){
                        $subject = "You're Missing Out on Job Opportunities";
                    }


                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                    ],
                                    'to' => [
                                        [
                                            'email' => $toEmail
                                        ]
                                    ],
                                    'subject' => $subject,
                                    'content' => $htmlContent,
                                    'mail_format' => 'html'
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
