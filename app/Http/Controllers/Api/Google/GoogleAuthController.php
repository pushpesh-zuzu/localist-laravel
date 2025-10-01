<?php

namespace App\Http\Controllers\Api\Google;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\CustomHelper;
use Google\Client as GoogleClient;

class GoogleAuthController extends Controller
{
    public function getAuthToken(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json(['error' => 'Authorization code is required'], 400);
        }

        // Collect config
        $clientId     = trim(CustomHelper::setting_value('google_reviews_client_id','YOUR_GOOGLE_CLIENT_ID'));
        $clientSecret = trim(CustomHelper::setting_value('google_reviews_client_secret', 'YOUR_GOOGLE_CLIENT_SECRET'));
        $redirectUri  = trim(CustomHelper::setting_value('google_reviews_redirect_uri', 'YOUR_GOOGLE_REDIRECT_URI'));

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType("offline");
        $client->setPrompt("consent");

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                // Include full debug info
                $debug = [
                    'google_error'     => $token,
                    'used_client_id'   => $clientId,
                    'used_client_secret' => substr($clientSecret, 0, 6) . '********', // mask
                    'used_redirect_uri'=> $redirectUri,
                    'received_code'    => $code,
                ];
                return $this->sendError($token['error_description'], $debug);
            }

            // Fetch user info if token works
            $oauth2 = new \Google\Service\Oauth2($client);
            $client->setAccessToken($token['access_token']);
            $googleUser = $oauth2->userinfo->get();

            $data = [
                'access_token'  => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_in'    => $token['expires_in'],
                'id_token'      => $token['id_token'] ?? null,
                'redirect_uri'  => $redirectUri,
                'user' => [
                    'id'     => $googleUser->id,
                    'email'  => $googleUser->email,
                    'name'   => $googleUser->name,
                    'avatar' => $googleUser->picture,
                ]
            ];

            return $this->sendResponse('Google authentication successful', $data);

        } catch (\Exception $e) {
            $debug = [
                'exception_message' => $e->getMessage(),
                'used_client_id'    => $clientId,
                'used_client_secret'=> substr($clientSecret, 0, 6) . '********',
                'used_redirect_uri' => $redirectUri,
                'received_code'     => $code,
            ];
            return $this->sendError("catchError: " . $e->getMessage(), $debug);
        }
    }
}
