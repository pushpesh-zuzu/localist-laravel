<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\FacebookPage;
use App\Helpers\CustomHelper;
use Carbon\Carbon;

class FacebookController extends Controller
{

    public function exchangeToken(Request $request)
    {
        $request->validate([
            'user_access_token' => 'required|string',
            'page_id' => 'nullable|string'
        ]);

        $userToken = $request->input('user_access_token');
        $pageIdFilter = $request->input('page_id');

        $appId = CustomHelper::setting_value('FACEBOOK_APP_ID');
        $appSecret = CustomHelper::setting_value('FACEBOOK_APP_SECRET');
        $graphVersion = CustomHelper::setting_value('FB_GRAPH_VERSION');

        // 1) Exchange user token for long-lived token
        $exchange = Http::get("https://graph.facebook.com/{$graphVersion}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $userToken,
        ]);

        if ($exchange->failed()) {
            $details = json_decode($exchange->body(), true) ?: ['raw_body' => $exchange->body()];

            return $this->sendError(__('Failed to exchange token'), [
                'details' => $details
            ], 400);
        }

        $longLivedUser = $exchange->json();
        $userLongToken = $longLivedUser['access_token'] ?? $userToken;
        $expiresIn = $longLivedUser['expires_in'] ?? null;

        //Get pages the user manages
        $pagesResp = Http::get("https://graph.facebook.com/{$graphVersion}/me/accounts", [
            'access_token' => $userLongToken,
            'fields' => 'id,name,access_token'
        ]);

        if ($pagesResp->failed()) {
            $details = json_decode($pagesResp->body(), true) ?: ['raw_body' => $pagesResp->body()];
            return $this->sendError(__('Failed to fetch pages'), [
                'details' => $details
            ], 400);
        }

        $pages = $pagesResp->json('data', []);

        // filter if a specific page_id requested
        if ($pageIdFilter) {
            $pages = array_values(array_filter($pages, fn($p) => ($p['id'] ?? '') === $pageIdFilter));
        }

        if (empty($pages)) {
            return $this->sendError(__('No pages found'), [], 404);
        }

        // pick first matching page
        $page = $pages[0];
        $pageAccessToken = $page['access_token'] ?? null;
        $pageId = $page['id'] ?? null;
        $pageName = $page['name'] ?? null;

        // store encrypted access token
        FacebookPage::updateOrCreate(['page_id' => $pageId], [
            'seller_id' => $request->user_id,
            'name' => $pageName,
            'access_token' => encrypt($pageAccessToken),
            'token_expires_at' => $expiresIn ? Carbon::now()->addSeconds($expiresIn) : Carbon::now()->addDays(60),
            'meta' => ['stored_at' => now()->toDateTimeString()]
        ]);

        return $this->sendResponse([
            'page_id' => $pageId,
            'page_name' => $pageName,
            'access_token' => $pageAccessToken
        ]);
    }  


    public function getSellerToken(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer'
        ]);

        $seller_id = $request->input('user_id');
        $page = FacebookPage::where('seller_id', $seller_id)->first();
        if (!$page) {
            return $this->sendError('No page token found for this seller.');
        }

        try {
            $token = decrypt($page->access_token);
        } catch (\Exception $e) {
            $token = null;
        }

        $expired = 'yes';

        if ($page->token_expires_at) {
            $expired = Carbon::parse($page->token_expires_at)->isPast() ? 'yes' : 'no';
        }

        $finalRows = [
            'page_id' => $page->page_id,
            'page_name' => $page->name,
            'page_access_token' => $token,
            'expired' => $expired,
            'token_expires_at' => $page->token_expires_at?->toDateTimeString()
        ];

        return $this->sendResponse($finalRows);
    }



    public function fetchReviews1(Request $request)
{
    $pageId = '830701720126367';
    $page = FacebookPage::where('page_id', $pageId)->first();
    if (!$page) {
        return response()->json(['error' => 'Page not found'], 404);
    }

    try {
        $accessToken = decrypt('eyJpdiI6InZnM08zekxSSUJVaXMzbnluTTlyOGc9PSIsInZhbHVlIjoibnFDdjRHN01YMGNzSFAyRnA4NG03TWxUMi94MjlnY2JZY3B5c1Njb09ua1VZVFI3dW5UejgyWlRlUW9GVHVnYk5sMERPVVdVTG96dkFac0hTeDZFd3JyY0U3WERjTGk3VXFaOEhDaFpiUWdEZGladGRWRDI0VHp2b2F0VERoWWhzWDVDS0xCYjN2ckYxN3VnZW1BMnRMNEx2SzJvd2JGeVdvNVduSTc3NEtSWjd5ZUZHQVJROWRtTmdmanh0WGZZaG81OHI0QytodkZDTnFYTlM1bkZTWWc5cGY1Slg3RS9xb3IyanQyKzVTZWs5SHdSNzJPMUZ6RGs4cXprY2R0V2gwTlBCcVJXcllzdlFkYnBMQ25iUEhwMHBzNmllWkxVNkIyaWNMOXhtUjg9IiwibWFjIjoiMjJiOGQ2YjFjODUzNDYwNDVmMzNmMzI3NWFjOTQyNDA4N2FiYzNhNmUyZmRkMWY0MDBkZjk4MDJkMGU5NTVlNyIsInRhZyI6IiJ9');
    } catch (\Exception $e) {
        return response()->json(['error' => 'Token decrypt failed'], 500);
    }

    $graphVersion = env('FB_GRAPH_VERSION', 'v21.0');
    $fields = 'id,review_text,created_time,rating,recommendation_type,reviewer{name,id}';
    $url = "https://graph.facebook.com/{$graphVersion}/{$pageId}/ratings?fields={$fields}&limit=25&access_token={$accessToken}";

    $all = [];
    while ($url) {
        $resp = Http::get($url);
        if ($resp->failed()) {
            return response()->json(['error' => 'Graph API error', 'details' => $resp->body()], 400);
        }

        $body = $resp->json();

        foreach ($body['data'] ?? [] as $r) {
            $all[] = [
                'review_id' => $r['id'] ?? null,
                'reviewer_name' => $r['reviewer']['name'] ?? 'Anonymous',
                'review_text' => $r['review_text'] ?? '',
                'rating' => $r['rating'] ?? null,
                'recommendation_type' => $r['recommendation_type'] ?? '',
                'review_created_time' => isset($r['created_time']) ? Carbon::parse($r['created_time']) : null
            ];

            // Optional: Save to database
            /*
            FacebookReview::updateOrCreate(
                ['review_id' => $r['id']],
                [
                    'page_id' => $pageId,
                    'reviewer_name' => $r['reviewer']['name'] ?? 'Anonymous',
                    'review_text' => $r['review_text'] ?? '',
                    'rating' => $r['rating'] ?? null,
                    'review_created_time' => isset($r['created_time']) ? Carbon::parse($r['created_time']) : null
                ]
            );
            */
        }

        $url = $body['paging']['next'] ?? null;
    }

    return response()->json(['count' => count($all), 'reviews' => $all]);
}



public function fetchReviews(Request $request)
{
    $pageId = '830701720126367';
    $page = FacebookPage::where('page_id', $pageId)->first();
    if (!$page) {
        return response()->json(['error' => 'Page not found'], 404);
    }

    try {
       $accessToken = decrypt('eyJpdiI6InZnM08zekxSSUJVaXMzbnluTTlyOGc9PSIsInZhbHVlIjoibnFDdjRHN01YMGNzSFAyRnA4NG03TWxUMi94MjlnY2JZY3B5c1Njb09ua1VZVFI3dW5UejgyWlRlUW9GVHVnYk5sMERPVVdVTG96dkFac0hTeDZFd3JyY0U3WERjTGk3VXFaOEhDaFpiUWdEZGladGRWRDI0VHp2b2F0VERoWWhzWDVDS0xCYjN2ckYxN3VnZW1BMnRMNEx2SzJvd2JGeVdvNVduSTc3NEtSWjd5ZUZHQVJROWRtTmdmanh0WGZZaG81OHI0QytodkZDTnFYTlM1bkZTWWc5cGY1Slg3RS9xb3IyanQyKzVTZWs5SHdSNzJPMUZ6RGs4cXprY2R0V2gwTlBCcVJXcllzdlFkYnBMQ25iUEhwMHBzNmllWkxVNkIyaWNMOXhtUjg9IiwibWFjIjoiMjJiOGQ2YjFjODUzNDYwNDVmMzNmMzI3NWFjOTQyNDA4N2FiYzNhNmUyZmRkMWY0MDBkZjk4MDJkMGU5NTVlNyIsInRhZyI6IiJ9');
    } catch (\Exception $e) {
        return response()->json(['error' => 'Token decrypt failed'], 500);
    }

    $graphVersion = env('FB_GRAPH_VERSION', 'v21.0');
    $fields = 'id,review_text,created_time,rating,recommendation_type,reviewer{name,id}';
    $url = "https://graph.facebook.com/{$graphVersion}/{$pageId}/ratings?fields={$fields}&limit=25&access_token={$accessToken}";

    $all = [];
    while ($url) {
        $resp = Http::get($url);
        if ($resp->failed()) {
            return response()->json([
                'error' => 'Graph API error',
                'details' => $resp->body()
            ], 400);
        }

        $body = $resp->json();

        foreach ($body['data'] ?? [] as $r) {
            $all[] = [
                'review_id' => $r['id'] ?? null,
                'reviewer_name' => $r['reviewer']['name'] ?? null,
                'review_text' => $r['review_text'] ?? '',
                'rating' => $r['rating'] ?? null,
                'recommendation_type' => $r['recommendation_type'] ?? '',
                'review_created_time' => isset($r['created_time']) ? Carbon::parse($r['created_time']) : null,
                'missing_reviewer' => !isset($r['reviewer']),  // true if reviewer not returned
                'missing_rating' => !isset($r['rating']),      // true if rating not returned
            ];
        }

        $url = $body['paging']['next'] ?? null;
    }

    return response()->json([
        'count' => count($all),
        'reviews' => $all
    ]);
}

        
}
