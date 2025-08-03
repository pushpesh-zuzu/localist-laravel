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
            } catch (\Throwable $e) {
                $results[$locationId] = ['error' => $e->getMessage()];
            }
        }

         Log::info('response location for user', [
                        'user_id' => $userId,
                        'response' => $results,
                    ]);

        return $results;

    }

    public function integrateServiceSingleLocations($userId,$locationId)
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
        if($lookUpId){
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
        }
        else{
            return false;
        }

       //dd($payload);


        return $payload;
    }

    protected function upsertToZohoService($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Services_Locations/upsert', $payload);
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
