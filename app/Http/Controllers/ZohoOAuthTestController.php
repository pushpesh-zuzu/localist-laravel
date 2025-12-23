<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoOAuthTestController extends Controller
{
    // Step 1: Redirect to Zoho auth URL
    public function authorize()
    {
        $clientId = '1000.E3TGKUVP6ZJK7ZNZ7K839BOTP4EAWP';

        $redirectUri = 'https://localists.com/admin/zoho/callback';
        $scopes = 'ZohoCampaigns.contacts.CREATE,ZohoCampaigns.contacts.READ,ZohoCampaigns.contacts.UPDATE,ZohoCampaigns.lists.READ,ZohoCampaigns.campaigns.CREATE,ZohoCampaigns.campaigns.READ,ZohoCampaigns.campaigns.UPDATE,ZohoCampaigns.reports.READ';

        $url = "https://accounts.zoho.eu/oauth/v2/auth?scope={$scopes}&client_id={$clientId}&response_type=code&access_type=offline&redirect_uri={$redirectUri}";

        return redirect($url);
    }



    // Step 2: Zoho redirects here with ?code=AUTH_CODE
    public function callback(Request $request)
    {
        $code = $request->get('code');

        $response = Http::asForm()->post('https://accounts.zoho.eu/oauth/v2/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => '1000.E3TGKUVP6ZJK7ZNZ7K839BOTP4EAWP',
            'client_secret' => '479e4fe44f4bfc634eb134f913465d881deeb04b2d',
            'redirect_uri'  => 'https://localists.com/admin/zoho/callback',
            'code'          => $code,
        ]);

        return $response->json();
    }



    // Step 3: Exchange code or refresh token for access token
    public function getAccessToken()
    {
        $clientId = '1000.E3TGKUVP6ZJK7ZNZ7K839BOTP4EAWP';
        $clientSecret = '479e4fe44f4bfc634eb134f913465d881deeb04b2d';
        $redirectUri = 'https://localists.com/admin/zoho/callback';
        $refreshToken = env('ZOHO_REFRESH_TOKEN'); // save refresh token after first exchange

        $response = Http::asForm()->post('https://accounts.zoho.eu/oauth/v2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            Log::info('Zoho Access Token: ' . $data['access_token']);
            return $data;
        }

        Log::error('Zoho Access Token Error: ' . $response->body());
        return $response->body();
    }
}
