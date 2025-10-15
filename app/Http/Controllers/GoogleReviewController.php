<?php

// namespace App\Http\Controllers\Api;


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Seller; // Your seller model
// use App\Models\Review; // Your review model

class GoogleReviewController extends Controller
{

//     GOOGLE_CLIENT_ID=your_google_client_id
// GOOGLE_CLIENT_SECRET=your_google_client_secret
// GOOGLE_REDIRECT_URL=http://localhost:8000/google/callback





    // Step 3a: Redirect seller to Google OAuth
    public function redirectToGoogle()
    {
        $client_id = '98795367891-erp3k9241b3k152r4eb88m8mnpmc9eui.apps.googleusercontent.com';
        $redirect_uri = "https://dev.localists.com/google/callback";
        $scope = urlencode('https://www.googleapis.com/auth/business.manage');

        $auth_url = "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}&scope={$scope}&access_type=offline&prompt=consent";

        return redirect($auth_url);
    }






public function handleGoogleCallback(Request $request)
{
    $code = $request->get('code');

    if (!$code) {
        return response()->json([
            'success' => false,
            'message' => 'Authorization failed!',
        ], 400);
    }

    // Exchange authorization code for tokens
    $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => '98795367891-erp3k9241b3k152r4eb88m8mnpmc9eui.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect_uri' => 'https://dev.localists.com/google/callback',
        'grant_type' => 'authorization_code',
    ]);

    $data = $response->json();

    if (!isset($data['access_token'])) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to get access token!',
            'error' => $data,
        ], 400);
    }

    $access_token = $data['access_token'];
    $refresh_token = $data['refresh_token'] ?? null;
    $expires_in = $data['expires_in'] ?? null;

    // ✅ Optionally save tokens for logged-in seller
    /*
    $seller = Auth::user();
    $seller->google_access_token = $access_token;
    $seller->google_refresh_token = $refresh_token;
    $seller->save();
    */

    // ✅ Return token data for Postman or frontend
    return response()->json([
        'success' => true,
        'message' => 'Google Business Profile connected successfully!',
        'data' => [
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'expires_in' => $expires_in,
        ],
    ]);
}









    // Step 3b: Handle callback from Google
    // public function handleGoogleCallback(Request $request)
    // {
    //     $code = $request->get('code');

    //     if (!$code) {
    //         return redirect('/')->with('error', 'Authorization failed!');
    //     }

    //     // Exchange code for access token
    //     $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
    //         'code' => $code,
    //         'client_id' => '98795367891-erp3k9241b3k152r4eb88m8mnpmc9eui.apps.googleusercontent.com',
    //         'client_secret' => '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',
    //         'redirect_uri' => "https://dev.localists.com/google/callback",
    //         'grant_type' => 'authorization_code',
    //     ]);

    //     $data = $response->json();

    //     if (!isset($data['access_token'])) {
    //         return redirect('/')->with('error', 'Failed to get access token!');
    //     }

    //     $access_token = $data['access_token'];
    //     $refresh_token = $data['refresh_token'] ?? null;

    //     // // Store tokens for the seller
    //     // $seller = Auth::user(); // Make sure seller is logged in
    //     // $seller->google_access_token = $access_token;
    //     // $seller->google_refresh_token = $refresh_token;
    //     // $seller->save();

    //     // // Optionally fetch reviews immediately
    //     // $this->fetchReviews($seller);


    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Google Business Profile connected successfully!',
    //         'data' => [
    //             'access_token' => $access_token,
    //             'refresh_token' => $refresh_token,
    //             'expires_in' => $googleUser->expiresIn ?? null,
    //             'email' => $googleUser->getEmail(),
    //             'name' => $googleUser->getName(),
    //         ],
    //     ]);

    //     return redirect('/dashboard')->with('success', 'Google Business Profile connected!');
    // }





   
public function getReviews(Request $request)
{
    $request->validate([
        'google_access_token' => 'required|string',
    ]);

    $access_token = $request->input('google_access_token');
    $allReviews = [];

    try {
        // 1️⃣ Get the seller’s linked Google accounts
        $accountsResponse = Http::withToken($access_token)
            ->get('https://mybusiness.googleapis.com/v4/accounts')
            ->json();

        if (empty($accountsResponse['accounts'][0]['name'])) {
            return response()->json([
                'success' => false,
                'message' => 'No Google Business accounts found for this token.',
                'raw_response' => $accountsResponse,
            ], 404);
        }

        $accountId = $accountsResponse['accounts'][0]['name']; // Example: accounts/123456

        // 2️⃣ Get locations under that account
        $locationsResponse = Http::withToken($access_token)
            ->get("https://mybusiness.googleapis.com/v4/$accountId/locations")
            ->json();

        if (empty($locationsResponse['locations'])) {
            return response()->json([
                'success' => false,
                'message' => 'No locations found under this Google account.',
                'raw_response' => $locationsResponse,
            ], 404);
        }

        // 3️⃣ Loop through locations → Fetch reviews
        foreach ($locationsResponse['locations'] as $location) {
            $locationId = $location['name'];
            $locationName = $location['locationName'] ?? 'Unknown Location';

            $reviewsResponse = Http::withToken($access_token)
                ->get("https://mybusiness.googleapis.com/v4/$locationId/reviews")
                ->json();

            if (!empty($reviewsResponse['reviews'])) {
                foreach ($reviewsResponse['reviews'] as $review) {
                    $allReviews[] = [
                        'location_name' => $locationName,
                        'review_id' => $review['reviewId'] ?? null,
                        'reviewer_name' => $review['reviewer']['displayName'] ?? 'Anonymous',
                        'rating' => $review['starRating'] ?? null,
                        'review_text' => $review['comment'] ?? '',
                        'review_date' => $review['createTime'] ?? null,
                    ];
                }
            }
        }

        // ✅ Return all reviews
        return response()->json([
            'success' => true,
            'reviews' => $allReviews,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching Google reviews',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    // Step 3c: Fetch seller reviews
    // public function fetchReviews($seller)
    // {
    //     $access_token = $seller->google_access_token;

    //     // 1. Get Google accounts linked to this seller
    //     $accounts = Http::withToken($access_token)
    //         ->get('https://mybusiness.googleapis.com/v4/accounts')
    //         ->json();

    //     if (!isset($accounts['accounts'][0]['name'])) return;

    //     $accountId = $accounts['accounts'][0]['name']; // Example: accounts/123456

    //     // 2. Get locations for this account
    //     $locations = Http::withToken($access_token)
    //         ->get("https://mybusiness.googleapis.com/v4/$accountId/locations")
    //         ->json();

    //     foreach ($locations['locations'] ?? [] as $location) {
    //         $locationId = $location['name']; // accounts/123456/locations/987654

    //         // 3. Fetch reviews for each location
    //         $reviews = Http::withToken($access_token)
    //             ->get("https://mybusiness.googleapis.com/v4/$locationId/reviews")
    //             ->json();

    //         // foreach ($reviews['reviews'] ?? [] as $review) {
    //         //     Review::updateOrCreate(
    //         //         [
    //         //             'review_id' => $review['reviewId'],
    //         //             'seller_id' => $seller->id,
    //         //         ],
    //         //         [
    //         //             'reviewer_name' => $review['reviewer']['displayName'] ?? 'Anonymous',
    //         //             'rating' => $review['starRating'] ?? null,
    //         //             'review_text' => $review['comment'] ?? '',
    //         //             'review_date' => $review['createTime'] ?? now(),
    //         //             'source' => 'google',
    //         //         ]
    //         //     );
    //         // }
    //     }
    // }
}
