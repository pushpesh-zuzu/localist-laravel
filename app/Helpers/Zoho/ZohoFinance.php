<?php

namespace App\Helpers\Zoho;

use App\Models\PurchaseHistory;
use Illuminate\Support\Facades\Http;

class ZohoFinance
{
    public function integratePurchaseHistory($userId)
    {
        $accessToken = ZohoHelper::getAccessToken();

        if (!$accessToken) {
            return null;
        }

        $logs = PurchaseHistory::where('user_id', $userId)
               ->get();

        foreach ($logs as $log) {
            $zohoFinanceId = $this->getZohoFinanceId($accessToken, $log->id);

            $payload = $this->buildFinancePayload($accessToken,$log);

            $response = $this->sendFinanceToZoho($accessToken, $payload, $zohoFinanceId);

        }

        return $response->json();


    }

    protected function buildFinancePayload($accessToken, $log)
    {
        $statusText = 'Unknown';
        switch ($log->status) {
            case 0:
                $statusText = 'Pending';
                break;
            case 1:
                $statusText = 'Success';
                break;
            case 2:
                $statusText = 'Failed';
                break;
        }
        $lookUpId = $this->getZohoLeadBuyerId($accessToken, $log->user_id);
        return [
            'data' => [[
                'Transaction_Id1' => $log->id,
                'Lead_Finance_Lookup' =>$lookUpId,
                'Name'           => ''.$log->id,
                'Price'           => $log->price,
                'Credits'         => $log->credits,
                'Transaction_Date' => \Carbon\Carbon::parse($log->purchase_Date)->toDateString(),
                'Payment_Type'     => $log->payment_type == 0 ? 'credit' : 'debit',
                'Payment_Status'   => $statusText,
            ]]
        ];
    }

    protected function getZohoLeadBuyerId($accessToken, $userId)
    {
         $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/search', [
                'criteria' => "(Lead_buyer_auto_id:equals:{$userId})"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }

    protected function getZohoFinanceId($accessToken, $transactionId)
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/Purchase_History/search', [
                'criteria' => "(Transaction_Id:equals:$transactionId)"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }

    protected function sendFinanceToZoho($accessToken, array $payload, $zohoFinanceId = null)
    {
        $url = $zohoFinanceId
            ? "https://www.zohoapis.eu/crm/v2/Purchase_History/{$zohoFinanceId}"
            : "https://www.zohoapis.eu/crm/v2/Purchase_History";

        $method = $zohoFinanceId ? 'put' : 'post';

        return Http::withToken($accessToken)->$method($url, $payload);
    }
}
