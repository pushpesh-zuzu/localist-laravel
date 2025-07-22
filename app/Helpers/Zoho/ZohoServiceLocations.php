<?php
namespace App\Helpers\Zoho;

use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZohoServiceLocations
{
    public function integrateServiceLocations($userId,$locationIds)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        // $zohoServiceId = $this->getZohoBuyerServiceId($access_token, $locationId);

        // $payload = $this->buildServicePayload($access_token, $userId, $locationId, $zohoServiceId);
        // if (!$payload) return null;

        // $response = $this->sendUserServiceToZoho($access_token, $payload, $zohoServiceId);

        // return $response->json();

        $results = [];

        foreach ($locationIds as $locationId) {
            try {
                $zohoServiceId = $this->getZohoBuyerServiceId($access_token, $locationId);

                $payload = $this->buildServicePayload($access_token, $userId, $locationId, $zohoServiceId);
                if (!$payload) {
                    $results[$locationId] = ['error' => 'Empty payload'];
                    continue;
                }

                $response = $this->sendUserServiceToZoho($access_token, $payload, $zohoServiceId);
                $results[$locationId] = $response->json();
            } catch (\Throwable $e) {
                $results[$locationId] = ['error' => $e->getMessage()];
            }
        }

        return $results;

    }

    public function integrateServiceSingleLocations($userId,$locationId)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $zohoServiceId = $this->getZohoBuyerServiceId($access_token, $locationId);

        $payload = $this->buildServicePayload($access_token, $userId, $locationId, $zohoServiceId);
        if (!$payload) return null;

        $response = $this->sendUserServiceToZoho($access_token, $payload, $zohoServiceId);

        return $response->json();



    }

    protected function buildServicePayload($access_token, $userId, $locationId, $zohoServiceId = null)
    {
        $location = UserServiceLocation::find($locationId);
        if (!$location) return null;

        $serviceDetails = UserService::with(['user', 'category'])
            ->find($location->user_service_id);
        if (!$serviceDetails) return null;

        $lookUpId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);
        $payload = [
            'data' => [[
                'Location_Id'        => $location->id,
                'Service Name'      => $serviceDetails->category->name ?? '',
                'Name'              => $serviceDetails->category->name ?? '',
                // 'Lead_Buyer_Name1'  => $serviceDetails->user->name ?? '',
                'Lead_Buyer_Lookup' => $lookUpId,
                'Miles'             => $location->miles,
                'Postcode'          => $location->postcode,
                'Nation_Wide'       => $location->nation_wide == 1 ? 'Yes' : 'No',
                'City'              => $location->city,
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
            ->get('https://www.zohoapis.eu/crm/v2/Services_Locations/search', [
                'criteria' => "(Location_Id:equals:{$serviceId})"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }





    protected function sendUserServiceToZoho($accessToken, array $payload, $zohoServiceId = null)
    {
        $url = $zohoServiceId
            ? "https://www.zohoapis.eu/crm/v2/Services_Locations/{$zohoServiceId}"
            : "https://www.zohoapis.eu/crm/v2/Services_Locations";

        $method = $zohoServiceId ? 'put' : 'post';

        return  Http::withToken($accessToken)->$method($url, $payload);
    }

    public function deleteBuyerServiceLocation($serviceIds)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $results = [];

        foreach ($serviceIds as $serviceId) {

            $zohoServiceId = $this->getZohoBuyerServiceId($access_token, $serviceId);

            if (!$zohoServiceId) {
                Log::warning("Zoho Service delete failed: No Zoho ID found for service_id {$serviceId}");
                return null;
            }

            $response = Http::withToken($access_token)
                ->delete("https://www.zohoapis.eu/crm/v2/Services_Locations/{$zohoServiceId}");
            $results[$serviceId] = $response->json();
        }
        return $results;
        // if ($response->successful()) {
        //     Log::info("Zoho Service deleted for service_id {$serviceId}");
        // } else {
        //     Log::error("Zoho Service delete failed", [
        //         'service_id' => $serviceId,
        //         'response' => $response->json(),
        //     ]);
        // }

        //return $response->json();
    }


}
