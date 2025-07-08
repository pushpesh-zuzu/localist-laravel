<?php
namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ZohoQuoteCustomers
{
    public function integrateQuoteCustomer($user)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $zohoId = $this->getZohoCustomerId($access_token, $user->id);
        $payload = $this->buildCustomerPayload($user, $zohoId);
        $response = $this->sendToZoho($access_token, $payload, $zohoId);

        return $response->json();

    }
    protected function getZohoCustomerId($accessToken, $userId)
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.in/crm/v2/Quote_Customers/search', [
                'criteria' => "(User_auto_Id:equals:{$userId})"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }

    protected function buildCustomerPayload($user, $zohoId = null)
    {
        $payload = [
            'data' => [[
                'User_auto_Id'   => $user->id,
                'Name'           => $user->name,
                'Email'          => $user->email,
                'Mobile'         => $user->phone,
                'password'       => $user->password,
                'zipcode'        => $user->zipcode,
                'city'           => $user->city,
                'status'         => $user->status == 1 ? 'Added' : 'Rejected',
                'otp'            => $user->otp,
                'active_status'  => $user->active_status,
                'registration_type' => $user->form_status ==1 ? 'completed' : 'abandoned',
                'user_type'      => $user->user_type,
                'updated_at'     => now()->format('c'),
            ]]
        ];

        if (!$zohoId) {
            $payload['data'][0]['created_at'] = now()->format('c');
        }

        return $payload;
    }

    protected function sendToZoho($accessToken, array $payload, $zohoId = null)
    {
        $url = $zohoId
            ? "https://www.zohoapis.in/crm/v2/Quote_Customers/{$zohoId}"
            : "https://www.zohoapis.in/crm/v2/Quote_Customers";

        $method = $zohoId ? 'put' : 'post';

        return Http::withToken($accessToken)->$method($url, $payload);
    }


}
