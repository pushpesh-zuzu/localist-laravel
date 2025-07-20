<?php
namespace App\Helpers\Zoho;

use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZohoService
{
    public function integrateService($userId,$serviceId)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $zohoServiceId = $this->getZohoBuyerServiceId($access_token, $serviceId);

        $payload = $this->buildServicePayload($access_token, $userId, $serviceId, $zohoServiceId);
        if (!$payload) return null;

        $response = $this->sendUserServiceToZoho($access_token, $payload, $zohoServiceId);

        return $response->json();

    }

    protected function buildServicePayload($access_token, $userId, $serviceId, $zohoServiceId = null)
    {
        $service = UserService::find($serviceId);
        if (!$service) return null;

        $serviceDetails = UserService::with(['user', 'category'])
            ->find($service->id);
        if (!$serviceDetails) return null;

        $lookUpId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);

        $payload = [
            'data' => [[
                'Service_Id'        => $service->id,
                'Service_Name'      => $serviceDetails->category->name ?? '',
                'Name'              => $serviceDetails->user->name ?? '',
                'Lead_Services_Lookup' => $lookUpId,
                'Status'            => $serviceDetails->status == 1 ? 'Added' : 'Rejected',
            ]]
        ];

       //dd($payload);
        if (!$zohoServiceId) {
            $payload['data'][0]['created_at'] = now()->format('c');
        }



        return $payload;
    }



    protected function getZohoBuyerServiceId($accessToken, $serviceId)
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/search', [
                'criteria' => "(Service_Id:equals:{$serviceId})"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }





    protected function sendUserServiceToZoho($accessToken, array $payload, $zohoServiceId = null)
    {
        $url = $zohoServiceId
            ? "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/{$zohoServiceId}"
            : "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services";

        $method = $zohoServiceId ? 'put' : 'post';

        return  Http::withToken($accessToken)->$method($url, $payload);
    }

     public function deleteBuyerService($serviceId)
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $zohoServiceId = $this->getZohoBuyerServiceId($access_token, $serviceId);
        if (!$zohoServiceId) {
            Log::warning("Zoho Service delete failed: No Zoho ID found for service_id {$serviceId}");
            return null;
        }

        $response = Http::withToken($access_token)
            ->delete("https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/{$zohoServiceId}");

        if ($response->successful()) {
            Log::info("Zoho Service deleted for service_id {$serviceId}");
        } else {
            Log::error("Zoho Service delete failed", [
                'service_id' => $serviceId,
                'response' => $response->json(),
            ]);
        }

        return $response->json();
    }


}
