<?php

namespace App\Helpers\Zoho;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Helpers\CustomHelper;

class ZohoEmails
{
    // protected string $accessToken;
    


    public function __construct()
    {
        // $this->
    }

    public static function sendCustomEmail($userId)
    {
        $accessToken = ZohoHelper::getAccessToken();
    }

    public static function sendWelcomeEmail($userId, $password)
    {
        // $htmlContent = view('emails.welcome', ['user' => $user])->render();
        $accessToken = ZohoHelper::getAccessToken();

        $zohoId = ZohoLeadBuyers::getZohoLeadBuyerId($accessToken, $userId);
    
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


                $htmlView = view('emails.lead_buyers.lead_buyer_registration',  [
                    'baseUrl' => 'https://locallists-react.vercel.app',
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $password,
                    'jobs' => rand(1, 50),
                    'services' => $services
                ])->render();
                $htmlContent = (new CssToInlineStyles())->convert($htmlView);
                $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);        
                $response = Http::withToken($accessToken)
                    ->post($url, [
                        'data' => [
                            [
                                'from' => [
                                    'email' => CustomHelper::setting_value('zoho_default_from_email', 'mikemarshall402@hotmail.com'),
                                    'user_name' => CustomHelper::setting_value('zoho_default_from_name', 'Localist') // Change to your preferred display name
                                ],
                                'to' => [
                                    [
                                        'email' => $user->email
                                    ]
                                ],
                                'subject' => 'Welcome to Localist',
                                'content' => $htmlContent,
                                'mail_format' => 'html'
                            ]
                        ]
                    ]);
            }
            
        }
    }
}