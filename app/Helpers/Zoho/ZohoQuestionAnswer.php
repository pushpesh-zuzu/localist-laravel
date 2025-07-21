<?php

namespace App\Helpers\Zoho;

use App\Models\LeadPrefrence;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoQuestionAnswer
{
    public function integrateServiceQa($userId,$questionIds)
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        // $payload = $this->buildQaPayload($access_token,$questionId, $userId);
        // if (!$payload) return null;

        // $zohoServiceId = $this->getZohoBuyerQaId($access_token, $questionId);

        // $response = $this->sendUserQaToZoho($access_token, $payload, $zohoServiceId);
        // return $response->json();

        $results = [];

        foreach ($questionIds as $questionId) {
            try {
                $payload = $this->buildQaPayload($access_token, $questionId, $userId);
                if (!$payload) {
                    $results[$questionId] = ['error' => 'Empty payload'];
                    continue;
                }

                $zohoServiceId = $this->getZohoBuyerQaId($access_token, $questionId);
                $response = $this->sendUserQaToZoho($access_token, $payload, $zohoServiceId);

                $results[$questionId] = $response->json();
            } catch (\Throwable $e) {
                $results[$questionId] = ['error' => $e->getMessage()];
            }
        }

        return $results;

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

            $payload = [
                'data' => [[
                    'Question_Id'           => $pref->id,
                    'Lead_Questions_Lookup' => ZohoHelper::getZohoLeadBuyerId($access_token, $userId),
                    'Name'                  => $category->name,
                    'QuestionAnswers'       => $formattedQA,
                ]]
            ];

            return $payload;
    }

   protected function getZohoBuyerQaId($accessToken, $serviceId)
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/Question_Answers/search', [
                'criteria' => "(Question_Id:equals:{$serviceId})"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }

    protected function sendUserQaToZoho($accessToken, array $payload, $zohoServiceId = null)
    {
        $url = $zohoServiceId
            ? "https://www.zohoapis.eu/crm/v2/Question_Answers/{$zohoServiceId}"
            : "https://www.zohoapis.eu/crm/v2/Question_Answers";

        $method = $zohoServiceId ? 'put' : 'post';

        return  Http::withToken($accessToken)->$method($url, $payload);

    }

    public function deleteServiceQa($questionId)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }
        $zohoServiceId = $this->getZohoBuyerQaId($access_token, $questionId);

        if (!$zohoServiceId) {
            Log::warning("Zoho QA delete failed: No Zoho ID found for question_id {$questionId}");
            return null;
        }

        $response = Http::withToken($access_token)
            ->delete("https://www.zohoapis.eu/crm/v2/Question_Answers/{$zohoServiceId}");

        if ($response->successful()) {
            Log::info("Zoho QA deleted for question_id {$questionId}");
        } else {
            Log::error("Zoho QA delete failed", [
                'question_id' => $questionId,
                'response' => $response->json(),
            ]);
        }

        return $response->json();
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
       dd($zohoServiceId);
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
