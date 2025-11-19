<?php

namespace App\Helpers\Zoho;

use App\Models\Category;
use App\Models\LeadRequest;
use App\Models\RecommendedLead;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoQuoteRequest
{
    public function integrateQuoteRequest($userId,$id)
    {
        $accessToken = ZohoHelper::getAccessToken();

        if (!$accessToken) {
            return null;
        }

        $leadRequests = LeadRequest::where('id', $id)
               ->first();


        // $recommendedLeadId = $this->getZohoPurchasedLeadsId($accessToken, $recommendedLeads->id);

        $payload = $this->buildQuoteRequestPayload($accessToken,$leadRequests,$userId);

        Log::info('Zoho API Purchase Payload', [
            'user_id' => $userId,
            'purchase_id' => $id,
            'payload' => $payload,
        ]);
        if (!$payload) return null;

        $response = $this->upsertToZohoService($accessToken,$leadRequests, $payload);

        $responseData = $response->json();

        if (
            isset($responseData['data'][0]['status']) &&
            $responseData['data'][0]['status'] === 'success' &&
            isset($responseData['data'][0]['details']['id'])
        ) {
            $zohoRecordId = $responseData['data'][0]['details']['id'];

            LeadRequest::where('id', $id)->update([
                'zoho_quote_request_id' => $zohoRecordId,
            ]);
        }

        Log::info('Zoho API Credit Used for Quote Request', [
            'user_id' => $userId,
            'lead_request_id' => $id,
            'response' => $response->json(),
        ]);
        return $response->json();


    }

    protected function buildQuoteRequestPayload($accessToken, $leadRequests,$userId)
    {

        $lookUpId =ZohoHelper::getZohoQuoteCustomerId($accessToken, $userId);
        $userName=User::find($userId)->name;
        $customerName=User::find($leadRequests->customer_id )->name;
        $service=Category::find($leadRequests->service_id)->name;
        $creditScore=$leadRequests->credit_score;

        $questions = json_decode($leadRequests->arrayed_questions, true); // decode to array

        foreach ($questions as $qa) {
            $question = $qa['ques'] ?? '';
            $answers = $qa['ans'] ?? [];

            if (!$question || empty($answers)) continue;

            $formattedAnswer = implode(', ', $answers);
            $questionBlocks[] = "{$question}\nAns: {$formattedAnswer}";
        }

        if (empty($questionBlocks)) return null;

        $formattedQA = implode("\n\n", $questionBlocks);


        return [
            'data' => [[
                'Quote_Request_Record_Id'      => $leadRequests->id,
                'Quote_Customer_Lookup'        => $lookUpId,
                'City'                         => $leadRequests->city,
                'Name'                         => $customerName,
                'Credit_Score'                 => $creditScore,
                'Zipcode'                      => $leadRequests->postcode,
                'Service'                      => $service,
                'Is_Urgent'                    => $leadRequests->is_urgent  == 1 ? 'Yes' : 'No',
                'Is_High_Hiring'               => $leadRequests->is_high_hiring == 1 ? 'Yes' : 'No',
                'Is_Frequent_User'             => $leadRequests->is_frequent_user == 1 ? 'Yes' : 'No',
                'Is_Phone_Verified'            => $leadRequests->is_phone_verified == 1 ? 'Yes' : 'No',
                'Status'                       => $leadRequests->status,
                'Sales_Value'                  =>  0.0,
                'Receive_Online'               => $leadRequests->recevive_online == 1 ? 'Yes' : 'No',
                'Closed'                       => $leadRequests->closed_status == 1 ? 'Yes' : 'No',
                'Question_Answers'             => $formattedQA,
                'Description'                  => $leadRequests->details

            ]],
            'duplicate_check_fields' => ['Quote_Request_Record_Id']

        ];
    }

    protected function upsertToZohoService($accessToken,$leadRequests, array $payload)
    {

        if ($leadRequests->zoho_quote_request_id) {

            $response = Http::withToken($accessToken)
                ->put("https://www.zohoapis.eu/crm/v2/Quote_Request/{$leadRequests->zoho_quote_request_id}", [
                    'data' => $payload['data']
                ]);
        } else {
            $response = Http::withToken($accessToken)->post('https://www.zohoapis.eu/crm/v2/Quote_Request/upsert', $payload);
        }

        return $response;
    }





    public function updateZohoQuoteStatus($leadRequestId)
{
    $accessToken = ZohoHelper::getAccessToken();
    if (!$accessToken) return null;

    $leadRequest = LeadRequest::find($leadRequestId);
    if (!$leadRequest) return null;

    
    if (empty($leadRequest->zoho_quote_request_id)) {
        Log::warning("Zoho update skipped — no zoho_quote_request_id", [
            'lead_request_id' => $leadRequestId
        ]);
        return null;
    }

    $Hired_User = '';

    if (!empty($leadRequest->hired_to)) {
        $user = User::find($leadRequest->hired_to);
        $Hired_User = $user->name ?? '';
    }  
    
    // Build payload for updating only Status
   
    $payload = [
            'data' => [[
                'Quote_Request_Record_Id'      => $leadRequest->id,                
                'Status'                       => $leadRequest->status, 
                'Hired_User'                   => $Hired_User, 
                'Hired_To'                     => $leadRequest->hired_to ?? '', 
            ]],
            'duplicate_check_fields' => ['Quote_Request_Record_Id']

        ];

    // Update using your existing Zoho update function
    $response = $this->upsertToZohoService($accessToken, $leadRequest, $payload);

    Log::info('Zoho Quote Status Updated', [
        'lead_request_id' => $leadRequestId,
        'new_status'      => $leadRequest->status,
        'response'        => $response->json()
    ]);

    return $response->json();
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
