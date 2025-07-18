<?php

namespace App\Helpers\Zoho;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Illuminate\Support\Facades\Http;
use App\Helpers\CustomHelper;
use App\Models\User;
use App\Models\EmailLog;

class ZohoEmails
{
    // protected string $accessToken;

    public static function sendWelcomeEmail($userId, $password)
    {
        // $htmlContent = view('emails.welcome', ['user' => $user])->render();
        $accessToken = ZohoHelper::getAccessToken();

        $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

        if(!empty($zohoId)){
            $user = User::with(['services.category','services.locations'])->where('id', $userId)->first();
            // echo "<pre>";
            // print_r($user->toArray());
            // exit;

            if(!empty($user)){

                $services = [];
                foreach($user->services as $s){
                    $sl = $s->category->name .' - ';
                    foreach($s->locations as $index => $l){
                        if($index > 0){
                            $sl .= ', ';
                        }
                        $sl .= $l->miles .' miles from ' .$l->postcode;
                    }
                    array_push($services, $sl);
                }
                // echo "<pre>";
                // print_r($services);
                // exit;


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
                $dataE['content'] = $htmlContent;
                $dataE['zoho_url'] = $url;
                $dataE['response'] = json_encode($rel);
                EmailLog::insertGetId($dataE);

            }

        }
    }

    public static function sendEncouragementEmail($userId)
    {

        $accessToken = ZohoHelper::getAccessToken();

        $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

        if(!empty($zohoId)){
            $user = User::with(['services.category','services.locations'])->where('id', $userId)->first();

            if(!empty($user)){


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
                $dataE['content'] = $htmlContent;
                $dataE['zoho_url'] = $url;
                $dataE['response'] = json_encode($rel);
                EmailLog::insertGetId($dataE);

            }

        }
    }


    private static function getZohoMailResponse($response){
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
