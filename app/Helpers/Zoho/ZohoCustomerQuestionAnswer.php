<?php

namespace App\Helpers\Zoho;

use App\Models\LeadPrefrence;
use App\Models\LeadRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoCustomerQuestionAnswer
{
    public function integrateServiceQa($userId,$requestId)
    {
        $access_token = ZohoHelper::getAccessToken();
        if (!$access_token) return null;

        //$serviceIds = LeadPrefrence::where('user_id', $userId)->pluck('service_id')->unique();

        $results = [];
        $totalCreditsUsed = 0;


            try {
                 Log::info('response question details', [
                        'user_id' => $userId,
                        'requestId' => $requestId,
                    ]);

                $payload = $this->buildQaPayloadByServiceId($access_token, $userId, $requestId);
                if (!$payload) {
                    $results[$requestId] = ['error' => 'Empty or invalid payload'];
                    return ;
                }

                $response = $this->upsertToZohoService($access_token, $payload);
                $responseData = $response->json();


                $results[$requestId] = [
                    'response' => $response->json(),
                    'credits_used' => $responseData
                ];
            } catch (\Throwable $e) {
                $results[$requestId] = ['error' => $e->getMessage()];
            }


        Log::info('Reduced credit Zoho Customer QA sync by service_id', [
            'user_id' => $userId,
            'total_credits_used' => $totalCreditsUsed,
            'results' => $results
        ]);

        Log::info('response question for user', [
                        'user_id' => $userId,
                        'response' => $results,
                    ]);

        return $results;

    }

    protected function buildQaPayloadByServiceId($access_token, $userId, $requestId)
    {
        $prefs = LeadRequest::with('category')
            ->where('customer_id', $userId)
            ->where('id', $requestId)
            ->get();

        if ($prefs->isEmpty()) return null;

        $questionBlocks = [];
        $category = null;

       foreach ($prefs as $pref) {
            $questions = json_decode($pref->arrayed_questions, true); // decode to array

            if (!is_array($questions)) continue;

            foreach ($questions as $qa) {
                $question = $qa['ques'] ?? '';
                $answers = $qa['ans'] ?? [];

                if (!$question || empty($answers)) continue;

                $formattedAnswer = implode(', ', $answers);
                $questionBlocks[] = "{$question}\nAns: {$formattedAnswer}";
            }

            $category = optional($pref->category); // assuming you still want category name
        }

        if (empty($questionBlocks)) return null;

        $formattedQA = implode("\n\n", $questionBlocks);
        $lookUpId = ZohoHelper::getZohoQuoteCustomerId($access_token, $userId);
        if (!$lookUpId) return null;

        return [
            'data' => [[
                'Question_Id' => $requestId,
                'Quote_Customer_Lookup' => $lookUpId,
                'Name' => $category->name ?? 'Service Q&A',
                'Unique_QA_Key' => "{$lookUpId}_{$requestId}",
                'QuestionAnswers' => $formattedQA,
            ]],
            'duplicate_check_fields' => ['Question_Id']
        ];
    }

    protected function upsertToZohoService($accessToken, array $payload)
    {
        $url = "https://www.zohoapis.eu/crm/v2/Customer_Question_Answer";

        $method =  'post';

        return  Http::withToken($accessToken)->$method($url, $payload);
    }

//    protected function getZohoBuyerQaId($accessToken, $serviceId)
//     {
//         $response = Http::withToken($accessToken)
//             ->get('https://www.zohoapis.eu/crm/v2/Question_Answers/search', [
//                 'criteria' => "(Question_Id:equals:{$serviceId})"
//             ]);

//         $data = $response->json();

//         return $data['data'][0]['id'] ?? null;
//     }

//     protected function sendUserQaToZoho($accessToken, array $payload, $zohoServiceId = null)
//     {
//         $url = $zohoServiceId
//             ? "https://www.zohoapis.eu/crm/v2/Question_Answers/{$zohoServiceId}"
//             : "https://www.zohoapis.eu/crm/v2/Question_Answers";

//         $method = $zohoServiceId ? 'put' : 'post';

//         return  Http::withToken($accessToken)->$method($url, $payload);

//     }






}
