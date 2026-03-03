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
            $data = $response->json('data') ?? [];

            $insertedCount += count($data);
        }

        return ['inserted_count' => $insertedCount];
    }

    /**
     * Builds batched payloads of 100 records each
     */
    protected function buildAvailableSellersBatchPayload($leadId, $lookupId, $sellers)
    {


        $payloads = [];
        $allRecords = [];

        foreach ($sellers as $seller) {


            if (is_array($seller)) {
                $seller = (object) $seller;
            }

            $user = User::find($seller->id);

            if (!$user || empty($user->zoho_record_id)) {
                continue;
            }

            $sellerlookupId = $user->zoho_record_id;


            if (empty($seller->name) || empty($seller->service_name)) {
                continue;
            }
            if ($seller->avg_rating > 0) {
                $rating = $seller->avg_rating . ' Star';
            } else {
                $rating = '-';
            }
            if ($seller->quicktorespond > 0) {
                $quicktorespond = 'Yes';
            } else {
                $quicktorespond = 'No';
            }
            $str = fn($v) => isset($v) ? (string) $v : null;
            $allRecords[] = [
                'Name' => $seller->name ?? '',
                'Lead_Buyer_Name' => $sellerlookupId ?? '',

                'Postcode' => $seller->postcode ?? '',
                'Email' => $seller->email ?? '',
                'Phone' => $seller->phone ?? '',
                'Service_Name' => $seller->service_name ?? '',
                'Credit_Score' => $str($seller->credit_score) ?? '',
                'Total_Credit' => $str($seller->total_credit) ?? '',
                'Quick_Responder' => $quicktorespond ?? '',
                'Lead_Request_Id' => strval($leadId),
                'Rating' => $rating ?? '',
                'Seller_Distance' => ($seller->distance ?? '') . ' miles away',
                'Synced_At' => Carbon::now()->format('jS M Y, g:i A'),
                'Quote_Request_Lookup' => $lookupId,
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
    protected function upsertToZohoYourMatchesForLeads($accessToken, array $payload)
    {
        $response =  Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Your_Matches/upsert', $payload);


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
        $page = 1;
        $perPage = 200;

        try {

            do {
                $searchUrl = "https://www.zohoapis.eu/crm/v2/Your_Matches/search?criteria=(Lead_Request_Id:equals:{$leadId})&page={$page}&per_page={$perPage}";

                $response = Http::withToken($accessToken)->get($searchUrl);

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
                        $totalDeleted += count($chunk);
                    }
                }

                $page++;
            } while (count($records) === $perPage);
        } catch (\Exception $e) {

            Log::error('Zoho Delete Error', [
                'leadId' => $leadId,
                'error' => $e->getMessage()
            ]);
        }

        Log::info('Zoho Delete End', ['deleted_count' => $totalDeleted]);

        return ['deleted_count' => $totalDeleted];
    }
}
