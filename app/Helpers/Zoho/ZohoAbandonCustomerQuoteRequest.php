<?php

namespace App\Helpers\Zoho;

use App\Models\Category;
use App\Helpers\CreditScorePredictor as CreditScore;
use App\Models\AbandonedUser;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoAbandonCustomerQuoteRequest
{
    public function integrateAbandonQuoteRequest($userId)
    {
        $accessToken = ZohoHelper::getAccessToken();

        if (!$accessToken) {
            return null;
        }

        $user = AbandonedUser::find($userId);

        if (!$user) {
            Log::warning("AbandonedUser not found for user ID: {$userId}");
            return null;
        }

        // Check if user has related questions and answers
        $questions = $user->questions ?? null;

        if (empty($questions)) {
            Log::info("Skipping Zoho Abandoned Quote Request creation — no questions found for user ID: {$userId}");
            return null;
        }

        $payload = $this->buildAbandonQuoteRequestPayload($accessToken, $userId);

        Log::info('Zoho API Purchase Payload', [
            'user_id' => $userId,
            'payload' => $payload,
        ]);
        if (!$payload) return null;

        $response = $this->upsertToZohoService($accessToken, $user, $payload);

        $responseData = $response->json();

        if (
            isset($responseData['data'][0]['status']) &&
            $responseData['data'][0]['status'] === 'success' &&
            isset($responseData['data'][0]['details']['id'])
        ) {
            $zohoRecordId = $responseData['data'][0]['details']['id'];

            AbandonedUser::where('id', $userId)->update([
                'zoho_abandoned_quote_request_id' => $zohoRecordId,
            ]);
        }


        $responseDataItem = $responseData['data'][0] ?? null;
        $errorMessage     = $responseData['data'][0]['message'] ?? null;

        $dbRecordId = $userId;
        $dbTable    = 'abandoned_users';

        try {
            ZohoHelper::logZohoRequest(
                'integrateAbandonQuoteRequest',
                'https://www.zohoapis.eu/crm/v2/Quote_Request/upsert',
                $payload,
                $responseDataItem,
                $errorMessage,
                $userId,
                $dbRecordId,
                $dbTable
            );
        } catch (\Exception $e) {
            Log::error('Failed to log Zoho Abandoned Quote Request', [
                'exception' => $e->getMessage(),
                'user_id' => $userId
            ]);
        }
        return $response->json();
    }

    protected function buildAbandonQuoteRequestPayload($accessToken, $userId)
    {

        $lookUpId = ZohoHelper::getZohoAbandonedQuoteCustomerId($accessToken, $userId);

        $user = AbandonedUser::find($userId);
        $userName = $user->name ?? 'NA';
        $category = Category::find($user->service_id);
        $service = $category->name ?? 'NA';
        $creditScoreModel = $category->credit_score_model ?? 0;

        if ($creditScoreModel === 'python') {
            $predict['Location'] = $user->city . ', ' . strtoupper($user->zipcode);
            $predict['Urgent'] = 0;
            $predict['High'] = 0;
            $predict['Verified'] = 0;
            $predict['Frequent'] = 0;

            $creditScore = CreditScore::getCreditScoreFromPython($user->service_id, $predict, $user->questions);
        } else {
            //laravel based credit score prediction
            $creditScore = CreditScore::getCreditScoreFromLaravel($user->service_id, $user->questions);
        }

        $arrQuesD = json_decode($user->questions, true);
        if (empty($arrQuesD)) {
            return null; // No questions found
        }

        $arrQues = [];
        foreach ($arrQuesD as $aq) {
            if (!empty($aq)) {
                $temp['ques'] = $aq['ques'] ?? '';
                $temp['ans'] = array_map('trim', explode(',', $aq['ans'] ?? ''));
                $arrQues[] = $temp;
            }
        }

        // Build formatted Q&A text block
        $questionBlocks = [];
        foreach ($arrQues as $qa) {
            if (empty($qa['ques']) || empty($qa['ans'])) continue;

            $formattedAnswer = implode(', ', $qa['ans']);
            $questionBlocks[] = "{$qa['ques']}\nAns: {$formattedAnswer}";
        }

        if (empty($questionBlocks)) {
            return null;
        }

        $formattedQA = implode("\n\n", $questionBlocks);

        return [
            'data' => [[
                'Quote_Request_Record_Id'      => (int) ($user->id . rand(10, 99)),
                'Quote_Customer_Lookup'        => $lookUpId,
                'City'                         => $user->city,
                'Name'                         => $user->name,
                'Credit_Score'                 => $creditScore,
                'Zipcode'                      => $user->zipcode,
                'Service'                      => $service,
                'Is_Urgent'                    =>  'No',
                'Is_High_Hiring'               => 'No',
                'Is_Frequent_User'             =>  'No',
                'Is_Phone_Verified'            => 'No',
                'Status'                       => 'new',
                'Sales_Value'                  =>  0.0,
                'Receive_Online'               =>  'No',
                'Closed'                       =>  'No',
                'Question_Answers'             => $formattedQA,
                'Description'                  => ''

            ]],
            'duplicate_check_fields' => ['Quote_Request_Record_Id']

        ];
    }

    protected function upsertToZohoService($accessToken, $user, array $payload)
    {

        if ($user->zoho_abandoned_quote_request_id) {

            $response = Http::withToken($accessToken)
                ->put("https://www.zohoapis.eu/crm/v2/Quote_Request/{$user->zoho_abandoned_quote_request_id}", [
                    'data' => $payload['data']
                ]);
        } else {
            $response = Http::withToken($accessToken)->post('https://www.zohoapis.eu/crm/v2/Quote_Request/upsert', $payload);
        }

        return $response;
    }



    public function deleteAbandonedQuoteRequest($zohoAbandonedQuoteId, $abUserId)
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            Log::error("Zoho Access Token missing while trying to delete Abandoned Quote Request for user ID: {$abUserId}");
            return null;
        }

        if (!$zohoAbandonedQuoteId) {
            Log::warning("Zoho delete skipped: No Zoho Abandoned Quote Request ID found for user ID: {$abUserId}");
            return null;
        }

        $response = Http::withToken($access_token)
            ->delete("https://www.zohoapis.eu/crm/v2/Quote_Request/{$zohoAbandonedQuoteId}");

        if ($response->successful()) {
            // Clear the Zoho ID in our DB
            $user = AbandonedUser::find($abUserId);
            if ($user) {
                $user->zoho_abandoned_quote_request_id = null;
                $user->save();
            }
        } else {
            Log::error("❌ Failed to delete Zoho Abandoned Quote Request.", [
                'user_id' => $abUserId,
                'zoho_record_id' => $zohoAbandonedQuoteId,
                'response' => $response->json(),
            ]);
        }

        return $response->json();
    }
}
