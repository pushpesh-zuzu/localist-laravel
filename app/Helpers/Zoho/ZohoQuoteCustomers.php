<?php

namespace App\Helpers\Zoho;

use App\Models\AbandonedUser;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ZohoQuoteCustomers
{


    public function integrateQuoteCustomer($userId, $type = null)
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $payload = $this->buildCustomerPayload($userId, $type);

        $responseData = null;
        $errorMessage = null;

        try {

            // Main Zoho API call
            $response = $this->upsertToZohoService($access_token, $payload);

            // Zoho response always safely parsed
            $responseData = $response->json();

            // If success → update zoho_record_id
            if (
                isset($responseData['data'][0]['status']) &&
                $responseData['data'][0]['status'] === 'success' &&
                isset($responseData['data'][0]['details']['id'])
            ) {

                $zohoRecordId = $responseData['data'][0]['details']['id'];

                $updatePayload = [
                    'data' => [[
                        'id' => $zohoRecordId,
                        'User_Auto_Id' => $zohoRecordId
                    ]]
                ];

                Http::withToken($access_token)
                    ->put("https://www.zohoapis.eu/crm/v2/Quote_Customers", $updatePayload);

                if ($type) {
                    AbandonedUser::where('id', $userId)
                        ->update(['zoho_record_id' => $zohoRecordId]);
                } else {
                    User::where('id', $userId)
                        ->update(['zoho_record_id' => $zohoRecordId]);
                }
            }
        } catch (\Throwable $e) {
            // Store error without breaking flow
            $errorMessage = $e->getMessage();
        }

        $dbRecordId = $userId;           // jo record update ho raha hai
        $dbTable    = $type === 'abandon' ? 'abandoned_users' : 'users';

        ZohoHelper::logZohoRequest(
            'integrateQuoteCustomer',
            'https://www.zohoapis.eu/crm/v2/Quote_Customers/upsert',
            $payload ?? null,
            $responseData ?? null,
            $errorMessage ?? null,
            $userId ?? null,
            $dbRecordId ?? null,
            $dbTable ?? null
        );


        return $responseData;
    }

    // public function integrateQuoteCustomer($userId,$type=null)
    // {

    //     $access_token = ZohoHelper::getAccessToken();

    //     if (!$access_token) {
    //         return null;
    //     }

    //     //$zohoId = $this->getZohoCustomerId($access_token, $user->id);

    //     $payload = $this->buildCustomerPayload($userId,$type);

    //     $response = $this->upsertToZohoService($access_token, $payload);

    //     $responseData = $response->json();
    //     if (
    //         isset($responseData['data'][0]['status']) &&
    //         $responseData['data'][0]['status'] === 'success' &&
    //         isset($responseData['data'][0]['details']['id'])
    //     ) {
    //         $zohoRecordId = $responseData['data'][0]['details']['id'];

    //         $updatePayload = [
    //                 'data' => [[
    //                     'id' => $zohoRecordId,
    //                     'User_Auto_Id' => $zohoRecordId
    //                 ]]
    //             ];

    //         Http::withToken($access_token)
    //             ->put("https://www.zohoapis.eu/crm/v2/Quote_Customers", $updatePayload);
    //         if($type){
    //             AbandonedUser::where('id', $userId)->update([
    //                 'zoho_record_id' => $zohoRecordId,
    //             ]);
    //         }
    //     }

    //     Log::info('Zoho API Credit Used for LeadBuyer Sync', [
    //         'user_id' => $userId,
    //         'response' => $responseData
    //     ]);

    //     DB::table('zoho_logs')->insert([
    //     'url'           => 'https://www.zohoapis.eu/crm/v2/Quote_Customers/upsert',
    //     'function_name' => 'integrateQuoteCustomer',
    //     'ipaddress'     => request()->ip(),
    //     'payload'       => json_encode([
    //         'request'      => $payload,
    //         'response'     => $responseData,
    //         'error'        => $errorMessage,
    //         'user_id'      => $userId,
    //     ]),
    //     'created_at' => now(),
    // ]);


    //     return $responseData;

    // }


    protected function buildCustomerPayload($userId, $type = null)
    {

        if ($type) {
            $user = AbandonedUser::findOrFail($userId);
        } else {
            $user = User::findOrFail($userId);
        }
        $datetime = new DateTime($user->created_at, new DateTimeZone('Europe/London'));
        $formatted = $datetime->format('Y-m-d\TH:i:sP');


        $payload = [
            'data' => [[
                'User_Auto_Id'      => $user->zoho_record_id,
                'Name'              => $user->name,
                'Email'             => $user->email,
                'Mobile'            => $user->phone ?? '',
                'Zipcode'           => $user->zipcode ?? "",
                'city'              => $user->city ?? "",
                'otp'               => $user->otp ?? 0,
                'Campaign_Id'      => $user->campaignid ?? '',
                'GCLID'             => $user->gclid ?? '',
                'Keyword'           => $user->keyword ?? '',
                'Campaign'          => $user->campaign ?? '',
                'AdGroup'           => $user->adgroup ?? '',
                'Target_Id'          => $user->targetid ?? '',
                'MS_Click_Id'         => $user->msclickid ?? '',
                'User_IP_Address'    => $user->user_ip_address ?? '',
                'Entry_URL'         => $user->entry_url ?? '',
                'Utm Source'    =>     $user->utm_source ?? '',
                'Utm Medium'         => $user->utm_medium ?? '',
                'Platform Source'         => $user->platform_source ?? '',
                'registration_type' => $user->form_status == 1 ? 'completed' : 'abandoned',
                'created_at'        => $formatted
            ]],
            'duplicate_check_fields' => ['User_Auto_Id']
        ];

        return $payload;
    }

    protected function upsertToZohoService($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Quote_Customers/upsert', $payload);
    }


    protected function upsertToZohoWhatsapp($accessToken, $zoho_record_id, array $payload)
    {

        $response = Http::withToken($accessToken)
            ->put("https://www.zohoapis.eu/crm/v2/Quote_Customers/{$zoho_record_id}", [
                'data' => $payload['data']
            ]);


        return $response;
    }

    public function updateZohoCustomerDetailForWhatsapp($userId, $magiclink, $quoteRequestUrl, $password)
    {
        $accessToken = ZohoHelper::getAccessToken();
        if (!$accessToken) return null;

        $user = User::findOrFail($userId);
        // Build payload for updating only Status

        $payload = [
            'data' => [[
                'User_Auto_Id'          => $user->zoho_record_id,
                'Magic_Login_Link'            => $magiclink,
                'Quote_Request_URL'     => $quoteRequestUrl,
                'Plan_Password'         => $password ?? '',
                'WhatsApp_Phone_Number' => $user->phone ?? '',
            ]],
            'duplicate_check_fields' => ['User_Auto_Id']
        ];

        // Update using your existing Zoho update function
        $response = $this->upsertToZohoWhatsapp($accessToken, $user->zoho_record_id, $payload);

        $responseDataItem = $response->json()['data'][0] ?? null;
        $errorMessage = $response->json()['data'][0]['message'] ?? null;
        $dbRecordId = $user->id;
        $dbTable = 'user_details';

        // Safe Zoho logging
        try {
            ZohoHelper::logZohoRequest(
                'updateZohoCustomerDetailForWhatsapp',
                'https://www.zohoapis.eu/crm/v2/Quote_Customers/upsert',
                $payload,           // payload sent to Zoho
                $responseDataItem,   // response received from Zoho
                $errorMessage,       // error message if any
                $user->id ?? null, // main user ID
                $dbRecordId,         // database record ID
                $dbTable,            // database table name
            );
        } catch (\Exception $e) {
            Log::error('Failed to log Zoho whatsapp  update', [
                'exception' => $e->getMessage(),
                'userId' => $userId
            ]);
        }

        return $response->json();
    }
}
