<?php

namespace App\Helpers\Zoho;

use App\Models\PurchaseHistory;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoLeadAvailable{

    public function integrateAvailableLeads($userId, $lead)
    {
        $accessToken = ZohoHelper::getAccessToken();

        
        $payload = $this->buildAvailableLeadsPayload($accessToken, $userId, $lead);

        if (!$payload) return null;

        $response = $this->upsertToZohoAvailableLeads($accessToken, $payload);

        return $response->json();


    }

    protected function buildAvailableLeadsPayload($accessToken, $userId, $lead)
    {
        $lookUpId =ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
        return [
            'data' => [[
                'Name' => $lead->customer->name,
                'Postcode' => $lead->postcode,
                'Email' => $lead->customer->email,
                'Category' => $lead->category->name,
                'Phone_Number' => $lead->customer->phone,
                'Credit' => $lead->credit_score,
                'Questions' => $lead->questions,
                'User_Id' => strval($userId),
                'Lead_Available_Lookup' =>$lookUpId,
            ]]
        ];
    }

    protected function upsertToZohoAvailableLeads($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Leads_Available/upsert', $payload);
    }

    public static function deleteLeadsAvailableRecords($userId)
    {
        // 1. Get access token and lookup ID
        $accessToken = ZohoHelper::getAccessToken();
        $lookUpId =ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

        // 2. Search for records
        $searchUrl = "https://www.zohoapis.eu/crm/v2/Leads_Available/search?criteria=(Lead_Available_Lookup:equals:{$lookUpId})";
        $response = Http::withToken($accessToken)->get($searchUrl);
        $records = $response->json('data') ?? [];

        if (empty($records)) {
            return "No records found for lookup ID {$lookUpId}";
        }

        // 3. Collect IDs
        $ids = collect($records)->pluck('id')->toArray();

        // 4. Delete in chunks of 100
        foreach (array_chunk($ids, 100) as $chunk) {
            $deleteUrl = "https://www.zohoapis.eu/crm/v2/Leads_Available";
            Http::withToken($accessToken)->delete($deleteUrl, [
                'ids' => implode(',', $chunk)
            ]);
        }
    }
}