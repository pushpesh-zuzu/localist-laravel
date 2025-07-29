<?php

namespace App\Helpers\Zoho;

use App\Models\PurchaseHistory;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class ZohoFinance
{
    public function integratePurchaseHistory($userId,$logId)
    {
        $accessToken = ZohoHelper::getAccessToken();

        if (!$accessToken) {
            return null;
        }

        $log = PurchaseHistory::where('id', $logId)
               ->first();


        $zohoFinanceId = $this->getZohoFinanceId($accessToken, $log->id);

        $payload = $this->buildFinancePayload($accessToken,$log,$userId);

        $response = $this->sendFinanceToZoho($accessToken, $payload, $zohoFinanceId);


        return $response->json();


    }

    protected function buildFinancePayload($accessToken, $log,$userId)
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
        $lookUpId =ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
        $userName=User::find($userId)->name;
        return [
            'data' => [[
                'Transaction_Id1' => $log->id,
                'Lead_Finance_Lookup' =>$lookUpId,
                 'Name'           => $log->price,
                 'Details'        => $log->details,
                //'Price'           => $log->price,
                'Credits'         => $log->credits,
                'Transaction_Date' => \Carbon\Carbon::parse($log->purchase_Date)->toDateString(),
                'Payment_Type'     => $log->payment_type == 0 ? 'Credit' : 'Debit',
                'Payment_Status'   => $statusText,
            ]]
        ];
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
