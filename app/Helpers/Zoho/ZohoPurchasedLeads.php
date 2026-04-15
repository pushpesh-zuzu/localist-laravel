<?php

namespace App\Helpers\Zoho;

use App\Helpers\CustomHelper;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Models\RecommendedLead;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoPurchasedLeads
{
    public function integratePurchaseLeads($userId, $id)
    {
        $accessToken = ZohoHelper::getAccessToken();

        if (!$accessToken) {
            return null;
        }

        $recommendedLeads = RecommendedLead::where('id', $id)
            ->first();


        // $recommendedLeadId = $this->getZohoPurchasedLeadsId($accessToken, $recommendedLeads->id);

        $payload = $this->buildPurchasedLeadPayload($accessToken, $recommendedLeads, $userId);

        Log::info('Zoho API Purchase Payload', [
            'user_id' => $userId,
            'purchase_id' => $id,
            'payload' => $payload,
        ]);
        if (!$payload) return null;

        $response = $this->upsertToZohoService($accessToken, $recommendedLeads, $payload);

        $responseData = $response->json();

        if (
            isset($responseData['data'][0]['status']) &&
            $responseData['data'][0]['status'] === 'success' &&
            isset($responseData['data'][0]['details']['id'])
        ) {
            $zohoRecordId = $responseData['data'][0]['details']['id'];

            RecommendedLead::where('id', $id)->update([
                'zoho_purchase_id' => $zohoRecordId,
            ]);
        }


        // ===== Safe Zoho Logging =====
        $responseDataItem = $responseData['data'][0] ?? null;
        $errorMessage = $responseData['data'][0]['message'] ?? null;
        $dbRecordId = $id;
        $dbTable = 'recommended_leads';

        try {
            ZohoHelper::logZohoRequest(
                'integratePurchaseLeads',
                'https://www.zohoapis.eu/crm/v2/Lead_Purchased/upsert',
                $payload,             // payload sent to Zoho
                $responseDataItem,    // response received from Zoho
                $errorMessage,        // error message if any
                $userId ?? null,      // main user ID
                $dbRecordId,          // database record ID
                $dbTable,             // database table name
            );
        } catch (\Exception $e) {
            Log::error('Failed to log Zoho Purchased Leads', [
                'exception' => $e->getMessage(),
                'user_id' => $userId,
                'purchase_id' => $id
            ]);
        }



        return $response->json();
    }

    protected function buildPurchasedLeadPayload($accessToken, $recommendedLeads, $userId)
    {

        $lookUpId = ZohoHelper::getZohoLeadBuyerId($accessToken, $userId);
        $userName = User::find($userId)->name;
        $LeadBuyerPhone = User::find($recommendedLeads->seller_id)->phone;
        $businessProfileName = User::find($recommendedLeads->seller_id)->business_profile_name;
        $LeadBuyerName = User::find($recommendedLeads->seller_id)->name;
        $userPhone = User::find($recommendedLeads->buyer_id)->phone;
        $userEmail = User::find($recommendedLeads->buyer_id)->email;
        $customerName = User::find($recommendedLeads->buyer_id)->name;
        $service = Category::find($recommendedLeads->service_id)->name;
        $creditScore = LeadRequest::find($recommendedLeads->lead_id)->credit_score;
        $postcode  = LeadRequest::find($recommendedLeads->lead_id)->postcode;
        $arrayedQuestions  = LeadRequest::find($recommendedLeads->lead_id)->arrayed_questions;
        $datetime = new DateTime($recommendedLeads->created_at, new DateTimeZone('Europe/London'));
        $formatted = $datetime->format('Y-m-d\TH:i:sP');

        $questions = json_decode($arrayedQuestions ?? '[]', true);

        if (!is_array($questions)) {
            $questions = [];
        }

        $questionBlocks = [];
        $index = 1;

        foreach ($questions as $qa) {
            $question = trim($qa['ques'] ?? '');
            $answers  = is_array($qa['ans'] ?? null) ? $qa['ans'] : [];

            if (!$question || empty($answers)) continue;

            $formattedAnswer = implode(', ', $answers);

            $questionBlocks[] = "Q{$index}. {$question}\nAns:  {$formattedAnswer}";
            $index++;
        }

        $formattedQA = !empty($questionBlocks)
            ? implode("\n\n", $questionBlocks)
            : null;


        $profileLink = rtrim(CustomHelper::setting_value('postlogin_react_base_url'), '/')
            . '/view-profile/'
            . strtolower(preg_replace('/\s+/', '-', trim($LeadBuyerName)))
            . '/'
            . $recommendedLeads->seller_id;

        $leadPhone = self::formatPhone($LeadBuyerPhone);
        $userPhoneNumber = self::formatPhone($userPhone);

        return [
            'data' => [[
                'Lead_Purchased_Id'     => $recommendedLeads->id,
                'Lead_Purchase_Lookup' => $lookUpId,
                'Name'                 => $service,
                'Customer_Name'        => $customerName,
                'Credit'               => $creditScore,
                'Date'                 => $formatted,
                'Purchase_Type'        => $recommendedLeads->purchase_type,
                'Status'               => $recommendedLeads->status,
                'Lead_Distance'         => (string) ($recommendedLeads->distance ?? ''),
                'LeadId'                => (string) ($recommendedLeads->lead_id ?? ''),
                'ServiceId'             => (string) ($recommendedLeads->service_id ?? ''),
                'Unit_Type'             => (string) ($recommendedLeads->unit_type ?? ''),
                'BuyerId'               => (string) ($recommendedLeads->buyer_id ?? ''),
                'Disclose_Information'  => (string) ($recommendedLeads->disclose_information ?? ''),
                'Final_Price'           => (string) ($recommendedLeads->final_price ?? ''),
                'Lead_Post_Code'           => (string) ($postcode  ?? ''),
                'Customer_Email'           => (string) ($userEmail ?? ''),
                'Customer_Phone' => str_starts_with((string) ($userPhone ?? ''), '+44')
                    ? '0' . substr((string) $userPhone, 3)
                    : (string) ($userPhone ?? ''),
                'Phone'         =>  (string) ($leadPhone ?? ''),
                'Lead_Buyer_Name'          =>  (string) ($LeadBuyerName ?? ''),
                'Question_Answers'             => $formattedQA,
                'Profile_Link'             => $profileLink,
                'Customer_WhatsApp_Number'  => (string) ($userPhoneNumber ?? ''),
                'Business_Profile_Name'  => $businessProfileName,


            ]],
            'duplicate_check_fields' => ['Lead_Purchase_Id']

        ];
    }


    public function formatPhone($phone)
    {
        $phone = $phone ?? '';

        // Remove only leading zeros (keep +)
        $phone = ltrim($phone, '0');

        // If starts with +44 → OK
        if (strpos($phone, '+44') === 0) {
            return $phone;
        }

        // If starts with 44 → add +
        if (strpos($phone, '44') === 0) {
            return '+' . $phone;
        }

        // Otherwise → add +44
        return '+44' . $phone;
    }

    protected function upsertToZohoService($accessToken, $recommendedLeads, array $payload)
    {

        if ($recommendedLeads->zoho_purchase_id) {

            $response = Http::withToken($accessToken)
                ->patch("https://www.zohoapis.eu/crm/v2/Lead_Purchased/{$recommendedLeads->zoho_purchase_id}", [
                    'data' => $payload['data']
                ]);
        } else {

            $response = Http::withToken($accessToken)->post('https://www.zohoapis.eu/crm/v2/Lead_Purchased/upsert', $payload);
        }

        return $response;
    }


    // protected function getZohoPurchasedLeadsId($accessToken, $recommendedLeadId)
    // {
    //     $response = Http::withToken($accessToken)
    //         ->get('https://www.zohoapis.eu/crm/v2/Leads_Purchased/search', [
    //             'criteria' => "(Lead_Purchase_Id:equals:$recommendedLeadId)"
    //         ]);

    //     $data = $response->json();

    //     return $data['data'][0]['id'] ?? null;
    // }

    // protected function sendLeadPurchasedToZoho($accessToken, array $payload, $recommendedLeadId = null)
    // {
    //     $url = $recommendedLeadId
    //         ? "https://www.zohoapis.eu/crm/v2/Leads_Purchased/{$recommendedLeadId}"
    //         : "https://www.zohoapis.eu/crm/v2/Leads_Purchased";

    //     $method = $recommendedLeadId ? 'put' : 'post';

    //     return Http::withToken($accessToken)->$method($url, $payload);
    // }
}
