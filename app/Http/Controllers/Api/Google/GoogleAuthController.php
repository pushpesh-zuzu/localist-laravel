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

        $client = new GoogleClient();
        $client->setClientId(CustomHelper::setting_value('google_reviews_client_id','YOUR_GOOGLE_CLIENT_ID'));
        $client->setClientSecret(CustomHelper::setting_value('google_reviews_client_secret', 'YOUR_GOOGLE_CLIENT_SECRET'));

        // Get and trim redirect URI
        $redirectUri = trim(CustomHelper::setting_value('google_reviews_redirect_uri', 'YOUR_GOOGLE_REDIRECT_URI'));
        $client->setRedirectUri($redirectUri);

        $client->setAccessType("offline"); // for refresh token
        $client->setPrompt("consent");

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                $token['used_redirect_uri'] = $redirectUri;
                return $this->sendError($token['error_description'], $token);
            }

            // Optionally fetch user info
            $oauth2 = new \Google\Service\Oauth2($client);
            $client->setAccessToken($token['access_token']);
            $googleUser = $oauth2->userinfo->get();

            $data = [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_in' => $token['expires_in'],
                'id_token' => $token['id_token'] ?? null,
                'redirect_uri' => $redirectUri,
                'user' => [
                    'id' => $googleUser->id,
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                    'avatar' => $googleUser->picture,
                ]
            ];

            return $this->sendResponse('Google authentication successful', $data);

        } catch (\Exception $e) {
            return $this->sendError("catchError: " . $e->getMessage(), [
                'redirect_uri' => "[" .$redirectUri ."]"
            ]);
        }
    }
}
