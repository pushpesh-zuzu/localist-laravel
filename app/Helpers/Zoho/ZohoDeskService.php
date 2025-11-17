<?php

namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CustomHelper;

class ZohoDeskService
{

    protected $clientId;
    protected $clientSecret;
    protected $refreshToken;
    protected $region;

    public function __construct()
    {
        $this->clientId     = CustomHelper::setting_value('zoho_desk_client_id');
        $this->clientSecret = CustomHelper::setting_value('zoho_desk_client_secret');
        $this->refreshToken = CustomHelper::setting_value('zoho_desk_refresh_token');
        $this->region       =  'eu';
    }

    public function getAccessToken()
    {
        // Check if we already have a token in cache
        if (Cache::has('zoho_desk_access_token')) {
            return Cache::get('zoho_desk_access_token');
        }

        // Otherwise generate a new one using refresh token
        $url = "https://accounts.zoho.{$this->region}/oauth/v2/token";

        $response = Http::asForm()->post($url, [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
        ]);

        if ($response->failed()) {
            \Log::error('ZohoDesk Token Refresh Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $accessToken = $response->json()['access_token'];

        // Cache token for 55 minutes (Zoho token expires in 1 hour)
        Cache::put('zoho_desk_access_token', $accessToken, now()->addMinutes(55));

        return $accessToken;
    }
}
