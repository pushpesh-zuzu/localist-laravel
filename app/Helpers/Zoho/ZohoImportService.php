<?php

namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoImportService
{






    private function searchAccountByEmail($access_token, $email)
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
                // 1. GROUP BY EMAIL
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
                // 3. HANDLE ACCOUNTS
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
                // 4. INSERT
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
                // 5. UPDATE
                // ======================
                if (!empty($updatePayload['data'])) {

                    $updateRes = Http::withToken($access_token)
                        ->put($baseUrl . '/Accounts', $updatePayload)
                        ->json();

                   
                }

                // ======================
                // 6. RELATED (MULTIPLE)
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
}
