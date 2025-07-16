<?php
namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CustomHelper;

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
            'refresh_token' => CustomHelper::setting_value('zoho_refresh_token','1000.c68e9a3d9f74e173ffffd9c6bfb59363.73534412eedae86f0ab6102401cd5f8f'),
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
}
