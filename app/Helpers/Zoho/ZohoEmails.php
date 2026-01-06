<?php

namespace App\Helpers\Zoho;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Illuminate\Support\Facades\Http;
use App\Helpers\CustomHelper;
use App\Helpers\WhatsAppMessage;
use App\Models\AbandonedUser;
use App\Models\User;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Models\Invoice;
use App\Models\LeadRequest;
use App\Models\RecommendedLead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\LeadService;
use App\Models\Postcode;

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

                    $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
                    $user->update(['remember_token' => $token]);
                    $htmlView = view('emails.lead_buyers.registration.lead_buyer_registration_new',  [
                        'baseUrl' => config('app.react_base_url'),
                        'siteUrl' => config('app.url'),
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $password,
                        'token' => $token,
                        // 'services' => $services
                    ])->render();
                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Welcome to Localists';

                    $attachments = [];
                    $pdfPath = public_path('Localists_Lead_Strategies.pdf');

                    if (file_exists($pdfPath)) {

                        $uploadResponse = Http::withToken($accessToken)
                            ->attach('file', fopen($pdfPath, 'r'), 'Localists_Lead_Strategies.pdf')
                            ->post('https://www.zohoapis.eu/crm/v8/files');


                        $attachmentId = $uploadResponse->json('data.0.details.id');

                        if ($attachmentId) {

                            $attachments = [
                                [
                                    'id' => $attachmentId
                                ]
                            ];
                        } else {
                            Log::error("CRM attachment upload failed: " . $uploadResponse->body());
                        }
                    }


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
                                    'attachments' => $attachments,
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


        //start comment by ashish//
        // $sendWhatsapp = EmailSetting::where('setting_name', 'Send Welcome Email')->value('whatsapp_setting_value');

        // if ($sendWhatsapp) {
        //     $user = User::find($userId);
        //     if ($user && !empty($user->phone)) {
        //         try {
        //             $response = WhatsAppMessage::sendTemplate(
        //                 userId: $user->id,
        //                 phoneNumber: null,
        //                 templateName: "lead_buyer_registration",
        //                 languageCode: "en_US",
        //                 components: [
        //                     [
        //                         'type' => 'body',
        //                         'parameters' => [
        //                             ['type' => 'text', 'text' => $user->name],
        //                         ],
        //                     ],
        //                 ]
        //             );
        //         } catch (\Exception $e) {
        //             Log::error('WhatsApp send failed for user ' . $userId . ': ' . $e->getMessage());
        //         }
        //     }
        // }


        //end comment by ashish//
    }

    public static function sendWelcomeEmailQuoteCustomer($userId, $password, $phoneOtp)
    {
        $sendWelcomeEmail = EmailSetting::where('setting_name', 'Send Welcome Email For Customer')->value('setting_value');

        if ($sendWelcomeEmail) {
            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();

                $lead = LeadRequest::where('customer_id', $userId)
                    ->latest('created_at') // order by created_at DESC
                    ->first();
                if (!empty($user)) {

                    $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
                    $user->update(['remember_token' => $token]);


                    $htmlView = view('emails.customers.registration.quote_customer_registration',  [
                        'baseUrl' => config('app.react_base_url'),
                        'siteUrl' => config('app.url'),
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $password,
                        'phone_otp' => $phoneOtp,
                        'leadId' => $lead->id ?? '',
                        'buyerId' => $userId ?? '',
                        'token' => $token ?? '',


                    ])->render();
                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_QUOTE_CUSTOMERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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
        //start comment by ashish//

        // $sendWhatsapp = EmailSetting::where('setting_name', 'Send Welcome Email For Customer')->value('whatsapp_setting_value');

        // if ($sendWhatsapp) {
        //     $user = User::find($userId);
        //     if ($user && !empty($user->phone)) {
        //         try {
        //             $response = WhatsAppMessage::sendTemplate(
        //                 userId: $user->id,
        //                 phoneNumber: null,
        //                 templateName: "quote_customer_registration",
        //                 languageCode: "en_US",
        //                 components: [
        //                     [
        //                         'type' => 'body',
        //                         'parameters' => [
        //                             ['type' => 'text', 'text' => $user->name],
        //                         ],
        //                     ],
        //                 ]
        //             );
        //         } catch (\Exception $e) {
        //             Log::error('WhatsApp send failed for user ' . $userId . ': ' . $e->getMessage());
        //         }
        //     }
        // }

        //end comment by ashish//
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
                        'siteUrl' => config('app.url'),
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
                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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

    public static function sendAbandonedEncouragementEmail($userId, $serviceName = null)
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
                        'siteUrl' => config('app.url'),
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
                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
                    $toEmail = $user->email;

                    $subject = "Your " . ($serviceName ? $serviceName : "service") . " quote is nearly done";

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
            Log::info('Incomplete registrtion p1', [
                'message' => $zohoId
            ]);
            if (!empty($zohoId)) {
                $user = AbandonedUser::where('id', $userId)->first();

                if (!empty($user)) {

                    Log::info('Incomplete registrtion p2', [
                        'message' => $user
                    ]);
                    $htmlView = view('emails.lead_buyers.registration.lead_buyer_incomplete_registration',  [
                        'baseUrl' => config('app.react_base_url'),
                        'siteUrl' => config('app.url'),
                        'name' => $user->name
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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
    //  'siteUrl' => config('app.url'),
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

    //                 $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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
            'siteUrl' => config('app.url'),
            'name' => $user->name,
            'leadDetailsList' => $formattedLeads,
        ])->render();

        $htmlContent = (new CssToInlineStyles())->convert($htmlView);
        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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
                    'to' => [['email' => $toEmail]],
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




        //start comment by ashish//
        // $sendWhatsapp = EmailSetting::where('setting_name', 'New Lead-Auto Bid Disable (Check Credit)')->value('whatsapp_setting_value');

        // if ($sendWhatsapp) {
        //     $user = User::find($userId);
        //     if ($user && !empty($user->phone)) {
        //         try {
        //             $response = WhatsAppMessage::sendTemplate(
        //                 userId: $user->id,
        //                 phoneNumber: null,
        //                 templateName: "lead_buyer_request",
        //                 languageCode: "en_US",
        //                 components: [
        //                     [
        //                         'type' => 'body',
        //                         'parameters' => [
        //                             ['type' => 'text', 'text' => $user->name],
        //                         ],
        //                     ],
        //                 ]
        //             );
        //         } catch (\Exception $e) {
        //             Log::error('WhatsApp send failed for user ' . $userId . ': ' . $e->getMessage());
        //         }
        //     }
        // }
        //end comment by ashish//
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
    //                 'siteUrl' => config('app.url'),
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

    //                 $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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
                'siteUrl' => config('app.url'),
                'name' => $user->name,
                'leadDetailsList' => $leadDetailsList,
            ])->render();

            $htmlContent = (new CssToInlineStyles())->convert($htmlView);
            $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);
            $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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


            //  start comment by ashish//

            // $sendWhatsapp = EmailSetting::where('setting_name', 'New Lead - Auto Bid Enabled (With Credits)')
            //     ->value('whatsapp_setting_value');

            // if ($sendWhatsapp && $user && !empty($user->phone)) {
            //     try {
            //         $response = WhatsAppMessage::sendTemplate(
            //             userId: $user->id,
            //             phoneNumber: $user->phone,
            //             templateName: "lead_buyer_autobidenough",
            //             languageCode: "en_US",
            //             components: [
            //                 [
            //                     'type' => 'body',
            //                     'parameters' => [
            //                         ['type' => 'text', 'text' => $user->name],
            //                     ],
            //                 ],
            //             ]
            //         );

            //         Log::info("WhatsApp sent successfully for user {$user->id}");
            //     } catch (\Exception $e) {
            //         Log::error("WhatsApp send failed for user {$user->id}: " . $e->getMessage());
            //     }
            // }
            //  end comment by ashish//


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
            'siteUrl' => config('app.url'),
            'name' => $user->name,
            'leadDetailsList' => $leadViews,
        ])->render();

        $htmlContent = (new CssToInlineStyles())->convert($htmlView);

        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);
        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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
                'lead_name'           => $lead->customer->name ? explode(' ', trim($lead->customer->name))[0] : '',
                'postcode'            => $lead->postcode ? explode(' ', trim($lead->postcode))[0] : '',
                'masked_phone'        => $lead->customer?->phone ? substr($lead->customer->phone, 0, 5) . str_repeat('*', strlen($lead->customer->phone) - 5) : 'N/A',
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
        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
        $toEmail   = $user->email;
        $subject   = 'Today’s Missed Leads – Take Action Before It’s Too Late';
        $hasAccordionStyle = stripos($htmlFinal, 'input.ac-toggle') !== false;
        $hasInputs = stripos($htmlFinal, '<input type="checkbox"') !== false;
        DB::table('zoho_logs')->insert([
            'url'          => $url,
            'function_name' => 'sendGroupedLeadDetails',
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
                    'mail_format' => 'html',
                    'org_email'  => true,
                ]
            ]
        ]);

        $rel = self::getZohoMailResponse($response);

        foreach ($leads as $leadId) {
            EmailLog::insertGetId([
                'user_id'     => $user->id,
                'from_email'  => $fromEmail . '-' . $hasAccordionStyle . '-' . $hasInputs,
                'lead_id'     => $leadId,
                'to_email'    => $toEmail,
                'message_id'  => $rel['message_id'],
                'subject'     => $subject,
                'setting_name' => 'Send Lead Details Email At Evening',
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

    //                 $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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
                'lead_name' => $lead->customer->name ? explode(' ', trim($lead->customer->name))[0] : '',
                'postcode' => $lead->postcode ? explode(' ', trim($lead->postcode))[0] : '',
                'masked_phone' => $lead->customer?->phone ? substr($lead->customer->phone, 0, 5) . str_repeat('*', strlen($lead->customer->phone) - 5) : 'N/A',
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
                'remaining_credit' => max(0, intval($user->total_credit - $lead->credit_score)),
                'questionsAndAnswers' => $questionsAndAnswers,
            ];
        });

        // Render single email with all leads grouped
        $htmlView = view('emails.lead_buyers.leads.lead_buyer_requestreply', [
            'baseUrl' => config('app.react_base_url'),
            'siteUrl' => config('app.url'),
            'name' => $user->name ? explode(' ', trim($user->name))[0] : '',
            'leads' => $groupedLeadsData,
            'totalCredit' => $user->total_credit
        ])->render();

        $htmlContent = (new CssToInlineStyles())->convert($htmlView);
        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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
                        'siteUrl' => config('app.url'),
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

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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
                        'siteUrl' => config('app.url'),
                        'name' => $user->name,
                        'total_count' => $totalLeadCount,
                        'total_credt_sum' => (int) ($totalCreditSum / $totalLeadCount),
                        'leadDataList' => $leadDataList,
                        'credit_purchase' => false,
                        'credit_value' => 0,
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = "7 Days, 0 Leads – Let’s Fix That";
                    if ($creditPurchase) {
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
                        'siteUrl' => config('app.url'),
                        'name' => $user->name,
                        'total_count' => $totalLeadCount,
                        'total_credt_sum' => (int) ($totalCreditSum / $totalLeadCount),
                        'leadDataList' => $leadDataList,
                        'credit_value' => 1,
                        'credit_purchase' => $creditPurchase
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = "Jobs Matching Your Preferences Are Waiting – You're Just 1 Top-Up Away";
                    if ($creditPurchase) {
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

    public static function leadPurchaseStatusUpdateEmail($rlead, $setting_name, $subject)
    {
        $sendLeadRequestEmail = EmailSetting::where('setting_name', $setting_name)->value('setting_value');
        if ($sendLeadRequestEmail) {
            $userId = $rlead->seller_id;
            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::where('id', $userId)->first();
                if (!empty($user)) {

                    $lead = LeadRequest::with(['category', 'customer'])
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
                        'siteUrl' => config('app.url'),
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

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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

    public static function newLeadClosedEmail($leadId, $sellerId)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Lead Buyer - New Lead Closed (Email Notification)')->value('setting_value');

        $allSellers = RecommendedLead::where('lead_id', $leadId)->where('seller_id', '!=', $sellerId)->get();

        foreach ($allSellers as $user) {
            $userId = $user->seller_id;
            $sendEmailSettings = CustomHelper::getSingleNotificationSetting($userId, 'buyer_email_customer_closing_leads', 'buyer', 'email');

            if ($sendLeadRequestEmail && $sendEmailSettings) {
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


                        $htmlView = view('emails.lead_buyers.leads.lead_buyer_closed',  [
                            'baseUrl' => config('app.react_base_url'),
                            'siteUrl' => config('app.url'),
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

                        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
                        $toEmail = $user->email;
                        $subject = 'Lead Closed: ' . $lead->category->name . ' request has been taken';


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

    public static function newLeadHiredEmail($leadId, $userId)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Lead Buyer - New Lead Hired (Email Notification)')->value('setting_value');
        $sendEmailSettings = CustomHelper::getSingleNotificationSetting($userId, 'buyer_email_customer_hiring_me', 'buyer', 'email');
        if ($sendLeadRequestEmail && $sendEmailSettings) {
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


                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_hired',  [
                        'baseUrl' => config('app.react_base_url'),
                        'siteUrl' => config('app.url'),
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

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'You Have Purchased a New Lead!';

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


    public static function newLeadPoolOf7LeadBuyerEmail($leadId, $userId)
    {
        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'New Lead Pool of 7 Lead Buyer')->value('setting_value');
        $sendEmailSettings = CustomHelper::getSingleNotificationSetting($userId, 'buyer_email_new_lead', 'buyer', 'email');
        if ($sendLeadRequestEmail && $sendEmailSettings) {
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


                    $htmlView = view('emails.lead_buyers.leads.lead_buyer_pool_of_7_lead_buyer',  [
                        'baseUrl' => config('app.react_base_url'),
                        'siteUrl' => config('app.url'),
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

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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


        //start comment by ashish//
        // $sendWhatsapp = EmailSetting::where('setting_name', 'New Lead Pool of 7 Lead Buyer')
        //     ->value('whatsapp_setting_value');

        // if ($sendWhatsapp) {
        //     $user = User::find($userId);
        //     $lead = LeadRequest::with('category', 'customer')->find($leadId);

        //     if ($user && !empty($user->phone) && $lead) {
        //         try {
        //             $response = WhatsAppMessage::sendTemplate(
        //                 userId: $user->id,
        //                 phoneNumber: $user->phone, // pass actual phone number
        //                 templateName: "lead_buyer_pool_of_7_lead_buyer", // make sure this template is approved
        //                 languageCode: "en_US",
        //                 components: [
        //                     [
        //                         'type' => 'body',
        //                         'parameters' => [
        //                             ['type' => 'text', 'text' => $user->name],                        // {{1}}
        //                             ['type' => 'text', 'text' => $lead->customer->name ?? ''],         // {{2}}
        //                             ['type' => 'text', 'text' => $lead->category->name ?? ''],         // {{3}}
        //                         ],
        //                     ],
        //                 ]
        //             );
        //         } catch (\Exception $e) {
        //             Log::error('WhatsApp send failed for user ' . $userId . ': ' . $e->getMessage());
        //         }
        //     }
        // }
        //end comment by ashish//
    }


    public static function sendLoginMagicLinkEmail($user, $token)
    {

        $sendLeadRequestEmail = EmailSetting::where('setting_name', 'Send Login Magic Link')->value('setting_value');

        if ($sendLeadRequestEmail) {
            $accessToken = ZohoHelper::getAccessToken();
            if ($user->user_type == 1) {
                $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $user->id);
            } elseif ($user->user_type == 2) {
                $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $user->id);
            }


            if (!empty($zohoId)) {
                if (!empty($user)) {
                    $htmlView = view('emails.login.login_with_magic_link',  [
                        'baseUrl' => config('app.react_base_url'),
                        'siteUrl' => config('app.url'),
                        'name' => $user->name,
                        'token' => $token,
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    if ($user->user_type == 1) {
                        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);
                    } elseif ($user->user_type == 2) {
                        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_QUOTE_CUSTOMERS_API_URL, $zohoId);
                    }

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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

    public static function switchEmailToQuoteAccount($userId)
    {

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

                $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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

    public static function switchEmailToLeadAccount($userId)
    {

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

                $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
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

    public static function unsoldLeadEmailAfter12hrs($data)
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


                    $htmlView = view('emails.customers.unsold12hrs',  [
                        'baseUrl' => config('app.react_base_url'),
                        'name' => $user->name,
                        'lead_name' => $lead->customer->name ?? '',
                        'service_name' => $lead->category->name,
                        'postcode' => $lead->postcode ?? '',
                        'sellerDetails' => $data['sellerDetails'] ?? '',
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
                        'is_frequent_user' => $lead->is_frequent_user ?? '',
                        'is_urgent' => $lead->is_urgent ?? '',
                        'is_high_hiring' => $lead->is_high_hiring ?? '',
                        'phone_verified' => $lead->is_phone_verified ?? '',
                        'hasEnoughCredits' => ($lead->credit_score <= $user->total_credit) ? '1' : '0',
                        'questionsAndAnswers' => $questionsAndAnswers,
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);

                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = $data['subject'];

                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'unsoldLead12HrsEmail',
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

                    $dataE['lead_id'] = $data['leadId'];
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




    public static function sendPlanInvoiceEmail($userId, $invId)
    {
        $sendWelcomeEmail = EmailSetting::where('setting_name', 'Send Plan Invoice Email')->value('setting_value');
        if ($sendWelcomeEmail) {
            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

            if (!empty($zohoId)) {
                $user = User::with(['services.category', 'services.locations'])->where('id', $userId)->first();
                if (!empty($user)) {
                    $invoices = Invoice::where('id', $invId)->first();
                    $htmlView = view('emails.lead_buyers.invoice.lead_buyer_plan_invoice',  [
                        'baseUrl' => config('app.react_base_url'),
                        'siteUrl' => config('app.url'),
                        'name' => $user->name,
                        'email' => $user->email,
                        'invoice_number' => $invoices->invoice_number,
                        'details'       => $invoices->details,
                        'amount'        => $invoices->amount,
                        'vat'           => $invoices->vat,
                        'total_amount'  => $invoices->total_amount,
                        'period'  => $invoices->period,
                        'created_at'    => $invoices->created_at,
                        'jobs' => rand(1, 50),
                    ])->render();
                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Your Invoice from Localists';

                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'sendPlanInvoiceEmail',
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
                    $dataE['setting_name'] = 'Send Plan Invoice Email';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }




    public static function sendNextDayExpiredQuoteEmail($data)
    {

        $sendEmail = EmailSetting::where('setting_name', 'Next Day Expired Quote Email')->value('setting_value');

        if ($sendEmail) {

            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $data['userId']);

            if (!empty($zohoId)) {

                $user = User::find($data['userId']);

                if (!empty($user)) {

                    $htmlView = view('emails.customers.expired_quote.next_day_expired_quote', [
                        'baseUrl' => config('app.react_base_url'),
                        'customerName' => $user->name ?? '',
                        'serviceName' => $data['serviceName'] ?? '',
                        'leadId' => $data['leadId'],
                        'token' => $data['token'],
                    ])->render();

                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);


                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_QUOTE_CUSTOMERS_API_URL, $zohoId);
                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
                    $fromName = CustomHelper::setting_value('zoho_default_from_name', 'Localists.com');
                    $toEmail = $user->email;
                    $subject = $data['subject'] ?? 'Need a hand finishing your project';


                    DB::table('zoho_logs')->insert([
                        'url' => $url,
                        'function_name' => 'sendNextDayExpiredQuoteEmail',
                        'ipaddress' => request()->ip(),
                        'created_at' => now(),
                    ]);


                    $response = Http::withToken($accessToken)
                        ->post($url, [
                            'data' => [
                                [
                                    'from' => [
                                        'email' => $fromEmail,
                                        'user_name' => $fromName
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
                    // dd($data['leadId']);

                    $dataE['user_id'] = $user->id;
                    $dataE['from_email'] = $fromEmail;
                    $dataE['to_email'] = $toEmail;
                    $dataE['lead_id'] = $data['leadId'] ?? null;
                    $dataE['message_id'] = $rel['message_id'];
                    $dataE['subject'] = $subject;
                    $dataE['setting_name'] = 'Next Day Expired Quote Email';
                    $dataE['content'] = $htmlContent;
                    $dataE['zoho_url'] = $url;
                    $dataE['response'] = json_encode($rel);
                    EmailLog::insertGetId($dataE);
                }
            }
        }
    }




    public static function reviewsForHiredLeadBuyer($leadId, $sellerId, $buyerId)
    {
        $sendEmail = EmailSetting::where('setting_name', 'Reviews Hired Lead Buyer')
            ->value('setting_value');

        if (!$sendEmail) {
            return;
        }

        $accessToken = ZohoHelper::getAccessToken();
        if (!$accessToken) {
            Log::error('Zoho access token not available while sending Reviews Hired Lead Buyer.');
            return;
        }

        $lead = LeadRequest::with(['category', 'customer'])->find($leadId);

        if (!$lead) {
            Log::error("Lead not found for ID: {$leadId}");
            return;
        }



        $categoryName  = optional($lead->category)->name;
        $customerName  = optional($lead->customer)->name;
        $customerEmail = optional($lead->customer)->email;
        $customerId    = optional($lead->customer)->id;

        if (!$customerEmail) {
            Log::error("Customer email missing for lead {$leadId}");
            return;
        }

        $subject = ucfirst($customerName) . ", we’d love your quick feedback";

        $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $customerId);
        if (empty($zohoId)) {
            return;
        }

        $seller = User::find($sellerId);

        if (!$seller) {
            Log::error("Seller not found for ID: {$sellerId}");
            return;
        }

        $slug = strtolower(preg_replace('/\s+/', '-', trim($seller->name)));

        $reviewUrl = config('app.react_base_url') . '/view-profile/' . $slug . '/' . $seller->id;

        $htmlView = view('emails.customers.reviews_hired_lead_buyer', [
            'baseUrl' => config('app.react_base_url'),
            'customerName' => $customerName ?? '',
            'sellerName' => $seller->name ?? '',
            'serviceName' => $categoryName ?? '',
            'reviewUrl' => $reviewUrl ?? '',
            'leadId' => $leadId,
        ])->render();

        $htmlContent = (new CssToInlineStyles())->convert($htmlView);

        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_QUOTE_CUSTOMERS_API_URL, $zohoId);
        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
        $fromName  = CustomHelper::setting_value('zoho_default_from_name', 'Localists.com');

        DB::table('zoho_logs')->insert([
            'url' => $url,
            'function_name' => 'reviewsForHiredLeadBuyer',
            'ipaddress' => request()->ip(),
            'created_at' => now(),
        ]);

        $response = Http::withToken($accessToken)->post($url, [
            'data' => [[
                'from' => [
                    'email' => $fromEmail,
                    'user_name' => $fromName
                ],
                'to' => [['email' => $customerEmail]],
                'subject' => $subject,
                'content' => $htmlContent,
                'mail_format' => 'html',
                'org_email' => true
            ]]
        ]);

        $rel = self::getZohoMailResponse($response);
        $messageId = $rel['message_id'] ?? null;

        EmailLog::insertGetId([
            'user_id' => $customerId,
            'from_email' => $fromEmail,
            'to_email' => $customerEmail,
            'lead_id' => $leadId,
            'message_id' => $messageId,
            'subject' => $subject,
            'setting_name' => 'Reviews Hired Lead Buyer',
            'content' => $htmlContent,
            'zoho_url' => $url,
            'response' => json_encode($rel),
        ]);
    }



    public static function leadAcceptedMailToSendCustomer($leadId,  $buyerId, LeadService $leadService)
    {
        $sendEmail = EmailSetting::where('setting_name', 'Lead Accepted')
            ->value('setting_value');

        if (!$sendEmail) {
            return;
        }

        $accessToken = ZohoHelper::getAccessToken();
        if (!$accessToken) {
            Log::error('Zoho access token not available while sending Reviews Hired Lead Buyer.');
            return;
        }

        $lead = LeadRequest::with(['category', 'customer'])->find($leadId);

        $reqPostcode = $lead->postcode;
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

        // Get Seller List
        $result = $leadService->getAllSellers($lead);

        // Replies Count
        $result['response']['repliesListCount'] =
            RecommendedLead::where('buyer_id', $buyerId)
            ->where('lead_id', $lead->id)
            ->count();
        $sellers = collect($result['response']['sellers'] ?? [])
            ->sortByDesc('rating')  // 👈 change 'rating' to your field
            ->take(5)
            ->values();


        $categoryName  = optional($lead->category)->name;
        $customerName  = optional($lead->customer)->name;
        $customerEmail = optional($lead->customer)->email;
        $customerId    = optional($lead->customer)->id;

        if (!$customerEmail) {
            Log::error("Customer email missing for lead {$leadId}");
            return;
        }

        $subject = "Lead Accepted";

        $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $customerId);
        if (empty($zohoId)) {
            return;
        }
        $htmlView = view('emails.customers.lead-accepted-email', [
            'baseUrl' => config('app.react_base_url'),
            'appURL' => config('app.url'),

            'customerName' => $customerName ?? '',
            'serviceName' => $categoryName ?? '',
            'leadId' => $leadId,
            'sellers' => $sellers,

        ])->render();

        $htmlContent = (new CssToInlineStyles())->convert($htmlView);

        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_QUOTE_CUSTOMERS_API_URL, $zohoId);
        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
        $fromName  = CustomHelper::setting_value('zoho_default_from_name', 'Localists.com');

        DB::table('zoho_logs')->insert([
            'url' => $url,
            'function_name' => 'leadAcceptedMailToSendCustomer',
            'ipaddress' => request()->ip(),
            'created_at' => now(),
        ]);

        $response = Http::withToken($accessToken)->post($url, [
            'data' => [[
                'from' => [
                    'email' => $fromEmail,
                    'user_name' => $fromName
                ],
                'to' => [['email' => $customerEmail]],
                'subject' => $subject,
                'content' => $htmlContent,
                'mail_format' => 'html',
                'org_email' => true
            ]]
        ]);

        $rel = self::getZohoMailResponse($response);
        $messageId = $rel['message_id'] ?? null;
        $dataE['user_id'] = $customerId;
        $dataE['from_email'] = $fromEmail;
        $dataE['to_email'] = $customerEmail;
        $dataE['lead_id'] = $leadId ?? null;
        $dataE['message_id'] = $messageId;
        $dataE['subject'] = $subject;
        $dataE['setting_name'] = 'Lead Accepted';
        $dataE['content'] = $htmlContent;
        $dataE['zoho_url'] = $url;
        $dataE['response'] = json_encode($rel);
        EmailLog::insertGetId($dataE);
    }


    public static function notifyCustomerNewProfessionalinPostcode($leadId)
    {
        $sendEmail = EmailSetting::where('setting_name', 'Notify Customer New Professional in Postcode added')
            ->value('setting_value');

        if (!$sendEmail) {
            return;
        }

        $accessToken = ZohoHelper::getAccessToken();
        if (!$accessToken) {
            Log::error('Zoho access token not available while sending Reviews Hired Lead Buyer.');
            return;
        }

        $lead = LeadRequest::with(['category', 'customer'])->find($leadId);

        $serviceName  = optional($lead->category)->name;
        $postCode  = $lead->postcode;
        $customerName  = optional($lead->customer)->name;
        $customerEmail = optional($lead->customer)->email;
        $customerId    = optional($lead->customer)->id;

        if (!$customerEmail) {
            Log::error("Customer email missing for lead {$leadId}");
            return;
        }

        $subject = ($customerName ? ucfirst($customerName) : "Customer") . ", a Verified Professional Is Now Available for Your Quote Request";

        $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $customerId);
        if (empty($zohoId)) {
            return;
        }
        $htmlView = view('emails.customers.notify-new-professional-postcode', [
            'baseUrl' => config('app.react_base_url'),
            'appURL' => config('app.url'),
            'customerName' => $customerName ?? '',
            'serviceName' => $serviceName ?? '',
            'postCode' => $postCode,

        ])->render();

        $htmlContent = (new CssToInlineStyles())->convert($htmlView);

        $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_QUOTE_CUSTOMERS_API_URL, $zohoId);
        $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
        $fromName  = CustomHelper::setting_value('zoho_default_from_name', 'Localists.com');

        DB::table('zoho_logs')->insert([
            'url' => $url,
            'function_name' => 'notifyCustomerNewProfessionalinPostcode',
            'ipaddress' => request()->ip(),
            'created_at' => now(),
        ]);

        $response = Http::withToken($accessToken)->post($url, [
            'data' => [[
                'from' => [
                    'email' => $fromEmail,
                    'user_name' => $fromName
                ],
                'to' => [['email' => $customerEmail]],
                'subject' => $subject,
                'content' => $htmlContent,
                'mail_format' => 'html',
                'org_email' => true
            ]]
        ]);

        $rel = self::getZohoMailResponse($response);
        $messageId = $rel['message_id'] ?? null;
        $dataE['user_id'] = $customerId;
        $dataE['from_email'] = $fromEmail;
        $dataE['to_email'] = $customerEmail;
        $dataE['lead_id'] = $leadId ?? null;
        $dataE['message_id'] = $messageId;
        $dataE['subject'] = $subject;
        $dataE['setting_name'] = 'Notify Customer New Professional in Postcode added';
        $dataE['content'] = $htmlContent;
        $dataE['zoho_url'] = $url;
        $dataE['response'] = json_encode($rel);
        EmailLog::insertGetId($dataE);
    }



    public static function sendWelcomeEmailTest($userId, $password)
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

                    $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
                    $user->update(['remember_token' => $token]);
                    $htmlView = view('emails.lead_buyers.registration.lead_buyer_registration_new',  [
                        'baseUrl' => config('app.react_base_url'),
                        'siteUrl' => config('app.url'),
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $password,
                        'token' => $token,
                        // 'services' => $services
                    ])->render();
                    $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                    $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);

                    $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');
                    $toEmail = $user->email;
                    $subject = 'Welcome to Localists';


                    $attachments = [];
                    $pdfPath = public_path('Localists_Lead_Strategies.pdf');

                    if (file_exists($pdfPath)) {
                        Log::info("CRM zohoId: " . $zohoId);

                        $uploadResponse = Http::withToken($accessToken)
                            ->attach('file', fopen($pdfPath, 'r'), 'Localists_Lead_Strategies.pdf')
                            ->post('https://www.zohoapis.eu/crm/v8/files');

                        Log::info("CRM Upload Response: " . $uploadResponse->body());

                        $attachmentId = $uploadResponse->json('data.0.details.id');

                        if ($attachmentId) {

                            $attachments = [
                                [
                                    'id' => $attachmentId
                                ]
                            ];
                            Log::info('Attachment Added:', ['attachments' => $attachments]);
                        } else {
                            Log::error("CRM attachment upload failed: " . $uploadResponse->body());
                        }
                    }


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
                                    'attachments' => $attachments,
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




    public static function sendLeadInfoToLocallistSalesPerson($userId, $sId, $sellers)
    {
        $sendWelcomeEmail = EmailSetting::where('setting_name', 'Send Lead Info To Localists Sales Person')->value('setting_value');

        if ($sendWelcomeEmail) {
            $accessToken = ZohoHelper::getAccessToken();
            $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $userId);

            if (!empty($zohoId)) {

                $lead = LeadRequest::with(['category', 'customer'])->find($sId);

                $serviceName  = optional($lead->category)->name;
                $postCode  = $lead->postcode;
                $credit_score  = $lead->credit_score;

                $htmlView = view('emails.send_lead_info_to_locallist_sales_person',  [
                    'baseUrl' => config('app.react_base_url'),
                    'siteUrl' => config('app.url'),
                    'serviceName' => $serviceName,
                    'postCode' => $postCode,
                    'CreditValue' => $credit_score,
                    'sellers' => $sellers,
                ])->render();
                $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                $url = ZohoHelper::getSetting(ZohoHelper::EMAIL_QUOTE_CUSTOMERS_API_URL, $zohoId);

                $fromEmail = CustomHelper::setting_value('zoho_default_from_email', 'info@localistscustomers.com');

                $subject = "New Lead Matched on Localists" . ($serviceName ? " - $serviceName" : '');

                DB::table('zoho_logs')->insert([
                    'url' => $url,
                    'function_name' => 'sendLeadInfoToLocallistSalesPerson',
                    'ipaddress' => request()->ip(),
                    'created_at' => now(),
                ]);



                $toEmails = CustomHelper::setting_value('localists_sales_person_emails', '');
                $toArray = collect(explode(',', $toEmails))
                    ->filter()
                    ->map(fn($email) => ['email' => trim($email)])
                    ->toArray();

                // Fetch comma-separated "CC" emails from settings
                $ccEmails = CustomHelper::setting_value('localists_sales_person_cc_emails', '');
                $ccArray = collect(explode(',', $ccEmails))
                    ->filter()
                    ->map(fn($email) => ['email' => trim($email)])
                    ->toArray();

                $response = Http::withToken($accessToken)->post($url, [
                    'data' => [[
                        'from' => [
                            'email' => $fromEmail,
                            'user_name' => CustomHelper::setting_value(
                                'zoho_default_from_name',
                                'Localists.com'
                            )
                        ],
                        'to' => $toArray,
                        'cc' => $ccArray,
                        'subject' => $subject,
                        'content' => $htmlContent,
                        'mail_format' => 'html',
                        'org_email' => true
                    ]]
                ]);


                $rel = self::getZohoMailResponse($response);
                $dataE['user_id'] = $userId;
                $dataE['from_email'] = $fromEmail;
                $dataE['to_email'] = 'michael.marshall@localists.com';
                $dataE['message_id'] = $rel['message_id'];
                $dataE['subject'] = $subject;
                $dataE['setting_name'] = 'Send Lead Info To Localists Sales Person';
                $dataE['content'] = $htmlContent;
                $dataE['zoho_url'] = $url;
                $dataE['response'] = json_encode($rel);
                EmailLog::insertGetId($dataE);
            }
        }
    }
}
