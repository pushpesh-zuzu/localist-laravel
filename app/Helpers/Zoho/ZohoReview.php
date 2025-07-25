<?php
namespace App\Helpers\Zoho;

use App\Models\Review;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Support\Facades\Log;


class ZohoReview
{
    public function integrateZohoReview($userId)
    {


        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $zohoId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);

        $payload = $this->buildLeadBuyerPayload($userId, $zohoId);

        $response = $this->sendToZoho($access_token, $payload, $zohoId);

        $responseData = $response->json();

        if (
            isset($responseData['data'][0]['status']) &&
            $responseData['data'][0]['status'] === 'success' &&
            isset($responseData['data'][0]['details']['id'])
        ) {
            $zohoRecordId = $responseData['data'][0]['details']['id'];
            User::where('id', $userId)->update([
                'zoho_record_id' => $zohoRecordId,
            ]);
        }
        return $responseData;

    }


    protected function buildLeadBuyerPayload($userId, $zohoId = null)
    {
        $rating = Review::where('user_id', $userId)->avg('ratings');
        $rating = max(1, min(5, round($rating)));
        $payload = [
            'data' => [[
                'Rating' => $rating
            ]]
        ];

        if (!$zohoId) {
            $payload['data'][0]['created_at'] = now()->format('c');
        }

        return $payload;
    }

    protected function sendToZoho($accessToken, array $payload, $zohoId = null)
    {

        $url = "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/{$zohoId}";

        $method = 'put';

        return Http::withToken($accessToken)->$method($url, $payload);
    }


}
