<?php
namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CustomHelper;

class ZohoHelper
{
    public const  EMAIL_LEAD_BUYERS_API_URL = 'https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/{ZOHO_ID}/actions/send_mail';


    public static function getUrl($key, $val){
        $url = $key;
        return str_replace('{ZOHO_ID}', $val, $url);
    }


    public static function getAccessToken()
    {
        // Cache::forget('zoho_access_token');

        if (Cache::has('zoho_access_token')) {
            return Cache::get('zoho_access_token');
        }

        $response = Http::asForm()->post('https://accounts.zoho.eu/oauth/v2/token', [
            'refresh_token' => CustomHelper::setting_value('zoho_refresh_token','1000.1a53f04d31321f900bae751ddeded56b.bbd05e59efd7bac8692024134c630438'),
            'client_id' => CustomHelper::setting_value('zoho_client_id','1000.FJEIQ7MU0TDJVHYALND65YGXHACOBP'),
            'client_secret' => CustomHelper::setting_value('zoho_client_secret','be2d92d7c7e894d377bfbcd68fd62ea54175b5a683'),
            'grant_type' => 'refresh_token'
        ]);

        $data = $response->json();
        if (isset($data['access_token'])) {
            Cache::put('zoho_access_token', $data['access_token'], now()->addSeconds($data['expires_in']));
            return $data['access_token'];
        }

        return null;
    }
}
