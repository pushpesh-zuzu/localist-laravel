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
    public function integrateService($userId, $serviceIds)
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
                $results = $response->json();

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


                    $responseDataItem = $results['data'][0] ?? null;
                    $errorMessage = $results['data'][0]['message'] ?? null;

                    try {
                        ZohoHelper::logZohoRequest(
                            'integrateService',
                            'https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/upsert',
                            $payload,
                            $responseDataItem,
                            $errorMessage,
                            $userId,
                            $serviceId,         // db record
                            'user_services',    // db table
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to log Zoho Service', [
                            'exception' => $e->getMessage(),
                            'user_id' => $userId,
                            'service_id' => $serviceId
                        ]);
                    }

            }
        }

        $usedCredits = $response ? $response->header('X-API-COST') : 0;

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
        if ($lookUpId) {
            $payload = [
                'data' => [[
                    'Service_Id'        => $service->id,
                    'Name'              => $serviceDetails->category->name ?? '',
                    'Lead_Services_Lookup' => $lookUpId,
                    'Status'            => $serviceDetails->status == 1 ? 'Added' : 'Rejected',
                ]],
                'duplicate_check_fields' => ['Service_Id']
            ];
        } else {
            return false;
        }

        return $payload;
    }

    // protected function upsertToZohoService($accessToken, array $payload)
    // {
    //     return Http::withToken($accessToken)
    //         ->post('https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/upsert', $payload);
    // }



    protected function upsertToZohoService($accessToken, array $payload, $zohoServiceId = null)
    {
        // Update existing record using PUT
        if (!empty($zohoServiceId)) {
            return Http::withToken($accessToken)
                ->put("https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/{$zohoServiceId}", [
                    'data' => $payload['data']
                ]);
        }

        // Create new record using POST / upsert
        return Http::withToken($accessToken)
            ->post("https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/upsert", $payload);
    }



    public function updateZohoServiceAssign($userId, $serviceId, $zohoServiceId)
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
                'Service_Id'            => $serviceId,
                'Lead_Services_Lookup'  => $lookUpId,
            ]],
            'duplicate_check_fields' => ['Service_Id']
        ];

        // 4. Upsert/Update
        $response = $this->upsertToZohoService($accessToken, $payload, $zohoServiceId);

        $responseJson = $response?->json();
        $responseDataItem = $responseJson['data'][0] ?? null;
        $errorMessage     = $responseJson['data'][0]['message'] ?? null;

        // 5. Log Correct URL (PUT or POST)
        $logUrl = "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Services/{$zohoServiceId}";

        // 6. Save Zoho Logs
        ZohoHelper::logZohoRequest(
            'updateZohoServiceAssign',
            $logUrl,
            $payload,
            $responseDataItem,
            $errorMessage,
            $userId,
            $serviceId,        // correct record for user_services table
            'user_services'
        );

        return $responseJson;
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


    public function updateZohoLastLogin($userId)
    {
        $accessToken = ZohoHelper::getAccessToken();

        if (!$accessToken) {
            Log::error("Zoho access token not available while updating last login for user ID: {$userId}");
            return null;
        }

        // Load user with last login relation
        $user = User::with('lastLogin')->findOrFail($userId);

        // Get existing Zoho record ID or fetch based on user type
        $zohoId = $user->zoho_record_id;

        if (empty($zohoId)) {
            if ($user->user_type == '1') {
                $zohoId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
            } elseif ($user->user_type == '2') {
                $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $userId);
            } else {
                Log::warning("Unknown user type ({$user->user_type}) for user ID: {$userId}");
                return false;
            }

            if (!$zohoId) {
                Log::warning("Zoho record ID not found or could not be fetched for user ID: {$userId}");
                return false;
            }
        }

        // Format last login timestamp
        $lastLogin = $user->lastLogin?->login_at
            ? \Carbon\Carbon::parse($user->lastLogin->login_at)->format('m/d/Y h:i A')
            : null;

        $payload = [
            'data' => [[
                'Last_Login' => $lastLogin,
            ]],
        ];

        // Determine Zoho module based on user type
        switch ($user->user_type) {
            case '1':
                $url = "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/{$zohoId}";
                break;

            case '2':
                $url = "https://www.zohoapis.eu/crm/v2/Quote_Customers/{$zohoId}";
                break;

            default:
                Log::warning("Unknown user type ({$user->user_type}) for user ID: {$userId}");
                return false;
        }

        // Send update request to Zoho CRM
        $response = Http::withToken($accessToken)->put($url, $payload);
        $responseData = $response->json();

        if (!$response->successful()) {
            Log::error("Zoho Last Login update failed for user {$userId}", [
                'status' => $response->status(),
                'response' => $responseData,
            ]);
        }

        return $responseData;
    }
}
