<?php
namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CustomHelper;

class ZohoHelper
{
    public static function getAccessToken()
    {
        if (Cache::has('zoho_access_token')) {
            return Cache::get('zoho_access_token');
        }

        $response = Http::asForm()->post('https://accounts.zoho.in/oauth/v2/token', [
            'refresh_token' => CustomHelper::setting_value('zoho_refresh_token','1000.eed92fd895e79d5f5ec51c1d15016eb0.30cab9ef1e266fd7f8c90d36716a70ce'),
            'client_id' => CustomHelper::setting_value('zoho_client_id','1000.TC3V4D3YO89C2JM7UIOCJN0A1HB16N'),
            'client_secret' => CustomHelper::setting_value('zoho_client_secret','f975b774a35f9d12f4db00dfb69559568b5d70adb8'),
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
