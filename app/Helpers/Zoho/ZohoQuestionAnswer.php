<?php

namespace App\Helpers\Zoho;

use App\Models\LeadPrefrence;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoQuestionAnswer
{
    public function integrateServiceQa($userId,$serviceIds)
    {
        $access_token = ZohoHelper::getAccessToken();
        if (!$access_token) return null;

        //$serviceIds = LeadPrefrence::where('user_id', $userId)->pluck('service_id')->unique();

        $results = [];
        $totalCreditsUsed = 0;

        foreach ($serviceIds as $serviceId) {
            try {
                 Log::info('response question details', [
                        'user_id' => $userId,
                        'serviceId' => $serviceId,
                    ]);

                $payload = $this->buildQaPayloadByServiceId($access_token, $userId, $serviceId);
                if (!$payload) {
                    $results[$serviceId] = ['error' => 'Empty or invalid payload'];
                    continue;
                }

                $response = $this->upsertToZohoService($access_token, $payload);
                $responseData = $response->json();

                if (
                    isset($responseData['data'][0]['status']) &&
                    $responseData['data'][0]['status'] === 'success' &&
                    isset($responseData['data'][0]['details']['id'])
                ) {
                    $zohoRecordId = $responseData['data'][0]['details']['id'];

                    LeadPrefrence::where('user_id', $userId)->where('service_id', $serviceId)->update([
                        'zoho_question_id' => $zohoRecordId,
                    ]);
                }

                $usedCredits = (int) $response->header('X-API-COST');
                $totalCreditsUsed += $usedCredits;

                $results[$serviceId] = [
                    'response' => $response->json(),
                    'credits_used' => $usedCredits
                ];
            } catch (\Throwable $e) {
                $results[$serviceId] = ['error' => $e->getMessage()];
            }
        }

        Log::info('Reduced credit Zoho QA sync by service_id', [
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

    protected function buildQaPayloadByServiceId($access_token, $userId, $serviceId)
    {
        $prefs = LeadPrefrence::with(['question.categories'])
            ->where('user_id', $userId)
            ->where('service_id', $serviceId)
            ->get();

        if ($prefs->isEmpty()) return null;

        $questionBlocks = [];
        $category = null;

        foreach ($prefs as $pref) {
            $questionText = $pref->question->questions ?? '';
            $answerText = $pref->answers ?? '';

            if (!$questionText || !$answerText) continue;

            $questionBlocks[] = "{$questionText}\nAns: {$answerText}";
            $category = optional($pref->question->categories);
        }

        if (empty($questionBlocks)) return null;

        $formattedQA = implode("\n\n", $questionBlocks);
        $lookUpId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);

        if (!$lookUpId) return null;

        return [
            'data' => [[
                'Question_Id' => $serviceId,
                'Lead_Questions_Lookup' => $lookUpId,
                'Unique_QA_Key' => "{$lookUpId}_{$serviceId}",
                'Name' => $category->name ?? 'Service Q&A',
                'QuestionAnswers' => $formattedQA,
            ]],
            'duplicate_check_fields' => ['Unique_QA_Key']
        ];
    }


    public function integrateServiceQaSingle($userId,$serviceId)
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        Log::info('response question siingle details', [
                        'user_id' => $userId,
                        'serviceId' => $serviceId,
                    ]);

        $payload = $this->buildQaPayloadByServiceId($access_token,$userId,$serviceId);
        if (!$payload) return null;

        //$zohoServiceId = $this->getZohoBuyerQaId($access_token, $questionId);

        //$response = $this->sendUserQaToZoho($access_token, $payload, $zohoServiceId);

         $response = $this->upsertToZohoService($access_token, $payload);

        $responseData = $response->json();

        if (
            isset($responseData['data'][0]['status']) &&
            $responseData['data'][0]['status'] === 'success' &&
            isset($responseData['data'][0]['details']['id'])
        ) {
            $zohoRecordId = $responseData['data'][0]['details']['id'];

            LeadPrefrence::where('user_id', $userId)->where('service_id', $serviceId)->update([
                'zoho_question_id' => $zohoRecordId,
            ]);
        }


        Log::info('response question for user', [
            'user_id' => $userId,
            'response' => $responseData,
        ]);

        return $responseData;


    }

    protected function buildQaPayload($access_token,$questionId,$userId)
    {

        $pref = LeadPrefrence::with(['question.categories'])
        ->where('user_id', $userId)
        ->where('id', $questionId)
        ->first();

        if (!$pref) return null;

           $expectedArray = json_decode($pref->question->answer ?? '', true);

            $expectedAnswers = [];
            if (is_array($expectedArray)) {
                foreach ($expectedArray as $item) {
                    if (isset($item['option'])) {
                        $expectedAnswers[] = trim($item['option']);
                    }
                }
            }

            $userAnswers = array_map('trim', explode(',', $pref->answers ?? ''));

            $commonAnswers = array_intersect($expectedAnswers, $userAnswers);


            $questionText = $pref->question->questions ?? '';
           // $answerText = implode(', ', $commonAnswers);
            $answerText = $pref->answers;
            $category = optional($pref->question->categories);

            $formattedQA = "{$questionText}\nAns: {$answerText}";
            $lookUpId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);

            if($lookUpId){
                $payload = [
                    'data' => [[
                        'Question_Id'           => $pref->id,
                        'Lead_Questions_Lookup' => ZohoHelper::getZohoLeadBuyerId($access_token, $userId),
                        'Name'                  => $category->name,
                        'QuestionAnswers'       => $formattedQA,
                    ]],
                    'duplicate_check_fields' => ['Question_Id']
                ];
            }
            else{
                return false;
            }

            return $payload;
    }

    protected function upsertToZohoService($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Question_Answers/upsert', $payload);
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

    public function deleteServiceQa($zohoServiceIds,$userId,$serviceId)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }
        foreach ($zohoServiceIds as $zohoServiceId) {

                $response = Http::withToken($access_token)
                    ->delete("https://www.zohoapis.eu/crm/v2/Question_Answers/{$zohoServiceId}");

                if ($response->successful()) {
                    Log::info("Zoho QA deleted for serviceId {$zohoServiceId}");
                } else {
                    Log::error("Zoho QA delete failed", [
                        'serviceId' => $zohoServiceId,
                        'response' => $response->json(),
                    ]);
                }
        }

        $payload = $this->buildQaPayloadByServiceId($access_token, $userId, $serviceId);

        if (!$payload) {
            Log::warning("Payload is empty or invalid for user_id {$userId}, service_id {$serviceId}");
            return null;
        }

        $insertResponse = $this->upsertToZohoService($access_token, $payload);

        if ($insertResponse->successful()) {
            $newZohoId = $insertResponse->json()['data'][0]['details']['id'] ?? null;

            if ($newZohoId) {
                LeadPrefrence::where('user_id', $userId)
                    ->where('service_id', $serviceId)
                    ->update(['zoho_question_id' => $newZohoId]);
            }

            Log::info("Zoho QA upserted successfully for service_id {$serviceId}");
        } else {
            Log::error("Zoho QA upsert failed", [
                'service_id' => $serviceId,
                'response' => $insertResponse->json(),
            ]);
        }


        return $insertResponse->json();
    }

    public function deleteBuyerQa($serviceId)
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $zohoServiceId = $this->getZohoBuyerQaId($access_token, $serviceId);
        if (!$zohoServiceId) {
            Log::warning("Zoho Service delete failed: No Zoho ID found for service_id {$serviceId}");
            return null;
        }
        $response = Http::withToken($access_token)
            ->delete("https://www.zohoapis.eu/crm/v2/Question_Answers/{$zohoServiceId}");

        if ($response->successful()) {
            Log::info("Zoho Service deleted for service_id {$serviceId}");
        } else {
            Log::error("Zoho Service delete failed", [
                'service_id' => $serviceId,
                'response' => $response->json(),
            ]);
        }

        return $response->json();
    }


}
