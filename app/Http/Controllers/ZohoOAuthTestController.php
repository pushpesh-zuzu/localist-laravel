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
        $clientId = '1000.4AU5IDFN2PO9X0S25QX5GJMH1NR0YA';
       
        $redirectUri = 'https://localists.com/admin/zoho/callback';
        $scopes = 'ZohoCRM.modules.ALL,ZohoCRM.settings.ALL,ZohoCRM.send_mail.all.CREATE,ZohoMail.attachments.CREATE';

        $url = "https://accounts.zoho.eu/oauth/v2/auth?scope={$scopes}&client_id={$clientId}&response_type=code&access_type=offline&redirect_uri={$redirectUri}";

        return redirect($url);
    }

    // Step 2: Zoho redirects here with ?code=AUTH_CODE
    public function callback(Request $request)
    {
        $code = $request->get('code');
        return "Authorization Code: " . $code;
    }

    // Step 3: Exchange code or refresh token for access token
    public function getAccessToken()
    {
        $clientId = '1000.4AU5IDFN2PO9X0S25QX5GJMH1NR0YA';
        $clientSecret = '4090502db26791407f55c70aeb95f188bdaccc0648';
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
