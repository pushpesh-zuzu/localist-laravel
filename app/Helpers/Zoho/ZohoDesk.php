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

        $orgId =  CustomHelper::setting_value('zoho_desk_org_id') ?? '20105748683';

       if ($data['user_type'] == 1) {
            $departmentId = CustomHelper::setting_value('zoho_desk_buyer_dept_id') ?? '197781000001100111';
        } else {
            $departmentId = CustomHelper::setting_value('zoho_desk_seller_dept_id') ?? '197781000001052044';
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

        // ✅ Debugging starts here
        if (!$response->successful()) {
            Log::error('Zoho Desk API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'error' => true,
                'status' => $response->status(),
                'body' => $response->body(),
            ];
        }

        $responseData = $response->json();

        \DB::table('zoho_logs')->insert([
            'url' => $url,
            'function_name' => 'createTicket',
            'ipaddress' => request()->ip(),
            'created_at' => now(),
        ]);

        Log::info('Zoho Desk Ticket Created', [
            'response' => $responseData
        ]);

        return $responseData;
    }

}
