<?php

namespace App\Helpers\Zoho;

use App\Models\Category;
use App\Models\LeadRequest;
use App\Models\RecommendedLead;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoPurchasedLeads
{
    public function integratePurchaseLeads($userId,$id)
    {
        $accessToken = ZohoHelper::getAccessToken();

        if (!$accessToken) {
            return null;
        }

        $recommendedLeads = RecommendedLead::where('id', $id)
               ->first();


        // $recommendedLeadId = $this->getZohoPurchasedLeadsId($accessToken, $recommendedLeads->id);

        $payload = $this->buildPurchasedLeadPayload($accessToken,$recommendedLeads,$userId);

        Log::info('Zoho API Purchase Payload', [
            'user_id' => $userId,
            'purchase_id' => $id,
            'payload' => $payload,
        ]);
        if (!$payload) return null;

        $response = $this->upsertToZohoService($accessToken,$recommendedLeads, $payload);

        $responseData = $response->json();

        if (
            isset($responseData['data'][0]['status']) &&
            $responseData['data'][0]['status'] === 'success' &&
            isset($responseData['data'][0]['details']['id'])
        ) {
            $zohoRecordId = $responseData['data'][0]['details']['id'];

            RecommendedLead::where('id', $id)->update([
                'zoho_purchase_id' => $zohoRecordId,
            ]);
        }

        Log::info('Zoho API Credit Used for Purchased Sync', [
            'user_id' => $userId,
            'purchase_id' => $id,
            'response' => $response->json(),
        ]);
        return $response->json();


    }

    protected function buildPurchasedLeadPayload($accessToken, $recommendedLeads,$userId)
    {

        $lookUpId =ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
        $userName=User::find($userId)->name;
        $customerName=User::find($recommendedLeads->buyer_id)->name;
        $service=Category::find($recommendedLeads->service_id)->name;
        $creditScore=LeadRequest::find($recommendedLeads->lead_id)->credit_score;
        $datetime = new DateTime($recommendedLeads->created_at, new DateTimeZone('Europe/London'));
        $formatted = $datetime->format('Y-m-d\TH:i:sP');

        return [
            'data' => [[
                'Lead_Purchased_Id'     => $recommendedLeads->id,
                'Lead_Purchase_Lookup' => $lookUpId,
                'Name'                 => $service,
                'Customer_Name'        => $customerName,
                'Credit'               => $creditScore,
                'Date'                 => $formatted,
                'Purchase_Type'        => $recommendedLeads->purchase_type,
                'Status'               => $recommendedLeads->status

            ]],
            'duplicate_check_fields' => ['Lead_Purchase_Id']

        ];
    }

    protected function upsertToZohoService($accessToken,$recommendedLeads, array $payload)
    {

        if ($recommendedLeads->zoho_purchase_id) {

            $response = Http::withToken($accessToken)
                ->put("https://www.zohoapis.eu/crm/v2/Lead_Purchased/{$recommendedLeads->zoho_purchase_id}", [
                    'data' => $payload['data']
                ]);
        } else {

            $response = Http::withToken($accessToken)->post('https://www.zohoapis.eu/crm/v2/Lead_Purchased/upsert', $payload);
        }

        return $response;
    }


    // protected function getZohoPurchasedLeadsId($accessToken, $recommendedLeadId)
    // {
    //     $response = Http::withToken($accessToken)
    //         ->get('https://www.zohoapis.eu/crm/v2/Leads_Purchased/search', [
    //             'criteria' => "(Lead_Purchase_Id:equals:$recommendedLeadId)"
    //         ]);

    //     $data = $response->json();

    //     return $data['data'][0]['id'] ?? null;
    // }

    // protected function sendLeadPurchasedToZoho($accessToken, array $payload, $recommendedLeadId = null)
    // {
    //     $url = $recommendedLeadId
    //         ? "https://www.zohoapis.eu/crm/v2/Leads_Purchased/{$recommendedLeadId}"
    //         : "https://www.zohoapis.eu/crm/v2/Leads_Purchased";

    //     $method = $recommendedLeadId ? 'put' : 'post';

    //     return Http::withToken($accessToken)->$method($url, $payload);
    // }
}
