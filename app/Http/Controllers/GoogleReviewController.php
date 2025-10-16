<?php

namespace App\Http\Controllers;

use App\Helpers\CustomHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Seller;
use App\Models\SellerGoogleToken;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\MyBusinessBusinessInformation;
use Google\Service\MyBusiness;

class GoogleReviewController extends Controller
{
    /**
     * Step 1: Redirect seller to Google OAuth
     */
    public function redirectToGoogle()
    {
        $client = new GoogleClient();

        $clientId     = trim(CustomHelper::setting_value('google_reviews_client_id', 'YOUR_GOOGLE_CLIENT_ID'));
        $clientSecret = trim(CustomHelper::setting_value('google_reviews_client_secret', 'YOUR_GOOGLE_CLIENT_SECRET'));
        $redirectUri  = trim(CustomHelper::setting_value('google_reviews_redirect_uri', 'YOUR_GOOGLE_REDIRECT_URI'));


        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->addScope('https://www.googleapis.com/auth/business.manage');
        $client->setAccessType('offline'); // to get refresh token
        $client->setPrompt('consent');

        return redirect($client->createAuthUrl());
    }

    /**
     * Step 2: Handle Google OAuth Callback
     */
    public function handleGoogleCallback(Request $request)
    {
        $code = $request->get('code');
        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization code missing.',
            ], 400);
        }

        try {
            $client = new GoogleClient();

            $clientId     = trim(CustomHelper::setting_value('google_reviews_client_id', 'YOUR_GOOGLE_CLIENT_ID'));
            $clientSecret = trim(CustomHelper::setting_value('google_reviews_client_secret', 'YOUR_GOOGLE_CLIENT_SECRET'));
            $redirectUri  = trim(CustomHelper::setting_value('google_reviews_redirect_uri', 'YOUR_GOOGLE_REDIRECT_URI'));


            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri($redirectUri);

            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get access token.',
                    'error' => $token
                ], 400);
            }

            $accessToken = $token['access_token'];
            $refreshToken = $token['refresh_token'] ?? null;
            $expiresAt = isset($token['expires_in']) ? Carbon::now()->addSeconds($token['expires_in']) : null;

            // Save tokens in DB
            // $seller = Auth::user();
            // SellerGoogleToken::updateOrCreate(
            //     ['seller_id' => $seller->id],
            //     [
            //         'access_token' => $accessToken,
            //         'refresh_token' => $refreshToken,
            //         'token_expires_at' => $expiresAt
            //     ]
            // );

            return response()->json([
                'success' => true,
                'message' => 'Google Business Profile connected successfully!',
                'data' => $token
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during Google OAuth',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Step 3: Fetch Google reviews for seller
     */
    public function getReviews(Request $request)
    {
        $seller = Auth::user();

        $tokenData = SellerGoogleToken::where('seller_id', $seller->id)->first();

        if (!$tokenData) {
            return response()->json([
                'success' => false,
                'message' => 'Google token not found for this seller.'
            ], 404);
        }

        try {
            $client = new GoogleClient();
            $client->setAccessToken([
                'access_token' => $tokenData->access_token,
                'refresh_token' => $tokenData->refresh_token,
                'expires_in' => $tokenData->token_expires_at ? $tokenData->token_expires_at->diffInSeconds(now()) : 3600
            ]);

            // Refresh token if expired
            if ($client->isAccessTokenExpired() && $tokenData->refresh_token) {
                $client->fetchAccessTokenWithRefreshToken($tokenData->refresh_token);
                $tokenData->access_token = $client->getAccessToken()['access_token'];
                $tokenData->token_expires_at = Carbon::now()->addSeconds($client->getAccessToken()['expires_in']);
                $tokenData->save();
            }

            $myBusinessService = new MyBusiness($client);

            // Get accounts
            $accountsList = $myBusinessService->accounts->listAccounts();
            if (empty($accountsList->getAccounts())) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Google Business accounts found for this seller.'
                ], 404);
            }

            $allReviews = [];

            foreach ($accountsList->getAccounts() as $account) {
                $accountName = $account->name; // e.g., accounts/123456

                // Get locations
                $locations = $myBusinessService->accounts_locations->listAccountsLocations($accountName);

                foreach ($locations->getLocations() as $location) {
                    $locationId = $location->name; // e.g., accounts/123456/locations/987654
                    $locationName = $location->locationName ?? 'Unknown Location';

                    // Get reviews
                    $reviews = $myBusinessService->accounts_locations_reviews->listAccountsLocationsReviews($locationId);

                    foreach ($reviews->getReviews() ?? [] as $review) {
                        $allReviews[] = [
                            'location_name' => $locationName,
                            'review_id' => $review->reviewId ?? null,
                            'reviewer_name' => $review->reviewer->displayName ?? 'Anonymous',
                            'rating' => $review->starRating ?? null,
                            'review_text' => $review->comment ?? '',
                            'review_date' => $review->createTime ?? null,
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'reviews' => $allReviews
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching Google reviews',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
