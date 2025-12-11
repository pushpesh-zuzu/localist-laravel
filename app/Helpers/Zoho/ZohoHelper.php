<?php

namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CustomHelper;
use App\Models\AbandonedUser;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ZohoHelper
{
    // public const  EMAIL_LEAD_BUYERS_API_URL = 'https://crmsandbox.zoho.eu/crm/v8/Lead_Buyer_Registration/{ZOHO_ID}/actions/send_mail';

    // public const  EMAIL_QUOTE_CUSTOMERS_API_URL = 'https://crmsandbox.zoho.eu/crm/v8/Quote_Customers/{ZOHO_ID}/actions/send_mail';


    public const EMAIL_LEAD_BUYERS_API_URL     = 'EMAIL_LEAD_BUYERS_API_URL';
    public const EMAIL_QUOTE_CUSTOMERS_API_URL = 'EMAIL_QUOTE_CUSTOMERS_API_URL';

    // map constants -> DB setting keys
    protected static array $map = [
        self::EMAIL_LEAD_BUYERS_API_URL     => 'zoho_email_api_url',
        self::EMAIL_QUOTE_CUSTOMERS_API_URL => 'zoho_email_api_url',
    ];

    /**
     * Get raw setting from DB (via CustomHelper)
     *
     * @param string $constKey One of the class constants above
     * @return string|null
     */
    public static function getSetting(string $constKey, ?string $zohoId = null): ?string
    {
        if (!isset(self::$map[$constKey])) {
            return null;
        }

        $baseUrl = CustomHelper::setting_value(self::$map[$constKey]);

        if (!$baseUrl) {
            return null;
        }

        // Build the template URL depending on the constant
        switch ($constKey) {
            case self::EMAIL_LEAD_BUYERS_API_URL:
                $template = rtrim($baseUrl, '/') . '/Lead_Buyer_Registration/{ZOHO_ID}/actions/send_mail';
                break;

            case self::EMAIL_QUOTE_CUSTOMERS_API_URL:
                $template = rtrim($baseUrl, '/') . '/Quote_Customers/{ZOHO_ID}/actions/send_mail';
                break;

            default:
                $template = rtrim($baseUrl, '/');
                break;
        }

        // If no zohoId passed, return template with placeholder (or base)
        if ($zohoId === null) {
            return $template;
        }

        // Replace placeholder with actual id (safe)
        return str_replace('{ZOHO_ID}', ltrim($zohoId, '/'), $template);
    }


    public static function getUrl($key, $val)
    {
        $url = $key;
        return str_replace('{ZOHO_ID}', $val, $url);
    }


    public static function getAccessToken()
    {
        if (Cache::has('zoho_access_token')) {
            return Cache::get('zoho_access_token');
        }

        $response = Http::asForm()->post('https://accounts.zoho.eu/oauth/v2/token', [
            'refresh_token' => CustomHelper::setting_value('zoho_refresh_token', '1000.d0a97ae6984c62b12f48ff5713738ff5.909decfa9983a8c1948ef6b318e7338e'),
            'client_id' => CustomHelper::setting_value('zoho_client_id', '1000.FJEIQ7MU0TDJVHYALND65YGXHACOBP'),
            'client_secret' => CustomHelper::setting_value('zoho_client_secret', 'be2d92d7c7e894d377bfbcd68fd62ea54175b5a683'),
            'grant_type' => 'refresh_token'
        ]);

        $data = $response->json();
        if (isset($data['access_token'], $data['expires_in'])) {
            Cache::put('zoho_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 100));

            $scopes = $data['scope'] ?? null;  // Extract scopes (always present if token succeeds)
           \Log::info('Extracted Zoho scopes: ' . ($scopes ?? 'null'));
            return $data['access_token'];
        }

        return null;
    }
    public static function getZohoLeadBuyerId($accessToken, $userId)
    {
        $recId = User::where('id', $userId)->value('zoho_record_id');
        if (!empty($recId)) {
            return $recId;
        }

        $baseUrl = CustomHelper::setting_value('zoho_email_api_url');

        $response = Http::withToken($accessToken)
            ->get($baseUrl . '/Lead_Buyer_Registration/search', [
                'criteria' => "(Lead_buyer_auto_id:equals:{$userId})"
            ]);

        // $response = Http::withToken($accessToken)
        //     ->get('https://crmsandbox.zoho.eu/crm/v2/Lead_Buyer_Registration/search', [
        //         'criteria' => "(Lead_buyer_auto_id:equals:{$userId})"
        //     ]);

        $data = $response->json();

        if (!empty($data['data'][0]['id'])) {
            $zohoId = User::where('id', $userId)->update([
                'zoho_record_id' => $data['data'][0]['id']
            ]);
            return $data['data'][0]['id'];
        }
        return null;
    }

    public static function getZohoAbandonLeadBuyerId($accessToken, $userId)
    {
        $recId = AbandonedUser::where('id', $userId)->value('zoho_record_id');
        if (!empty($recId)) {
            return $recId;
        }

        $baseUrl = CustomHelper::setting_value('zoho_email_api_url');

        $response = Http::withToken($accessToken)
            ->get($baseUrl . '/Lead_Buyer_Registration/search', [
                'criteria' => "(Lead_buyer_auto_id:equals:{$recId})"
            ]);

        // $response = Http::withToken($accessToken)
        //     ->get('https://crmsandbox.zoho.eu/crm/v2/Lead_Buyer_Registration/search', [
        //         'criteria' => "(Lead_buyer_auto_id:equals:{$userId})"
        //     ]);

        $data = $response->json();

        if (!empty($data['data'][0]['id'])) {
            $zohoId = AbandonedUser::where('id', $userId)->update([
                'zoho_record_id' => $data['data'][0]['id']
            ]);
            return $data['data'][0]['id'];
        }
        return null;
    }


    public static function getZohoQuoteCustomerId($accessToken, $userId)
    {
        $recId = User::where('id', $userId)->value('zoho_record_id');
        if (!empty($recId)) {
            return $recId;
        }

        $baseUrl = CustomHelper::setting_value('zoho_email_api_url');

        $response = Http::withToken($accessToken)
            ->get($baseUrl . '/Quote_Customers/search', [
                'criteria' => "(User_Auto_Id:equals:{$recId})"
            ]);

        // $response = Http::withToken($accessToken)
        //     ->get('https://crmsandbox.zoho.eu/crm/v2/Quote_Customers/search', [
        //         'criteria' => "(User_auto_Id:equals:{$userId})"
        //     ]);

        $data = $response->json();

        if (!empty($data['data'][0]['id'])) {
            $zohoId = User::where('id', $userId)->update([
                'zoho_record_id' => $data['data'][0]['id']
            ]);
            return $data['data'][0]['id'];
        }
        return null;
    }


    public static function getZohoAbandonedQuoteCustomerId($accessToken, $userId)
    {
        $recId = AbandonedUser::where('id', $userId)->value('zoho_record_id');
        if (!empty($recId)) {
            return $recId;
        }

        $baseUrl = CustomHelper::setting_value('zoho_email_api_url');

        $response = Http::withToken($accessToken)
            ->get($baseUrl . '/Quote_Customers/search', [
                'criteria' => "(User_Auto_Id:equals:{$recId})"
            ]);

        // $response = Http::withToken($accessToken)
        //     ->get('https://crmsandbox.zoho.eu/crm/v2/Quote_Customers/search', [
        //         'criteria' => "(User_auto_Id:equals:{$userId})"
        //     ]);

        $data = $response->json();

        if (!empty($data['data'][0]['id'])) {
            $zohoId = AbandonedUser::where('id', $userId)->update([
                'zoho_record_id' => $data['data'][0]['id']
            ]);
            return $data['data'][0]['id'];
        }
        return null;
    }


    public static function dispatchAfterResponse(callable $callback, array $responseData = ['success' => true])
    {

        register_shutdown_function(function () use ($callback) {
            try {
                $callback();
            } catch (\Throwable $e) {
                Log::error('Zoho background task failed', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });


        $json = json_encode($responseData);


        if (!headers_sent()) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
            header('Content-Type: application/json');
            header('Content-Length: ' . strlen($json));
            header('Connection: close');
        }


        while (ob_get_level() > 0) {
            ob_end_clean();
        }


        echo $json;


        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
        }
    }





    // public static function dispatchAfterResponse(callable $callback, array $responseData = ['success' => true])
    // {
    //     // Send the response first
    //     $json = json_encode($responseData);

    //     // Set headers for CORS and response
    //     if (!headers_sent()) {
    //         header("Access-Control-Allow-Origin: *");
    //         header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    //         header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    //         header("Content-Type: application/json");
    //         header("Content-Length: " . strlen($json));
    //         header("Connection: close");
    //     }

    //     // Clean (and end) all output buffers to prevent corrupt response
    //     while (ob_get_level() > 0) {
    //         ob_end_clean();
    //     }

    //     echo $json;

    //     // Close connection with client
    //     if (function_exists('fastcgi_finish_request')) {
    //         fastcgi_finish_request();
    //     } else {
    //         flush();
    //     }

    //     // Register callback to be executed after response
    //     register_shutdown_function(function () use ($callback) {
    //         try {
    //             $callback();
    //         } catch (\Throwable $e) {
    //             \Log::error('Zoho background task failed', [
    //                 'message' => $e->getMessage(),
    //                 'trace' => $e->getTraceAsString()
    //             ]);
    //         }
    //     });
    // }



    public static function logZohoRequest($functionName = null, $url = null, $payload = null, $response = null, $error = null, $userId = null, $dbRecordId = null, $dbTable = null)
    {
        try {
            DB::table('zoho_logs')->insert([
                'url'           => $url ?? '',
                'function_name' => $functionName ?? '',
                'ipaddress'     => request()->ip() ?? 'N/A',
                'payload'       => json_encode([
                    'request'  => $payload ?? [],
                    'response' => $response ?? [],
                    'error'    => $error ?? '',
                    'userId'    => $userId ?? '',
                    'dbRecordId'  => $dbRecordId ?? '',
                    'dbTable'  => $dbTable ?? '',
                ], JSON_UNESCAPED_UNICODE),
                'created_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            // Logging should never break your code
            \Log::error('Zoho Log Failed: ' . $e->getMessage());
        }
    }


    public static function getAccountId($accessToken)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
        ])->get('https://mail.zoho.eu/api/accounts');

       if ($response->successful()) {

    Log::info('Zoho accounts response:', $response->json());

    $accounts = $response->json('data', []);

    if (!empty($accounts) && isset($accounts[0]['accountId'])) {
        return $accounts[0]['accountId'];
    }

    Log::error('Zoho accountId not found; response missing accountId');
    return null;
}
        Log::error('Failed to fetch Zoho accountId: ' . $response->body());
        return null;
    }



    public static function getnewAccessTokenTest()
    {
        if (Cache::has('zoho_access_token')) {
            return Cache::get('zoho_access_token');
        }

        $response = Http::asForm()->post('https://accounts.zoho.eu/oauth/v2/token', [
            'refresh_token' => CustomHelper::setting_value('zoho_refresh_token', '1000.d0a97ae6984c62b12f48ff5713738ff5.909decfa9983a8c1948ef6b318e7338e'),
            'client_id' => CustomHelper::setting_value('zoho_client_id', '1000.FJEIQ7MU0TDJVHYALND65YGXHACOBP'),
            'client_secret' => CustomHelper::setting_value('zoho_client_secret', 'be2d92d7c7e894d377bfbcd68fd62ea54175b5a683'),
            'grant_type' => 'refresh_token'
        ]);

        $data = $response->json();
        if (isset($data['access_token'], $data['expires_in'])) {
            Cache::put('zoho_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 100));

           return $scopes = $data['scope'] ?? null;  // Extract scopes (always present if token succeeds)
           //\Log::info('Extracted Zoho scopes: ' . ($scopes ?? 'null'));
          //  return $data['access_token'];
        }

        return null;
    }
}
