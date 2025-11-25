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
    public function integrateServiceLocations($userId, $locationIds)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        Log::info('request location for user', [
            'user_id' => $userId,
            'locations' => $locationIds
        ]);

        // $zohoServiceId = $this->getZohoBuyerServiceId($access_token, $locationId);

        // $payload = $this->buildServicePayload($access_token, $userId, $locationId, $zohoServiceId);
        // if (!$payload) return null;

        // $response = $this->sendUserServiceToZoho($access_token, $payload, $zohoServiceId);

        // return $response->json();

        $results = [];

        foreach ($locationIds as $locationId) {
            try {

                $payload = $this->buildServicePayload($access_token, $userId, $locationId);
                if (!$payload) {
                    $results[$locationId] = ['error' => 'Empty payload'];
                    continue;
                }

                $response = $this->upsertToZohoService($access_token, $payload);
                $responseData = $response->json();
                $results[$locationId] = $responseData;

                if (
                    isset($responseData['data'][0]['status']) &&
                    $responseData['data'][0]['status'] === 'success' &&
                    isset($responseData['data'][0]['details']['id'])
                ) {
                    $zohoRecordId = $responseData['data'][0]['details']['id'];

                    UserServiceLocation::where('id', $locationId)->update([
                        'zoho_location_id' => $zohoRecordId,
                    ]);
                }

                // ===== Safe Zoho Logging =====
                $responseDataItem = $responseData['data'][0] ?? null;
                $errorMessage = $responseData['data'][0]['message'] ?? null;
                $dbRecordId = $locationId;
                $dbTable = 'user_service_locations';

                try {
                    ZohoHelper::logZohoRequest(
                        'integrateServiceLocations',
                        'https://www.zohoapis.eu/crm/v2/Services_Locations/upsert',
                        $payload,
                        $responseDataItem,
                        $errorMessage,
                        $userId ?? null,
                        $dbRecordId,
                        $dbTable,
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to log Zoho Service Location', [
                        'exception' => $e->getMessage(),
                        'user_id' => $userId,
                        'location_id' => $locationId
                    ]);
                }
            } catch (\Throwable $e) {
                $results[$locationId] = ['error' => $e->getMessage()];
            }
        }


        return $results;
    }

    public function integrateServiceSingleLocations($userId, $locationId)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }


        $payload = $this->buildServicePayload($access_token, $userId, $locationId);
        if (!$payload) return null;

        $response = $this->upsertToZohoService($access_token, $payload);

        return $response->json();
    }

    protected function buildServicePayload($access_token, $userId, $locationId)
    {
        $location = UserServiceLocation::find($locationId);
        if (!$location) return null;

        $serviceDetails = UserService::with(['user', 'category'])
            ->find($location->user_service_id);
        if (!$serviceDetails) return null;

        $lookUpId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);
        if ($lookUpId) {
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
                ]],
                'duplicate_check_fields' => ['Location_Id']
            ];
        } else {
            return false;
        }

        //dd($payload);


        return $payload;
    }



    protected function upsertToZohoService($accessToken, array $payload, $zohoLocationId = null)
    {
        // Update existing record using PUT
        if (!empty($zohoLocationId)) {
            return Http::withToken($accessToken)
                ->put("https://www.zohoapis.eu/crm/v2/Services_Locations/{$zohoLocationId}", [
                    'data' => $payload['data']
                ]);
        }

        // Create new record using POST / upsert
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Services_Locations/upsert', $payload);
    }



    public function updateZohoAssignServiceLocation($userId, $locationId, $zohoLocationId)
    {
        // 1. Access Token
        $accessToken = ZohoHelper::getAccessToken();
        if (!$accessToken) {
            Log::error("Zoho Access Token missing");
            return null;
        }

        // 2. Get Zoho Lookup ID
        $lookUpId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
        if (!$lookUpId) {
            Log::error("Lookup ID missing for user {$userId}");
            return null;
        }

        // 3. Build Payload
        $payload = [
            'data' => [[
                'Location_Id'            => $locationId,
                'Lead_Buyer_Lookup'  => $lookUpId,
            ]],
            'duplicate_check_fields' => ['Location_Id']
        ];

        // 4. Upsert/Update
        $response = $this->upsertToZohoService($accessToken, $payload, $zohoLocationId);

        $responseJson = $response?->json();
        $responseDataItem = $responseJson['data'][0] ?? null;
        $errorMessage     = $responseJson['data'][0]['message'] ?? null;

        // 5. Log Correct URL (PUT or POST)
        $logUrl =  "https://www.zohoapis.eu/crm/v2/Services_Locations/{$zohoLocationId}";

        // 6. Save Zoho Logs
        ZohoHelper::logZohoRequest(
            'updateZohoAssignServiceLocation',
            $logUrl,
            $payload,
            $responseDataItem,
            $errorMessage,
            $userId,
            $serviceId,        // correct record for user_services table
            'user_service_locations'
        );

        return $responseJson;
    }

    // protected function getZohoBuyerServiceId($accessToken, $serviceId)
    // {
    //     $response = Http::withToken($accessToken)
    //         ->get('https://www.zohoapis.eu/crm/v2/Services_Locations/search', [
    //             'criteria' => "(Location_Id:equals:{$serviceId})"
    //         ]);

    //     $data = $response->json();

    //     return $data['data'][0]['id'] ?? null;
    // }





    // protected function sendUserServiceToZoho($accessToken, array $payload, $zohoServiceId = null)
    // {
    //     $url = $zohoServiceId
    //         ? "https://www.zohoapis.eu/crm/v2/Services_Locations/{$zohoServiceId}"
    //         : "https://www.zohoapis.eu/crm/v2/Services_Locations";

    //     $method = $zohoServiceId ? 'put' : 'post';

    //     return  Http::withToken($accessToken)->$method($url, $payload);
    // }

    public function deleteBuyerServiceLocation($zohoServiceIds)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $results = [];

        foreach ($zohoServiceIds as $zohoServiceId) {




            if (!$zohoServiceId) {
                Log::warning("Zoho Service location delete failed: No Zoho ID found for service_id {$zohoServiceId}");
                return null;
            }

            $response = Http::withToken($access_token)
                ->delete("https://www.zohoapis.eu/crm/v2/Services_Locations/{$zohoServiceId}");
            $results[$zohoServiceId] = $response->json();
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
