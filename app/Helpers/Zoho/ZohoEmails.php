<?php

namespace App\Helpers\Zoho;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class ZohoEmails
{
    // protected string $accessToken;
    


    public function __construct()
    {
        // $this->
    }

    public static function sendWelcomeEmail($userId)
    {
        // $htmlContent = view('emails.welcome', ['user' => $user])->render();
        $accessToken = ZohoHelper::getAccessToken();

        $zohoId = ZohoLeadBuyers::getZohoLeadBuyerId($accessToken, 68);
        // print_r($zohoId);exit;
        $url = ZohoHelper::getUrl(ZohoHelper::EMAIL_LEAD_BUYERS_API_URL, $zohoId);
        print_r($url);
        
        $a = Http::withToken($accessToken)
            ->post($url, [
                'fromAddress' => 'mikemarshall402@hotmail.com',
                'toAddress' => 'pushpeshsh@zuzucodes.com',
                'subject' => 'Welcome!',
                'content' => 'Welcome to our platform, asdsd',
            ]);

        print_r(json_decode($a, true));
    }
}