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

        $payload = $this->buildLeadBuyerPayload($userId);

        if (!$payload) return null;

        $response = $this->updateZohoLeadBuyer($access_token, $zohoId,$payload);
        $responseData = $response->json();


        Log::info('Zoho Review Response', [
            'user_id' => $userId,
            'payload' => $payload,
            'response' => $responseData,
        ]);

        return $responseData;

    }


    protected function buildLeadBuyerPayload($userId)
    {
        $rating = Review::where('user_id', $userId)->avg('ratings');
        $rating = intval(max(1, min(5, round($rating))));
        $payload = [
            'data' => [[
                'Rating' => 5
            ]]

        ];

        return $payload;
    }


    protected function updateZohoLeadBuyer($accessToken, $zohoRecordId, array $payload)
    {
        return Http::withToken($accessToken)
            ->put("https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/{$zohoRecordId}", [
                'data' => [$payload],
            ]);
    }


    // protected function sendToZoho($accessToken, array $payload, $zohoId = null)
    // {

    //     $url = "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/{$zohoId}";

    //     $method = 'put';

    //     return Http::withToken($accessToken)->$method($url, $payload);
    // }


}
