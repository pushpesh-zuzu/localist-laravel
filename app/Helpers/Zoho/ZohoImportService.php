<?php

namespace App\Helpers\Zoho;

use App\Models\D7SupplierClickOpenReport;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ZohoImportService
{
    public function searchAccountByEmail($access_token, $email)
    {

        $response = Http::withToken($access_token)
            ->get('https://www.zohoapis.eu/crm/v2/Accounts/search', [
                'criteria' => "(Company_Email:equals:$email)"
            ]);


        $data = $response->json();

        if (!empty($data['data'][0]['id'])) {

            return $data['data'][0]['id'];
        }

        return null;
    }


    public function importAccountsWithRelated(array $rows)
    {

        $access_token = ZohoHelper::getAccessToken();
        if (!$access_token) {
            Log::error('Zoho Access Token not found');
            return null;
        }

        $baseUrl = "https://www.zohoapis.eu/crm/v2";
        $chunks = array_chunk($rows, 100);

        foreach ($chunks as $chunkIndex => $chunk) {

            try {

                $lastData = [
                    'email' => null,
                    'name' => null,
                    'phone' => null,
                    'website' => null,
                    'campaign_opened_time' => null,
                    'main_service_type' => null,
                    'lead_source' => null,
                ];

                foreach ($chunk as $index => $row) {

                    if (!empty($row['email'])) {

                        $lastData = [
                            'email' => trim($row['email']),
                            'name' => $row['name'] ?? null,
                            'phone' => $row['phone'] ?? null,
                            'website' => $row['website'] ?? null,
                            'campaign_opened_time' => $row['campaign_opened_time'] ?? null,
                            'main_service_type' => $row['main_service_type'] ?? null,
                            'lead_source' => $row['lead_source'] ?? null,
                        ];
                    } else {

                        $chunk[$index] = array_merge($row, $lastData);
                    }
                }

                // ======================
                //  GROUP BY EMAIL
                // ======================
                $grouped = [];
                foreach ($chunk as $row) {
                    $email = trim($row['email'] ?? '');
                    if (!$email) continue;

                    $grouped[$email][] = $row;
                }


                $insertPayload = ['data' => []];
                $updatePayload = ['data' => []];
                $accountMap = [];

                // ======================
                //  HANDLE ACCOUNTS
                // ======================
                foreach ($grouped as $email => $rowsGroup) {

                    $firstRow = $rowsGroup[0];

                    $accountName = trim($firstRow['name'] ?? '');
                    if (!$accountName) {
                        $accountName = $email;
                    }


                    $phone = isset($firstRow['phone'])
                        ? (string)$firstRow['phone']
                        : null;

                    $accountId = $this->searchAccountByEmail($access_token, $email);


                    if ($accountId) {

                        $updatePayload['data'][] = [
                            'id' => $accountId,
                            'Account_Name' => $accountName,
                            'Lead_Source'  => $firstRow['lead_source'] ?? null,
                            'Phone'        => $phone,
                        ];

                        $accountMap[$email] = $accountId;
                    } else {

                        $insertPayload['data'][] = [
                            'Account_Name' => $accountName,
                            'Company_Email' => $email,
                            'Phone'        => $phone,
                            'Website'      => $firstRow['website'] ?? null,
                            'Campaign_Opened_Time' => $firstRow['lead_source'] ?? null,
                            'Main_Service_Type'    => $firstRow['main_service_type'] ?? null,
                            'Lead_Source'          => $firstRow['lead_aource'] ?? null,
                        ];
                    }
                }

                // ======================
                //  INSERT
                // ======================
                if (!empty($insertPayload['data'])) {

                    $insertRes = Http::withToken($access_token)
                        ->post($baseUrl . '/Accounts', $insertPayload)
                        ->json();


                    foreach ($insertRes['data'] ?? [] as $index => $res) {

                        if (($res['status'] ?? '') === 'success') {

                            $email = $insertPayload['data'][$index]['Company_Email'];
                            $accountMap[$email] = $res['details']['id'];
                        }
                    }
                }

                // ======================
                //  UPDATE
                // ======================
                if (!empty($updatePayload['data'])) {

                    $updateRes = Http::withToken($access_token)
                        ->put($baseUrl . '/Accounts', $updatePayload)
                        ->json();
                }

                // ======================
                // RELATED (MULTIPLE)
                // ======================
                $relatedPayload = ['data' => []];

                foreach ($grouped as $email => $rowsGroup) {

                    $accountId = $accountMap[$email] ?? null;
                    if (!$accountId) continue;

                    foreach ($rowsGroup as $row) {

                        $click = $row['clicks'] ?? null;
                        $open  = $row['opens'] ?? null;


                        if (empty($click) && empty($open)) {
                            continue;
                        }

                        $name = trim($row['email_subject'] ?? '') ?: 'Campaign Activity';

                        // Date format
                        $date = $row['email_date_time'] ?? null;


                        $UniqueKey = md5(json_encode([
                            'account' => $accountId,
                            'date' => trim((string)$date),
                            'click' => (string)$click,
                            'open' => (string)$open,
                            'name' => trim($name),
                        ]));

                        $relatedPayload['data'][] = [
                            'Account' => ['id' => $accountId],
                            'CLICK'   => $click !== null ? (string)$click : null,
                            'OPEN'    => $open !== null ? (string)$open : null,
                            'EMAIL_DATE_TIME' => $date,
                            'SOURCE_D7_CAMPAIGN' => $row['source_d7_campaign'] ?? null,
                            'Name' => $name,
                            'Unique_Key' => $UniqueKey,
                        ];
                    }
                }

                if (!empty($relatedPayload['data'])) {


                    $relatedRes = Http::withToken($access_token)
                        ->post($baseUrl . '/Marketing_Contact_History/upsert', [
                            'data' => $relatedPayload['data'],
                            'duplicate_check_fields' => ['Unique_Key']
                        ])
                        ->json();
                }

                usleep(500000);
            } catch (\Throwable $e) {

                Log::error('Zoho Import Exception: ' . $e->getMessage(), [
                    'line' => $e->getLine(),
                    'chunk_index' => $chunkIndex
                ]);
            }
        }

        Log::info('Import Completed');

        return true;
    }



    public function addMarketingContactHistory(string $accountId, string $messageId)
    {
        $relatedPayload = ['data' => []];
        $access_token = ZohoHelper::getAccessToken();
        $d7OpenEmailReport = EmailLog::where('message_id', $messageId)->first();
        $d7OpenReport      = D7SupplierClickOpenReport::where('message_id', $messageId)->first();

        $baseUrl = "https://www.zohoapis.eu/crm/v2";

        if (!$d7OpenReport) {
            return $relatedPayload;
        }

        $click = $d7OpenReport->click_count ?? 0;
        $open  = $d7OpenReport->open_count ?? 0;

        $open_at  = $d7OpenReport->open_at;
        $click_at = $d7OpenReport->click_at;


        if ($click == 0 && $open == 0) {
            return $relatedPayload;
        }

        // subject
        $name = $d7OpenEmailReport ? trim($d7OpenEmailReport->subject ?? '')     : '';

        $dateRaw = $open_at ?? $click_at ?? now();

        $date = $dateRaw
            ? Carbon::parse($dateRaw)->format('n/j/Y H:i:s')
            : null;

        // unique key
        $UniqueKey = md5(json_encode([
            'account' => $accountId,
            'date'    => (string)$date,
            'click'   => (string)$click,
            'open'    => (string)$open,
            'name'    => $name . $messageId,
        ]));

        $relatedPayload['data'][] = [
            'Account'          => ['id' => $accountId],
            'CLICK'            => (string)$click,
            'OPEN'             => (string)$open,
            'EMAIL_DATE_TIME'  => $date,
            'SOURCE'           => 'D7 Supplier Send Mail',
            'Name'             => $name ?: 'D7 Supplier Send Mail',
            'Unique_Key'       => $UniqueKey,
        ];


        if (!empty($relatedPayload['data'])) {

            $url = $baseUrl . '/Marketing_Contact_History/upsert';

            $response = Http::withToken($access_token)
                ->post($url, [
                    'data' => $relatedPayload['data'],
                    'duplicate_check_fields' => ['Unique_Key']
                ]);

            $responseData = $response->json();
            $errorMessage = $response->failed() ? json_encode($responseData) : null;

            $dbRecordId = $d7OpenReport->id ?? null;
            $dbTable    = 'd7_supplier_click_open_reports';
            $supplierId = $accountId ?? null;

            ZohoHelper::logZohoRequest(
                'syncD7SuppliersToZohoAccounts',
                $url,
                $relatedPayload,
                $responseData,
                $errorMessage,
                $supplierId,
                $dbRecordId,
                $dbTable
            );
        }
    }





    public function updateLeadSourceForAccount($accountId)
    {
        try {

            if (empty($accountId)) {
                Log::warning('Account ID is empty');
                return false;
            }

            $access_token = ZohoHelper::getAccessToken();

            if (!$access_token) {
                Log::error('Zoho Access Token not found');
                return false;
            }

            $baseUrl = "https://www.zohoapis.eu/crm/v2";

            // Prepare payload
            $updatePayload = [
                'data' => [
                    [
                        'id' => $accountId,
                        'Lead_Source' => 'D7 Supplier',
                    ]
                ]
            ];

            Log::info('Zoho Update Start', [
                'account_id' => $accountId,
                'payload'    => $updatePayload
            ]);

            // API Call
            $response = Http::withToken($access_token)
                ->put($baseUrl . '/Accounts', $updatePayload);

            $updateRes = $response->json();

            Log::info('Zoho Update Response', [
                'account_id' => $accountId,
                'response'   => $updateRes
            ]);

            // Check success
            if (
                isset($updateRes['data'][0]['code']) &&
                $updateRes['data'][0]['code'] === 'SUCCESS'
            ) {
                return true;
            }

            Log::warning('Zoho Update Failed', [
                'account_id' => $accountId,
                'response'   => $updateRes
            ]);

            return false;
        } catch (\Throwable $e) {

            Log::error('Zoho Update Exception', [
                'account_id' => $accountId,
                'error'      => $e->getMessage(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
