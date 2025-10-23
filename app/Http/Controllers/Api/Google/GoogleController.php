<?php

namespace App\Http\Controllers\Api\Google;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\CustomHelper;
use Google\Client as GoogleClient;
use Google\Service\Oauth2;
use Google\Service\MyBusinessAccountManagement;
use Google\Service\MyBusinessBusinessInformation;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Log as FacadesLog, Mail, Validator, Http
};
use Carbon\Carbon;

class GoogleController extends Controller
{

    public function getAuthUrl()
    {
        // Collect config
        $clientId     = trim(CustomHelper::setting_value('google_reviews_client_id','YOUR_GOOGLE_CLIENT_ID'));
        $clientSecret = trim(CustomHelper::setting_value('google_reviews_client_secret', 'YOUR_GOOGLE_CLIENT_SECRET'));
        $redirectUri  = trim(CustomHelper::setting_value('google_reviews_redirect_uri', 'YOUR_GOOGLE_REDIRECT_URI'));

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        // Important scopes for Business Profile (reviews)
        $scopes = [
            'openid',
            'email',
            'profile',
            'https://www.googleapis.com/auth/business.manage'
        ];
        $client->addScope($scopes);
        $client->setAccessType('offline');      // to get refresh_token
        $client->setPrompt('consent');          // force showing consent to obtain refresh token

        $authUrl = $client->createAuthUrl();

        return $this->sendResponse('OK', [
            'message' => 'Google authentication URL generated successfully',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'auth_url' => $authUrl
        ]);
    }
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
        // $client->setRedirectUri("postmessage");

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

    public function getGoogleReviews(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $businessName = $request->business_name;
        $userId = $request->user_id;
        $refresh = $request->boolean('refresh', false);
        $apiKey = CustomHelper::setting_value('serpapi_key', 'your_default_serpapi_key');

        // Step 1: Fetch existing reviews from DB
        $existingReviews = DB::table('google_reviews')
            ->where('user_id', $userId)
            ->where('business_name', $businessName)
            ->orderByDesc('review_date')
            ->get();

        // CASE 1: Return from DB if available and no refresh
        if ($existingReviews->isNotEmpty() && !$refresh) {
            $reviews = $existingReviews->map(function ($r) {
                return [
                    'id' => $r->id,
                    'review_id' => $r->review_id,
                    'user_id' => $r->user_id,
                    'business_name' => $r->business_name,
                    'reviewer_name' => $r->reviewer_name,
                    'profile_image' => $r->profile_image,
                    'rating' => (float) $r->rating,
                    'review_text' => $r->review_text,
                    'review_date' => $r->review_date,
                ];
            });

            return $this->sendResponse('Google Reviews', [
                'source' => 'database',
                'total' => $reviews->count(),
                'reviews' => $reviews,
            ]);
        }

        // Step 2: Search place on Google Maps via SerpApi
        $placeSearch = Http::get('https://serpapi.com/search.json', [
            'engine' => 'google_maps',
            'q' => $businessName,
            'api_key' => $apiKey,
        ]);

        $placeId = $placeSearch['place_results']['place_id'] ?? null;
        if (!$placeId) {
            return $this->sendError('Place ID not found for this business.');
        }

        // Step 3: Fetch all reviews with pagination
        $allReviews = collect();
        $nextPageToken = null;

        do {
            $params = [
                'engine' => 'google_maps_reviews',
                'api_key' => $apiKey,
                'place_id' => $placeId,
            ];

            if ($nextPageToken) {
                $params['next_page_token'] = $nextPageToken;
            }

            $response = Http::get('https://serpapi.com/search.json', $params);
            if ($response->failed()) break;

            $data = $response->json();

            $reviews = collect($data['reviews'] ?? [])->map(function ($review) use ($userId, $businessName) {
                $reviewId = $review['review_id'] ?? md5($userId . $businessName . ($review['user']['name'] ?? 'Anonymous') . ($review['snippet'] ?? '') . ($review['iso_date'] ?? now()));
                return [
                    'user_id' => $userId,
                    'business_name' => $businessName,
                    'review_id' => $reviewId,
                    'reviewer_name' => $review['user']['name'] ?? 'Anonymous',
                    'profile_image' => $review['user']['thumbnail'] ?? null,
                    'rating' => isset($review['rating']) ? (float) preg_replace('/[^0-9.]/', '', $review['rating']) : null,
                    'review_text' => $review['snippet'] ?? '',
                    'review_date' => isset($review['iso_date']) ? Carbon::parse($review['iso_date']) : now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->filter(fn($r) => $r['review_id']);

            $allReviews = $allReviews->merge($reviews);

            $nextPageToken = $data['serpapi_pagination']['next_page_token'] ?? null;
            if ($nextPageToken) sleep(2);
        } while ($nextPageToken);

        // Step 4: Delete existing reviews if refresh = true
        if ($refresh) {
            DB::table('google_reviews')
                ->where('user_id', $userId)
                ->where('business_name', $businessName)
                ->delete();
        }

        // Step 5: Upsert fetched reviews (no duplicates)
        if ($allReviews->isNotEmpty()) {
            DB::table('google_reviews')->upsert(
                $allReviews->toArray(),
                ['review_id', 'user_id', 'business_name'],
                ['reviewer_name', 'profile_image', 'rating', 'review_text', 'review_date', 'updated_at']
            );
        }

        // Step 6: Fetch final reviews from DB (consistent structure)
        $finalReviews = DB::table('google_reviews')
            ->where('user_id', $userId)
            ->where('business_name', $businessName)
            ->orderByDesc('review_date')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'review_id' => $r->review_id,
                    'user_id' => $r->user_id,
                    'business_name' => $r->business_name,
                    'reviewer_name' => $r->reviewer_name,
                    'profile_image' => $r->profile_image,
                    'rating' => (float) $r->rating,
                    'review_text' => $r->review_text,
                    'review_date' => $r->review_date,
                ];
            });

        $source = $existingReviews->isEmpty() ? 'serpapi_initial' : 'serpapi_refreshed';

        return $this->sendResponse('Google Reviews', [
            'source' => $source,
            'total' => $finalReviews->count(),
            'reviews' => $finalReviews,
        ]);
    }



}


