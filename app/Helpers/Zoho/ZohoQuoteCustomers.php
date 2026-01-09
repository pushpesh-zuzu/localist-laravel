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
                }else{
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
}
