<?php
namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CustomHelper;

class ZohoLeads
{

    public function integrateLead($lead)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $zohoId = $this->getZohoLeadId($access_token, $lead->id);
        $payload = $this->buildLeadPayload($lead, $zohoId);
        $response = $this->sendToZoho($access_token, $payload, $zohoId);
        return $response->json();

    }
    protected function getZohoLeadId($accessToken, $leadId)
    {
        $response = Http::withToken($accessToken)
           ->get('https://www.zohoapis.in/crm/v2/Leads/search', [
                'criteria' => "(Lead_auto_Id:equals:{$leadId})"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }

    protected function buildLeadPayload($lead, $zohoId = null)
    {
        $payload = [
           'data' => [[
                    'Lead_auto_Id' => $lead->id,
                    'CustomerId' => $lead->customer_id,
                    'ServiceId' => $lead->service_id,
                    'City Name' => $lead->city,
                    'postcode' => $lead->postcode,
                    'questions' => $lead->questions,
                    'arrayed_questions' => $lead->arrayed_questions,
                    'Mobile Number' => $lead->phone,
                    'credit_score' => $lead->credit_score,
                    'recevive_online' => $lead->recevive_online,
                    'is_urgent' => $lead->is_urgent,
                    'should_autobid' => 1,
                    'is_high_hiring' => $lead->is_high_hiring,
                    'is_phone_verified' => $lead->is_phone_verified,
                    'is_frequent_user' => $lead->is_frequent_user,
                    'lead_status' => $lead->status,
                    'Last_Name' => "Nil",
                    'updated_at' => now()->format('c')
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
                ? "https://www.zohoapis.in/crm/v2/Leads/{$zohoId}"
                : "https://www.zohoapis.in/crm/v2/Leads";

        $method = $zohoId ? 'put' : 'post';

        return Http::withToken($accessToken)->$method($url, $payload);
    }
}
