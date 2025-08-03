<?php

namespace App\Helpers\Zoho;

use App\Models\PurchaseHistory;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoFinance
{
    public function integratePurchaseHistory($userId,$logId)
    {
        $accessToken = ZohoHelper::getAccessToken();

        Log::info('Zoho Finance');
        if (!$accessToken) {
            return null;
        }

        $log = PurchaseHistory::where('id', $logId)
               ->first();

        $payload = $this->buildFinancePayload($accessToken,$log,$userId);

        Log::info('Zoho Finance', [
            'user_id' => $userId,
            'payload' => $payload,
        ]);
        if (!$payload) return null;

        $response = $this->upsertToZohoService($accessToken, $payload);

        $responseData = $response->json();

        if (
            isset($responseData['data'][0]['status']) &&
            $responseData['data'][0]['status'] === 'success' &&
            isset($responseData['data'][0]['details']['id'])
        ) {
            $zohoRecordId = $responseData['data'][0]['details']['id'];

            PurchaseHistory::where('id', $logId)->update([
                'zoho_finance_id' => $zohoRecordId,
            ]);
        }


        Log::info('Zoho API for Finance ', [
            'user_id' => $userId,
            'response' => $response->json(),
        ]);


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
            ]],
            'duplicate_check_fields' => ['Transaction_Id1']
        ];
    }

    protected function upsertToZohoService($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Purchase_History/upsert', $payload);
    }



    // protected function getZohoFinanceId($accessToken, $transactionId)
    // {
    //     $response = Http::withToken($accessToken)
    //         ->get('https://www.zohoapis.eu/crm/v2/Purchase_History/search', [
    //             'criteria' => "(Transaction_Id1:equals:$transactionId)"
    //         ]);

    //     $data = $response->json();

    //     return $data['data'][0]['id'] ?? null;
    // }

    // protected function sendFinanceToZoho($accessToken, array $payload, $zohoFinanceId = null)
    // {
    //     $url = $zohoFinanceId
    //         ? "https://www.zohoapis.eu/crm/v2/Purchase_History/{$zohoFinanceId}"
    //         : "https://www.zohoapis.eu/crm/v2/Purchase_History";

    //     $method = $zohoFinanceId ? 'put' : 'post';

    //     return Http::withToken($accessToken)->$method($url, $payload);
    // }
}
