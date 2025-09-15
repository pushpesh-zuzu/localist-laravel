<?php
namespace App\Helpers\Zoho;

use App\Models\AbandonedUser;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZohoQuoteCustomers
{
    public function integrateQuoteCustomer($userId,$type=null)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        //$zohoId = $this->getZohoCustomerId($access_token, $user->id);

        $payload = $this->buildCustomerPayload($userId,$type);

        $response = $this->upsertToZohoService($access_token, $payload);

        $responseData = $response->json();
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
            if($type){
                AbandonedUser::where('id', $userId)->update([
                    'zoho_record_id' => $zohoRecordId,
                ]);
            }
        }

        Log::info('Zoho API Credit Used for LeadBuyer Sync', [
            'user_id' => $userId,
            'response' => $responseData
        ]);

        return $responseData;

    }


    protected function buildCustomerPayload($userId,$type=null)
    {

        if($type){
            $user = AbandonedUser::findOrFail($userId);
        }
        else{
            $user = User::findOrFail($userId);
        }
        $datetime = new DateTime($user->created_at, new DateTimeZone('Asia/Kolkata'));
        $formatted = $datetime->format('Y-m-d\TH:i:sP');
        $payload = [
            'data' => [[
                'User_Auto_Id'      => $user->zoho_record_id,
                'Name'              => $user->name,
                'Email'             => $user->email,
                'Mobile'            => $user->phone,
                'zipcode'           => $user->zipcode,
                'city'              => $user->city,
                'otp'               => $user->otp ?? 0,
                'registration_type' => $user->form_status ==1 ? 'completed' : 'abandoned',
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
