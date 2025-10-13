<?php

namespace App\Http\Controllers\Api\Google;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\CustomHelper;
use Google\Client as GoogleClient;
use Google\Service\Oauth2;
use Google\Service\MyBusinessAccountManagement;

class GoogleAuthController extends Controller
{
    public function getAuthToken(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return $this->sendError('Authorization code is required');
        }

        // Collect config
        $clientId     = trim(CustomHelper::setting_value('google_reviews_client_id','YOUR_GOOGLE_CLIENT_ID'));
        $clientSecret = trim(CustomHelper::setting_value('google_reviews_client_secret', 'YOUR_GOOGLE_CLIENT_SECRET'));
        $redirectUri  = trim(CustomHelper::setting_value('google_reviews_redirect_uri', 'YOUR_GOOGLE_REDIRECT_URI'));

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType("offline"); // needed for refresh token
        $client->setPrompt("consent"); // force prompt to get refresh token
        $client->setRedirectUri("postmessage");

        try {
            // Exchange code for tokens
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                $debug = [
                    'google_error'      => $token,
                    'used_client_id'    => $clientId,
                    'used_redirect_uri' => $redirectUri,
                    'received_code'     => $code,
                ];
                return $this->sendError($token['error_description'] ?? 'Google auth error', $debug);
            }

            $data = [
                'access_token'  => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_in'    => $token['expires_in'],
                'profile'      => self::getGoogleProfileData($token['access_token']),
            ];
           
            /**
             * 🔐 TODO:
             * Save $token['refresh_token'] in your DB against the logged-in user
             * so you can refresh access tokens later without asking the user again.
             */

            return $this->sendResponse('Google authentication successful', $data);

        } catch (\Exception $e1) {
            $debug1 = [
                'exception_message' => $e1->getMessage(),
                'used_redirect_uri' => $redirectUri,
                'received_code'     => $code,
            ];
            return $this->sendError("catchError: " . $e1->getMessage(), $debug1);
        }
    }

    public static function getGoogleProfileData($accessToken)
    {
        $client = new GoogleClient();
        $client->setAccessToken($accessToken);

        try {
            $oauth2 = new Oauth2($client);
            $googleUser = $oauth2->userinfo->get();

            return [
                'id' => $googleUser->id,
                'email' => $googleUser->email,
                'name' => $googleUser->name,
                'avatar' => $googleUser->picture
            ];

        } catch (\Exception $e) {
            return null;
        }
    }
}


