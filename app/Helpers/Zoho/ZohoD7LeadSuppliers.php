<?php

namespace App\Helpers\Zoho;

use App\Models\AbandonedUser;
use App\Models\D7LeadSupplier;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ZohoD7LeadSuppliers
{


    public function integrateD7LeadSupplier(int $supplierId, string $action = 'insert')
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $payload = $this->buildCustomerPayload($supplierId);

        $responseData = null;
        $errorMessage = null;

        try {

            if ($action == 'insert') {
                $response = $this->upsertToZohoService($access_token, $payload);
            } else {

                $supplierLead = D7LeadSupplier::where('id', $supplierId)->first();
                if (!empty($supplierLead->zoho_record_id)) {

                    $response = $this->updateZohoRecord($access_token, $supplierLead->zoho_record_id, $payload);
                } else {
                    $response = $this->upsertToZohoService($access_token, $payload);
                }
            }

            $responseData = $response->json();

            if (
                isset($responseData['data'][0]['status']) &&  $responseData['data'][0]['status'] === 'success' &&  isset($responseData['data'][0]['details']['id'])
            ) {

                $zohoRecordId = $responseData['data'][0]['details']['id'];

                $updatePayload = [
                    'data' => [[
                        'id' => $zohoRecordId,
                        'User_Auto_Id' => $zohoRecordId
                    ]]
                ];

                Http::withToken($access_token)
                    ->put("https://www.zohoapis.eu/crm/v2/D7_Lead_Suppliers", $updatePayload);


                D7LeadSupplier::where('id', $supplierId)
                    ->update(['zoho_record_id' => $zohoRecordId]);
            }
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }

        $dbRecordId = $supplierId;           // jo record update ho raha hai
        $dbTable    = 'd7_lead_suppliers';

        ZohoHelper::logZohoRequest(
            'integrateD7LeadSupplier',
            'https://www.zohoapis.eu/crm/v2/D7_Lead_Suppliers/upsert',
            $payload ?? null,
            $responseData ?? null,
            $errorMessage ?? null,
            $supplierId ?? null,
            $dbRecordId ?? null,
            $dbTable ?? null
        );

        return $responseData;
    }


    protected function buildCustomerPayload($userId)
    {
        $user = D7LeadSupplier::findOrFail($userId);

        $datetime  = new DateTime($user->created_at, new DateTimeZone('Europe/London'));
        $formatted = $datetime->format('Y-m-d\TH:i:sP');

        // helper for string casting
        $str = fn($v) => isset($v) ? (string) $v : null;

        $payload = [
            'data' => [[
                'User_Auto_Id'  => $user->zoho_record_id ?? $user->id,
                'Name'          => $user->name,
                'Supplier_Name' => $user->name,
                'Email'         => $user->email,

                'Phone'    => $str($user->phone),
                'website'  => $str($user->website),
                'Category' => $str($user->category),

                'Address_Line_1'  => $str($user->address1),
                'Address_Line_2'  => $str($user->address2),
                'Region_State'    => $str($user->region),
                'ZIP_Postal_Code' => $str($user->zip),
                'Country'         => $str($user->country),

                'Google_Reviews' =>
                $user->google_stars
                    ? $user->google_stars . ' (' . ($user->google_review_count ?? 0) . ')'
                    : null,

                'Google_Rank' => $str($user->google_rank),

                'Yelp_Reviews' =>
                $user->yelp_stars
                    ? $user->yelp_stars . ' (' . ($user->yelp_review_count ?? 0) . ')'
                    : null,

                'Facebook_Reviews' =>
                $user->facebook_stars
                    ? $user->facebook_stars . ' (' . ($user->facebook_review_count ?? 0) . ')'
                    : null,

                // Instagram
                'InstagramFollower'      => $str($user->instagram_followers),
                'Instagram_Follows'      => $str($user->instagram_follows),
                'InstagramisBusiness'    => $str($user->instagram_is_business),
                'Instagram_Media_Count'  => $str($user->instagram_media_count),

                // Social URLs
                'Facebook_Page'  => $str($user->facebook_url),
                'Instagram_Page' => $str($user->instagram_url),
                'LinkedIn_Page'  => $str($user->linkedin_url),
                'Twitter_Page'   => $str($user->twitter_url),

                // Tracking / Tech flags (ALL strings now)
                'Facebook_Pixel_Installed' => $str($user->facebook_pixel),
                'Schema_Enabled'           => $str($user->schema_enabled),
                'Google_Remarketing'       => $str($user->google_remarketing),
                'Google_Analytics'         => $str($user->google_analytics),
                'LinkedIn_Analytics'       => $str($user->linkedin_analytics),

                'Uses_Shopify'   => $str($user->uses_shopify),
                'Uses_WordPress' => $str($user->uses_wordpress),
                'Mobile_Friendly' => $str($user->mobile_friendly),

                'Lead_Service_Keyword' => $str($user->lead_service),
                'created_at'           => $formatted,
            ]],
            'duplicate_check_fields' => ['User_Auto_Id']
        ];

        return $payload;
    }




    public function deleteZohoRecord(string $zohoRecordId)
    {
        $access_token = ZohoHelper::getAccessToken();

        $response = Http::withToken($access_token)
            ->delete("https://www.zohoapis.eu/crm/v2/D7_Lead_Suppliers/{$zohoRecordId}");

        if ($response->failed()) {
            Log::error('Zoho delete failed', [
                'record_id' => $zohoRecordId,
                'response' => $response->json(),
            ]);

            throw new \Exception('Failed to delete Zoho record');
        }
    }

    protected function upsertToZohoService($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/D7_Lead_Suppliers/upsert', $payload);
    }


    protected function updateZohoRecord(string $accessToken, string $zohoRecordId, array $payload)
    {
        return Http::withToken($accessToken)
            ->put(
                "https://www.zohoapis.eu/crm/v2/D7_Lead_Suppliers/{$zohoRecordId}",
                [
                    'data' => $payload['data']   // ✅ ONLY actual data
                ]
            );
    }

    protected function searchZohoAccount($accessToken, $criteria)
    {
        return Http::withToken($accessToken)
            ->get("https://www.zohoapis.eu/crm/v2/Accounts/search", [
                'criteria' => $criteria
            ]);
    }





    public function syncD7SuppliersToZohoAccounts(int $supplierId, string $action = 'insert')
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $payload = $this->buildAccountModulePayload($supplierId);

        $responseData = null;
        $errorMessage = null;

        try {

            $supplier = D7LeadSupplier::findOrFail($supplierId);

            $website = $supplier->website;
            $phone   = preg_replace('/\D/', '', $supplier->phone);
            $email   = $supplier->email;

            $zohoRecordId = null;

            // Account Name Search
            if (!$zohoRecordId && !empty($supplier->name)) {

                $name = urlencode($supplier->name);

                $res = $this->searchZohoAccount($access_token, "(Account_Name:equals:$name)");

                $data = $res->json();
                $zohoRecordId = $data['data'][0]['id'] ?? null;
            }

            // Website Search
            if (!$zohoRecordId && !empty($website)) {

                $criteria = "(Website:equals:" . urlencode($website) . ")";
                $res = $this->searchZohoAccount($access_token, $criteria);

                $data = $res->json();
                $zohoRecordId = $data['data'][0]['id'] ?? null;
            }

            // Phone Search
            if (!$zohoRecordId && !empty($phone)) {

                $res = $this->searchZohoAccount($access_token, "(Phone:equals:$phone)");
                $data = $res->json();
                $zohoRecordId = $data['data'][0]['id'] ?? null;
            }

            if (!$zohoRecordId && !empty($phone)) {

                $res = $this->searchZohoAccount($access_token, "(Phone_2:equals:$phone)");
                $data = $res->json();
                $zohoRecordId = $data['data'][0]['id'] ?? null;
            }

            // Email Search
            if (!$zohoRecordId && !empty($email)) {

                $res = $this->searchZohoAccount($access_token, "(Company_Email:equals:$email)");
                $data = $res->json();
                $zohoRecordId = $data['data'][0]['id'] ?? null;
            }

            // ================= UPDATE =================

            if ($zohoRecordId) {

                $zohoRecord = Http::withToken($access_token)
                    ->get("https://www.zohoapis.eu/crm/v2/Accounts/{$zohoRecordId}");

                $zohoData = $zohoRecord->json()['data'][0] ?? null;

                if ($zohoData) {
                    $this->applyZohoMergeLogic($payload, $zohoData);
                    if (!empty($zohoData['Account_Name'])) {
                        unset($payload['data'][0]['Account_Name']);
                    }
                }

                if (!empty(array_filter($payload['data'][0]))) {

                    $response = $this->updateZohoAccountRecord(
                        $access_token,
                        $zohoRecordId,
                        $payload
                    );
                }
            } else {

                $response = $this->upsertToZohoAccountsModule($access_token, $payload);
            }

            if (isset($response)) {

                $responseData = $response->json();

                // DUPLICATE FALLBACK
                if (
                    isset($responseData['data'][0]['code']) &&
                    $responseData['data'][0]['code'] === 'DUPLICATE_DATA'
                ) {

                    $duplicateId = $responseData['data'][0]['details']['id'] ?? null;

                    if ($duplicateId) {

                        $zohoRecord = Http::withToken($access_token)
                            ->get("https://www.zohoapis.eu/crm/v2/Accounts/{$duplicateId}");

                        $zohoData = $zohoRecord->json()['data'][0] ?? null;

                        if ($zohoData) {
                            $this->applyZohoMergeLogic($payload, $zohoData);

                            if (!empty($zohoData['Account_Name'])) {
                                unset($payload['data'][0]['Account_Name']);
                            }
                        }

                        $response = $this->updateZohoAccountRecord(
                            $access_token,
                            $duplicateId,
                            $payload
                        );

                        $responseData = $response->json();

                        D7LeadSupplier::where('id', $supplierId)
                            ->update(['zoho_account_record_id' => $duplicateId]);
                    }
                }

                if (
                    isset($responseData['data'][0]['status']) &&
                    $responseData['data'][0]['status'] === 'success' &&
                    isset($responseData['data'][0]['details']['id'])
                ) {

                    $zohoRecordId = $responseData['data'][0]['details']['id'];

                    D7LeadSupplier::where('id', $supplierId)
                        ->update(['zoho_account_record_id' => $zohoRecordId]);
                }
            }
        } catch (\Throwable $e) {

            $errorMessage = $e->getMessage();
        }

        ZohoHelper::logZohoRequest(
            'syncD7SuppliersToZohoAccounts',
            'https://www.zohoapis.eu/crm/v2/Accounts',
            $payload ?? null,
            $responseData ?? null,
            $errorMessage ?? null,
            $supplierId,
            $supplierId,
            'd7_lead_suppliers'
        );

        return $responseData;
    }

    private function applyZohoMergeLogic(&$payload, $zohoData)
    {
        // EMAIL
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

        // PHONE
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

        // WEBSITE
        if (!empty($zohoData['Website'])) {
            unset($payload['data'][0]['Website']);
        }

        // SERVICE
        if (!empty($zohoData['Main_Service_Type'])) {
            unset($payload['data'][0]['Main_Service_Type']);
        }
    }
 


    protected function buildAccountModulePayload($userId)
    {
        $user = D7LeadSupplier::findOrFail($userId);

        $datetime  = new DateTime($user->created_at, new DateTimeZone('Europe/London'));
        $formatted = $datetime->format('Y-m-d\TH:i:sP');

        $str = fn($v) => isset($v) ? (string) $v : null;

        $data = [
            'User_Auto_Id'  => $user->zoho_record_id ?? $user->id,

            'Account_Name' => $user->name,
            'Company_Email' => $user->email,

            'Phone' => ltrim($str($user->phone), '+'),
            'Website'  => $str($user->website),
            'Main_Service_Type' => $str($user->lead_service),

            'Billing_Street'  => $str($user->address1),
            'Billing_City'  => $str($user->address2),
            'Billing_Code' => $str($user->zip),

            'Description' => $user->google_stars
                ? 'Google Reviews: ' . $user->google_stars .
                ($user->google_review_count ? ' (' . $user->google_review_count . ')' : '')
                : null,

            'Facebook'  => $str($user->facebook_url),
            'Instagram' => $str($user->instagram_url),
            'LinkedIn'  => $str($user->linkedin_url),
            'Twitter'   => $str($user->twitter_url),

            'Lead_Source' => 'D7 Supplier',
            'created_at'  => $formatted,
        ];

        // Remove null / empty fields
        $data = array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });

        return [
            'data' => [$data],
            'duplicate_check_fields' => ['User_Auto_Id']
        ];
    }
    


    protected function upsertToZohoAccountsModule($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Accounts/upsert', $payload);
    }


    protected function updateZohoAccountRecord(string $accessToken, string $zohoRecordId, array $payload)
    {
        return Http::withToken($accessToken)
            ->put(
                "https://www.zohoapis.eu/crm/v2/Accounts/{$zohoRecordId}",
                [
                    'data' => $payload['data']   // ✅ ONLY actual data
                ]
            );
    }

    public function deleteZohoAccountRecord(string $zohoRecordId)
    {
        $access_token = ZohoHelper::getAccessToken();

        $response = Http::withToken($access_token)
            ->delete("https://www.zohoapis.eu/crm/v2/Accounts/{$zohoRecordId}");

        if ($response->failed()) {
            Log::error('Zoho delete failed', [
                'record_id' => $zohoRecordId,
                'response' => $response->json(),
            ]);

            throw new \Exception('Failed to delete Zoho record');
        }
    }
}
