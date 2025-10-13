<?php

namespace App\Http\Controllers\Api\Google;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\CustomHelper;
use Google\Client as GoogleClient;
use Google\Service\Oauth2;
use Google\Service\MyBusinessAccountManagement;
use Google\Service\MyBusinessBusinessInformation;

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
                'locations'    => self::getGoogleLocations($token['access_token']),
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

    public static function getGoogleLocations($accessToken)
    {
        $client = new GoogleClient();
        $client->setAccessToken($accessToken);

        try {
            // 1️⃣ Get account info
            $myBusinessService = new MyBusinessAccountManagement($client);
            $accounts = $myBusinessService->accounts->listAccounts();

            $locations = [];

            foreach ($accounts->getAccounts() as $account) {
                $accountId = $account->name; // e.g., accounts/123456789

                // Use MyBusinessBusinessInformation instead of old MyBusiness for newer APIs
                $locationsService = new \Google\Service\MyBusinessBusinessInformation($client);
                $locList = $locationsService->accounts_locations->listAccountsLocations($accountId);

                foreach ($locList->getLocations() as $location) {
                    // Extract numeric location ID from full resource name
                    preg_match('/locations\/(\d+)/', $location->name, $matches);
                    $locationId = $matches[1] ?? null;

                    $locations[] = [
                        'account_id'   => $accountId,
                        'location_id'  => $locationId,
                        'name'         => $location->name,
                        'title'        => $location->locationName,
                        'address'      => $location->address ? json_decode(json_encode($location->address), true) : null,
                        'primaryPhone' => $location->primaryPhone ?? null,
                        'websiteUrl'   => $location->websiteUrl ?? null,
                        'latlng'       => $location->latlng ? json_decode(json_encode($location->latlng), true) : null,
                    ];
                }
            }

            return $locations;

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

}


