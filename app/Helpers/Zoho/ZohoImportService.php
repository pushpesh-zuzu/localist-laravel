<?php

namespace App\Helpers\Zoho;

use App\Models\D7SupplierClickOpenReport;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ZohoImportService
{


    public function importAccountsWithRelated(array $rows)
    {
        $inserted = [];
        $updated = [];
        $relatedCount = 0;
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
                            // 'name' => $row['name'] ?? null,
                            // 'phone' => $row['phone'] ?? null,
                            //'website' => $row['website'] ?? null,
                            'campaign_opened_time' => $row['campaign_opened_time'] ?? null,
                            // 'main_service_type' => $row['main_service_type'] ?? null,
                            // 'lead_source' => $row['lead_source'] ?? null,
                        ];
                    } else {

                        $chunk[$index] = array_merge($row, $lastData);
                    }
                }

                // ======================
                // GROUP BY EMAIL
                // ======================
                $grouped = [];

                foreach ($chunk as $row) {

                    $email = trim($row['email'] ?? '');

                    if (!$email) continue;

                    $grouped[$email][] = $row;
                }

                $accountMap = [];

                // ======================
                // HANDLE ACCOUNTS
                // ======================
                foreach ($grouped as $email => $rowsGroup) {

                    $firstRow = $rowsGroup[0];

                    $accountName = trim($firstRow['name'] ?? '');
                    if (!$accountName) {
                        $accountName = $email;
                    }

                    $phone = $firstRow['phone'] ?? null;
                    $website = $firstRow['website'] ?? null;

                    // normalize phone
                    $phone = $phone ? preg_replace('/\D/', '', $phone) : null;

                    $accountId = $this->findZohoAccount(
                        $access_token,
                        $accountName,
                        $email,
                        $phone,
                        $website
                    );

                    $payload = [
                        'data' => [[
                            //  'Account_Name' => $accountName,
                            'Company_Email' => $email,
                            //  'Phone' => $phone,
                            //  'Website' => $website,
                            // 'Main_Service_Type' => $firstRow['main_service_type'] ?? null,
                            // 'Lead_Source' => $firstRow['lead_source'] ?? null,
                        ]]
                    ];

                    // ======================
                    // UPDATE IF EXISTS
                    // ======================

                    if ($accountId) {

                        $updated[$email] = $email;
                        $zohoRecord = Http::withToken($access_token)
                            ->get($baseUrl . "/Accounts/{$accountId}")
                            ->json();

                        $zohoData = $zohoRecord['data'][0] ?? null;

                        if ($zohoData) {
                            $this->applyZohoMergeLogic($payload, $zohoData);
                        }

                        $payload['data'][0]['id'] = $accountId;

                        Http::withToken($access_token)
                            ->put($baseUrl . "/Accounts", $payload)
                            ->json();

                        $accountMap[$email] = $accountId;
                    }

                    // ======================
                    // INSERT
                    // ======================

                    else {

                        $insertRes = Http::withToken($access_token)
                            ->post($baseUrl . '/Accounts', $payload)
                            ->json();

                        $res = $insertRes['data'][0] ?? null;

                        if (($res['status'] ?? '') === 'success') {
                            $inserted[$email] = $email;
                            $accountMap[$email] = $res['details']['id'];
                        }

                        // DUPLICATE HANDLER
                        elseif (($res['code'] ?? '') === 'DUPLICATE_DATA') {

                            $duplicateId = $res['details']['id'] ?? null;

                            if ($duplicateId) {
                                $updated[$email] = $email;

                                $zohoRecord = Http::withToken($access_token)
                                    ->get($baseUrl . "/Accounts/{$duplicateId}")
                                    ->json();

                                $zohoData = $zohoRecord['data'][0] ?? null;

                                if ($zohoData) {
                                    $this->applyZohoMergeLogic($payload, $zohoData);
                                }

                                $payload['data'][0]['id'] = $duplicateId;

                                Http::withToken($access_token)
                                    ->put($baseUrl . "/Accounts", $payload)
                                    ->json();

                                $accountMap[$email] = $duplicateId;
                            }
                        }
                    }
                }

                // ======================
                // RELATED MODULE
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

                        $date = $row['email_date_time'] ?? null;

                        $UniqueKey = md5(json_encode([
                            'account' => $accountId,
                            'date' => trim((string)$date),
                            'click' => (string)$click,
                            'open' => (string)$open,
                            'name' => trim($name),
                        ]));

                        $relatedCount++;
                        $relatedPayload['data'][] = [
                            'Account' => ['id' => $accountId],
                            'CLICK' => $click !== null ? (string)$click : null,
                            'OPEN' => $open !== null ? (string)$open : null,
                            'EMAIL_DATE_TIME' => $date,
                            'SOURCE_D7_CAMPAIGN' => $row['source_d7_campaign'] ?? null,
                            'Name' => $name,
                            'Unique_Key' => $UniqueKey,
                        ];
                    }
                }

                if (!empty($relatedPayload['data'])) {

                    Http::withToken($access_token)
                        ->post($baseUrl . '/Marketing_Contact_History/upsert', [
                            'data' => $relatedPayload['data'],
                            'duplicate_check_fields' => ['Unique_Key']
                        ])
                        ->json();
                }

                usleep(2000000);
            } catch (\Throwable $e) {

                Log::error('Zoho Import Exception: ' . $e->getMessage(), [
                    'line' => $e->getLine(),
                    'chunk_index' => $chunkIndex
                ]);
            }
        }

        return [
            'inserted_count' => count($inserted),
            'updated_count' => count($updated),
            'inserted_emails' => array_values($inserted),
            'updated_emails' => array_values($updated),
            'related_records' => $relatedCount
        ];

        Log::info('Import Completed');
    }


    private function applyZohoMergeLogic(&$payload, $zohoData)
    {
        // ================= EMAIL =================

        $zohoEmail = $zohoData['Company_Email'] ?? null;
        $zohoEmail2 = $zohoData['Company_Email_2'] ?? null;
        $newEmail  = $payload['data'][0]['Company_Email'] ?? null;

        if (!empty($zohoEmail) && !empty($newEmail)) {

            if (strtolower($zohoEmail) !== strtolower($newEmail)) {

                if (empty($zohoEmail2)) {
                    $payload['data'][0]['Company_Email_2'] = $newEmail;
                }

                unset($payload['data'][0]['Company_Email']);
            }
        }

        // ================= PHONE =================

        $zohoPhone  = $zohoData['Phone'] ?? null;
        $zohoPhone2 = $zohoData['Phone_2'] ?? null;
        $newPhone   = $payload['data'][0]['Phone'] ?? null;

        if (!empty($newPhone)) {

            $normalize = fn($p) => preg_replace('/\D/', '', $p);

            $zohoPhoneNorm  = $zohoPhone ? $normalize($zohoPhone) : null;
            $zohoPhone2Norm = $zohoPhone2 ? $normalize($zohoPhone2) : null;
            $newPhoneNorm   = $normalize($newPhone);

            if ($newPhoneNorm === $zohoPhoneNorm || $newPhoneNorm === $zohoPhone2Norm) {

                unset($payload['data'][0]['Phone']);
            } else {

                if (empty($zohoPhone)) {

                    $payload['data'][0]['Phone'] = $newPhone;
                } elseif (empty($zohoPhone2)) {

                    $payload['data'][0]['Phone_2'] = $newPhone;
                    unset($payload['data'][0]['Phone']);
                } else {

                    unset($payload['data'][0]['Phone']);
                }
            }
        }

        // ================= WEBSITE =================

        $zohoWebsite = $zohoData['Website'] ?? null;

        if (!empty($zohoWebsite)) {
            unset($payload['data'][0]['Website']);
        }

        // ================= SERVICE TYPE =================

        $zohoService = $zohoData['Main_Service_Type'] ?? null;

        if (!empty($zohoService)) {
            unset($payload['data'][0]['Main_Service_Type']);
        }
    }



    protected function searchZohoAccount($accessToken, $criteria)
    {
        return Http::withToken($accessToken)
            ->get("https://www.zohoapis.eu/crm/v2/Accounts/search", [
                'criteria' => $criteria
            ]);
    }

    private function findZohoAccount($access_token, $name, $email, $phone, $website)
    {
        $zohoRecordId = null;

        if (!$zohoRecordId && !empty($name)) {

            $res = $this->searchZohoAccount($access_token, "(Account_Name:equals:" . urlencode($name) . ")");
            $zohoRecordId = $res->json()['data'][0]['id'] ?? null;
        }

        if (!$zohoRecordId && !empty($website)) {

            $res = $this->searchZohoAccount($access_token, "(Website:equals:" . urlencode($website) . ")");
            $zohoRecordId = $res->json()['data'][0]['id'] ?? null;
        }

        if (!$zohoRecordId && !empty($phone)) {

            $res = $this->searchZohoAccount($access_token, "(Phone:equals:$phone)");
            $zohoRecordId = $res->json()['data'][0]['id'] ?? null;
        }

        if (!$zohoRecordId && !empty($phone)) {

            $res = $this->searchZohoAccount($access_token, "(Phone_2:equals:$phone)");
            $zohoRecordId = $res->json()['data'][0]['id'] ?? null;
        }

        if (!$zohoRecordId && !empty($email)) {

            $res = $this->searchZohoAccount($access_token, "(Company_Email:equals:$email)");
            $zohoRecordId = $res->json()['data'][0]['id'] ?? null;
        }

        return $zohoRecordId;
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
            ? Carbon::parse($dateRaw)->format('d-m-Y H:i:s')
            : null;

        // unique key
        // $UniqueKey = md5(json_encode([
        //     'account' => $accountId,
        //     'date'    => (string)$date,
        //     'click'   => (string)$click,
        //     'open'    => (string)$open,
        //     'name'    => $name . $messageId,
        // ]));

        $UniqueKey = md5(json_encode([
            'account'   => $accountId,
            'messageId' => $messageId,
            'subject'   => trim(strtolower($name)), // normalize subject
        ]));

        $relatedPayload['data'][] = [
            'Account'          => ['id' => $accountId],
            'CLICK'            => (string)$click,
            'OPEN'             => (string)$open,
            'EMAIL_DATE_TIME'  => $date,
            'SOURCE_D7_CAMPAIGN'  => 'D7 Supplier Send Mail',
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
                'syncD7SuppliersClickOpenReports',
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
}
