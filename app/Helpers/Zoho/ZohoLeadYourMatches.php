<?php

namespace App\Helpers\Zoho;

use Carbon\Carbon;
use App\Models\PurchaseHistory;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoLeadYourMatches
{
    /**
     * Integrate available leads in batches and return inserted count
     */
    public function integrateYourMatchesLeadsBatch($leadId, $lookupId, $sellers)
    {
        $accessToken = ZohoHelper::getAccessToken();

        if (!$lookupId || empty($sellers)) {
            return ['inserted_count' => 0];
        }

        $payloads = $this->buildAvailableSellersBatchPayload($leadId, $lookupId, $sellers);

        $insertedCount = 0;

        foreach ($payloads as $payload) {

            $response = $this->upsertToZohoYourMatchesForLeads($accessToken, $payload);

            Log::info('Zoho Upsert Response', $response->json()); // 👈 important

            $data = $response->json('data') ?? [];

            foreach ($data as $record) {
                if (isset($record['status']) && $record['status'] === 'success') {
                    $insertedCount++;
                }
            }
        }

        return ['inserted_count' => $insertedCount];
    }

    /**
     * Builds batched payloads of 100 records each
     */
    protected function buildAvailableSellersBatchPayload($leadId, $lookupId, $sellers)
    {
        $payloads = [];
        $uniqueRecords = [];

        foreach ($sellers as $seller) {

            if (is_array($seller)) {
                $seller = (object) $seller;
            }

            $user = User::find($seller->id);

            if (!$user || empty($user->zoho_record_id)) {
                continue;
            }

            if (empty($seller->name) || empty($seller->service_name)) {
                continue;
            }

            $sellerlookupId = $user->zoho_record_id;

            // 🔥 UNIQUE KEY (important)
            $uniqueKey = $leadId . '_' . $sellerlookupId;

            if (isset($uniqueRecords[$uniqueKey])) {
                continue; // skip duplicate seller
            }

            $rating = ($seller->avg_rating > 0)
                ? $seller->avg_rating . ' Star'
                : '-';

            $quicktorespond = ($seller->quicktorespond > 0)
                ? 'Yes'
                : 'No';

            $uniqueRecords[$uniqueKey] = [
                'Name' => $seller->business_profile_name ?? '',
                'Lead_Buyer_Name' => $sellerlookupId,
                'Postcode' => $seller->postcode ?? '',
                'Email' => $seller->email ?? '',
                'Phone' => $seller->phone ?? '',
                'Service_Name' => $seller->service_name ?? '',
                'Credit_Score' => isset($seller->credit_score) ? (string)$seller->credit_score : '',
                'Total_Credit' => isset($seller->total_credit) ? (string)$seller->total_credit : '',
                'Quick_Responder' => $quicktorespond,
                'Lead_Request_Id' => (string)$leadId,
                'Rating' => $rating,
                'Seller_Distance' => isset($seller->distance)
                    ? $seller->distance . ' miles away'
                    : '',
                'Synced_At' => Carbon::now()->format('Y-m-d H:i:s'),
                'Quote_Request_Lookup' => $lookupId,
            ];
        }

        $allRecords = array_values($uniqueRecords);

        foreach (array_chunk($allRecords, 100) as $chunk) {
            $payloads[] = [
                'data' => $chunk,
                'duplicate_check_fields' => ['Lead_Buyer_Name']
            ];
        }

        return $payloads;
    }

    /**
     * Perform the actual upsert
     */
    protected function upsertToZohoYourMatchesForLeads($accessToken, array $payload)
    {
        $response = Http::withToken($accessToken)
            ->retry(3, 1000)
            ->post('https://www.zohoapis.eu/crm/v2/Your_Matches/upsert', $payload);

        if (!$response->successful()) {
            Log::error('Zoho Upsert Failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }

        return $response;
    }

    /**
     * Delete all existing Your_Matches for a user and return count
     */


    public static function deleteYourMatchesLeadsRecords($leadId)
    {
        Log::info('Zoho Delete Start', ['leadId' => $leadId]);

        $accessToken = ZohoHelper::getAccessToken();
        $totalDeleted = 0;
        $maxAttempts = 10; // safety stop
        $attempt = 0;

        try {

            while ($attempt < $maxAttempts) {

                $searchUrl = "https://www.zohoapis.eu/crm/v2/Your_Matches/search?criteria=(Lead_Request_Id:equals:{$leadId})&per_page=200";

                $response = Http::withToken($accessToken)->get($searchUrl);

                // If no content (204)
                if ($response->status() == 204) {
                    Log::info('Zoho Delete: No records found');
                    break;
                }

                if (!$response->successful()) {
                    Log::error('Zoho Search Failed', $response->json());
                    break;
                }

                $records = $response->json('data') ?? [];

                if (empty($records)) {
                    break;
                }

                $ids = collect($records)->pluck('id')->toArray();

                foreach (array_chunk($ids, 100) as $chunk) {

                    $deleteUrl = "https://www.zohoapis.eu/crm/v2/Your_Matches?ids=" . implode(',', $chunk);

                    $deleteResponse = Http::withToken($accessToken)
                        ->retry(3, 1000)
                        ->delete($deleteUrl);

                    if ($deleteResponse->successful()) {

                        $deleteData = $deleteResponse->json('data') ?? [];

                        foreach ($deleteData as $item) {
                            if (($item['status'] ?? '') === 'success') {
                                $totalDeleted++;
                            }
                        }
                    } else {
                        Log::error('Zoho Delete Failed', $deleteResponse->json());
                    }
                }

                $attempt++;
                sleep(2);
            }
        } catch (\Exception $e) {

            Log::error('Zoho Delete Exception', [
                'leadId' => $leadId,
                'error' => $e->getMessage()
            ]);
        }

        Log::info('Zoho Delete End', [
            'deleted_count' => $totalDeleted,
            'attempts' => $attempt
        ]);

        return ['deleted_count' => $totalDeleted];
    }
}
