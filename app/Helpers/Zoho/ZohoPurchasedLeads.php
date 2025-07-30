<?php

namespace App\Helpers\Zoho;

use App\Models\Category;
use App\Models\LeadRequest;
use App\Models\RecommendedLead;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;

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


        $recommendedLeadId = $this->getZohoPurchasedLeadsId($accessToken, $recommendedLeads->id);

        $payload = $this->buildPurchasedLeadPayload($accessToken,$recommendedLeads,$userId);

        $response = $this->sendLeadPurchasedToZoho($accessToken, $payload, $recommendedLeadId);


        return $response->json();


    }

    protected function buildPurchasedLeadPayload($accessToken, $recommendedLeads,$userId)
    {

        $lookUpId =ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
        $userName=User::find($userId)->name;
        $customerName=User::find($recommendedLeads->buyer_id)->name;
        $service=Category::find($recommendedLeads->service_id)->name;
        $creditScore=LeadRequest::find($recommendedLeads->lead_id)->credit_score;
        $datetime = new DateTime($recommendedLeads->created_at, new DateTimeZone('Asia/Kolkata'));
        $formatted = $datetime->format('Y-m-d\TH:i:sP');

        return [
            'data' => [[
                'Lead_Purchase_Id'     => $recommendedLeads->id,
                'Lead_Purchase_Lookup' => $lookUpId,
                'Service_Name'         => $service,
                'Customer_Name'        => $customerName,
                'Credit'               => $creditScore,
                'Date'                 => $formatted,
                'Status'               => $recommendedLeads->status
            ]]
        ];
    }



    protected function getZohoPurchasedLeadsId($accessToken, $recommendedLeadId)
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/Leads_Purchased/search', [
                'criteria' => "(Lead_Purchase_Id:equals:$recommendedLeadId)"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }

    protected function sendLeadPurchasedToZoho($accessToken, array $payload, $recommendedLeadId = null)
    {
        $url = $recommendedLeadId
            ? "https://www.zohoapis.eu/crm/v2/Leads_Purchased/{$recommendedLeadId}"
            : "https://www.zohoapis.eu/crm/v2/Leads_Purchased";

        $method = $recommendedLeadId ? 'put' : 'post';

        return Http::withToken($accessToken)->$method($url, $payload);
    }
}
