<?php

namespace App\Helpers\Zoho;
use Carbon\Carbon;
use App\Models\PurchaseHistory;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoLeadAvailable
{
    /**
     * Integrate available leads in batches and return inserted count
     */
    public function integrateAvailableLeadsBatch($userId, $leads)
    {
        $accessToken = ZohoHelper::getAccessToken();
        $lookupId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);

        if (!$lookupId || empty($leads)) {
            return ['inserted_count' => 0];
        }

        // ✅ Build payloads
        $payloads = $this->buildAvailableLeadsBatchPayload($userId, $lookupId, $leads);

        $insertedCount = 0;

        foreach ($payloads as $payload) {
            $response = $this->upsertToZohoAvailableLeads($accessToken, $payload);
            $data = $response->json('data') ?? [];

            // Each successful record counts as one inserted or updated record
            $insertedCount += count($data);
        }

        return ['inserted_count' => $insertedCount];
    }

    /**
     * Builds batched payloads of 100 records each
     */
    protected function buildAvailableLeadsBatchPayload($userId, $lookupId, $leads)
    {
        $payloads = [];
        $allRecords = [];

        foreach ($leads as $lead) {
            if (!$lead->customer || !$lead->category) {
                continue;
            }

            $allRecords[] = [
                'Name' => $lead->customer->name ?? '',
                'Postcode' => $lead->postcode ?? '',
                'Email' => $lead->customer->email ?? '',
                'Category' => $lead->category->name ?? '',
                'Phone_Number' => $lead->customer->phone ?? '',
                'Credit' => $lead->credit_score ?? '',
                'Questions' => $lead->questions ?? '',
                'User_Id' => strval($userId),
                'Synced_At' => Carbon::now('Asia/Kolkata')->format('jS M Y, g:i A'),
                'Lead_Available_Lookup' => $lookupId,
            ];
        }

        foreach (array_chunk($allRecords, 100) as $chunk) {
            $payloads[] = ['data' => $chunk];
        }

        return $payloads;
    }

    /**
     * Perform the actual upsert
     */
    protected function upsertToZohoAvailableLeads($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Leads_Available/upsert', $payload);
    }

    /**
     * Delete all existing Leads_Available for a user and return count
     */
    public static function deleteLeadsAvailableRecords($userId)
    {
        $accessToken = ZohoHelper::getAccessToken();

        $searchUrl = "https://www.zohoapis.eu/crm/v2/Leads_Available/search?criteria=(User_Id:equals:{$userId})";
        $response = Http::withToken($accessToken)->get($searchUrl);
        $records = $response->json('data') ?? [];

        if (empty($records)) {
            return ['deleted_count' => 0];
        }

        $ids = collect($records)->pluck('id')->toArray();
        $totalDeleted = count($ids);

        foreach (array_chunk($ids, 100) as $chunk) {
            $deleteUrl = "https://www.zohoapis.eu/crm/v2/Leads_Available";
            Http::withToken($accessToken)->delete($deleteUrl, [
                'ids' => implode(',', $chunk)
            ]);
        }

        return ['deleted_count' => $totalDeleted];
    }
}