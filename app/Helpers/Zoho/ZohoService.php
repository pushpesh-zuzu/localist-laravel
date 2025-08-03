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
    public function integrateService($userId,$serviceIds)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        // $zohoServiceId = $this->getZohoBuyerServiceId($access_token, $serviceId);

        // $payload = $this->buildServicePayload($access_token, $userId, $serviceId, $zohoServiceId);
        // if (!$payload) return null;

        // $response = $this->sendUserServiceToZoho($access_token, $payload, $zohoServiceId);

        // return $response->json();

        $results = [];

        foreach ($serviceIds as $serviceId) {

            $payload = $this->buildServicePayload($access_token, $userId, $serviceId);

            if ($payload) {
                $response = $this->upsertToZohoService($access_token, $payload);
                $results=$response->json();

                if (
                    isset($results['data'][0]['status']) &&
                    $results['data'][0]['status'] === 'success' &&
                    isset($results['data'][0]['details']['id'])
                ) {
                    $zohoRecordId = $results['data'][0]['details']['id'];
                    UserService::where('id', $serviceId)->update([
                        'zoho_service_id' => $zohoRecordId,
                    ]);

                }


            }

        }

        $usedCredits = $response->header('X-API-COST'); // this may return 24

        Log::info('Zoho API Credit Used for service Sync', [
            'user_id' => $userId,
            'credits_used' => $usedCredits,
        ]);

        return $results;

    }

    protected function buildServicePayload($access_token, $userId, $serviceId)
    {
        $service = UserService::find($serviceId);
        if (!$service) return null;

        $serviceDetails = UserService::with(['user', 'category'])
            ->find($service->id);
        if (!$serviceDetails) return null;

        $lookUpId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);
        if($lookUpId){
            $payload = [
                'data' => [[
                    'Service_Id'        => $service->id,
                    'Name'              => $serviceDetails->category->name ?? '',
                    'Lead_Services_Lookup' => $lookUpId,
                    'Status'            => $serviceDetails->status == 1 ? 'Added' : 'Rejected',
                ]],
                'duplicate_check_fields' => ['Service_Id']
            ];
        }
        else{
            return false;
        }

        return $payload;
    }

    protected function upsertToZohoService($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/upsert', $payload);
    }








    // protected function sendUserServiceToZoho($accessToken, array $payload, $zohoServiceId = null)
    // {
    //     $url = $zohoServiceId
    //         ? "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/{$zohoServiceId}"
    //         : "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services";

    //     $method = $zohoServiceId ? 'put' : 'post';

    //     return  Http::withToken($accessToken)->$method($url, $payload);
    // }

    public function deleteBuyerService($zohoServiceId)
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        if (!$zohoServiceId) {

            return null;
        }

        $response = Http::withToken($access_token)
            ->delete("https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/{$zohoServiceId}");

        if ($response->successful()) {
            Log::info("Zoho Service deleted for service_id {$zohoServiceId}");
        } else {
            Log::error("Zoho Service delete failed", [
                'service_id' => $zohoServiceId,
                'response' => $response->json(),
            ]);
        }

        return $response->json();
    }


}
