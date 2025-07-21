<?php
namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CustomHelper;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ZohoHelper
{
    public const  EMAIL_LEAD_BUYERS_API_URL = 'https://www.zohoapis.eu/crm/v8/Lead_Buyer_Registration/{ZOHO_ID}/actions/send_mail';


    public static function getUrl($key, $val){
        $url = $key;
        return str_replace('{ZOHO_ID}', $val, $url);
    }


    public static function getAccessToken()
    {
        if (Cache::has('zoho_access_token')) {
            return Cache::get('zoho_access_token');
        }

        $response = Http::asForm()->post('https://accounts.zoho.eu/oauth/v2/token', [
            'refresh_token' => CustomHelper::setting_value('zoho_refresh_token','1000.978fe950cd1fee122b6148c8e1f94938.0a6cf411f7e64540b4f67be1876ee169'),
            'client_id' => CustomHelper::setting_value('zoho_client_id','1000.FJEIQ7MU0TDJVHYALND65YGXHACOBP'),
            'client_secret' => CustomHelper::setting_value('zoho_client_secret','be2d92d7c7e894d377bfbcd68fd62ea54175b5a683'),
            'grant_type' => 'refresh_token'
        ]);

        $data = $response->json();
        if (isset($data['access_token'], $data['expires_in'])) {
            Cache::put('zoho_access_token', $data['access_token'], now()->addSeconds($data['expires_in']-100));
            return $data['access_token'];
        }

        return null;
    }
    public static function getZohoLeadBuyerId($accessToken, $userId)
    {
        $recId = User::where('id', $userId)->value('zoho_record_id');
        if(!empty($recId)){
            return $recId;
        }

        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/search', [
                'criteria' => "(Lead_buyer_auto_id:equals:{$userId})"
            ]);

        $data = $response->json();

        if(!empty($data['data'][0]['id'])){
            $zohoId = User::where('id', $userId)->update([
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


        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header('Content-Type: application/json');
        header('Content-Length: ' . strlen($json));
        header('Connection: close');


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




}
