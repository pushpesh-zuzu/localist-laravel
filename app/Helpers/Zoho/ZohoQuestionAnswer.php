<?php

namespace App\Helpers\Zoho;

use App\Models\LeadPrefrence;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoQuestionAnswer
{
    public function integrateServiceQa($userId,$questionId)
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $payload = $this->buildQaPayload($access_token,$questionId, $userId);

        if (!$payload) return null;

        $zohoServiceId = $this->getZohoBuyerQaId($access_token, $questionId);
        $response = $this->sendUserQaToZoho($access_token, $payload, $zohoServiceId);

        return $response->json();
    }

    protected function buildQaPayload($access_token,$questionId,$userId)
    {
        $pref = LeadPrefrence::with(['question.categories'])
        ->where('user_id', $userId)
        ->where('id', $questionId)
        ->first();

        if (!$pref) return null;

            $questionText = $pref->question->questions ?? '';
            $answerText = $pref->answers ?? '';
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
                'criteria' => "(Service Id:equals:{$serviceId})"
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

        return Http::withToken($accessToken)->$method($url, $payload);
    }
}
