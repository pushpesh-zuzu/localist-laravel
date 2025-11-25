<?php

namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoDeskService;

class ZohoDesk
{



    public function createTicket($data)
    {
        $accessToken = (new ZohoDeskService())->getAccessToken();

        if (!$accessToken) {
            Log::error('Zoho Desk Access Token not found');
            return ['error' => 'Access token not found'];
        }

        $orgId =  CustomHelper::setting_value('zoho_desk_org_id');

        if ($data['user_type'] == 1) {
            $departmentId = CustomHelper::setting_value('zoho_desk_buyer_dept_id');
        } else {
            $departmentId = CustomHelper::setting_value('zoho_desk_seller_dept_id');
        }


        $payload = [
            'subject' => $data['subject'] ?? 'New Ticket',
            'departmentId' => $departmentId,
            'contact' => [
                'lastName' => $data['full_name'] ?? 'Unknown',
                'email' => $data['email'] ?? 'noemail@domain.com'
            ],
            'description' => $data['message'] ?? 'No description provided.',
            'priority' => $data['priority'] ?? 'High',
            'status' => 'Open',
            'channel' => 'Web'
        ];

        $url = 'https://desk.zoho.eu/api/v1/tickets';

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
            'orgId' => $orgId,
        ])->post($url, $payload);

        try {
            $responseData = $response->json();
            $responseDataItem = $responseData['data'][0] ?? null;
            $errorMessage = $responseDataItem['message'] ?? null;
            $dbRecordId = $data['contactId'] ?? null;  // agar koi local record ID hai to use karein
            $dbTable = 'contact_us';      // ya jis table se related hai wo
            $userId = null;
            ZohoHelper::logZohoRequest(
                'createTicket',        // function name
                $url,                  // API URL
                $payload,              // payload sent to Zoho
                $responseDataItem,     // response received from Zoho
                $errorMessage,         // error message if any
                $userId, // main user ID
                $dbRecordId,           // database record ID
                $dbTable               // database table name
            );
        } catch (\Exception $e) {
            Log::error('Failed to log Zoho Desk Request', [
                'exception' => $e->getMessage(),
                'user_id' => $data['user_id'] ?? null,
                'record_id' => $data['id'] ?? null
            ]);
        }


        // ✅ Debugging starts here
        if (!$response->successful()) {

            return [
                'error' => true,
                'status' => $response->status(),
                'body' => $response->body(),
            ];
        }

        $responseData = $response->json();

        return $responseData;
    }
}
