<?php

namespace App\Helpers\Zoho;

use App\Models\LeadPrefrence;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoQuestionAnswer
{
    public function integrateServiceQa($user)
    {
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $userId = $user->id;
        $payload = $this->buildQaPayload($access_token, $userId);

        $responses = [];

        foreach ($payload['data'] as $dataBlock) {
            $zohoServiceId = $this->getZohoBuyerQaId($access_token, $dataBlock['Question_Id']);

            $response = $this->sendUserQaToZoho($access_token, [
                'data' => [$dataBlock]
            ], $zohoServiceId);

            $responses[] = [
                'status' => $response->status(),
                'body'   => $response->json(),
            ];
        }

        return $responses;
    }

    protected function buildQaPayload($access_token, $userId)
    {
        $leadPrefs = LeadPrefrence::with([
            'question.category'
        ])
        ->where('user_id', $userId)
        ->get();

        // Group preferences by category (service)
        $grouped = $leadPrefs->groupBy(fn($pref) => $pref->question->category->id ?? null);

        $payloadData = [];

        foreach ($grouped as $categoryId => $preferences) {
            if (!$categoryId) continue;

            $categoryName = optional($preferences->first()->question->category)->name;
            $formattedQA = '';
            $leadPreferenceId = null;
            $counter = 1;

            foreach ($preferences as $pref) {
                $questionText = $pref->question->questions ?? '';
                $answerText = $pref->answers ?? '';

                $formattedQA .= "Q{$counter}. {$questionText}\nAns: {$answerText}\n\n";

                $leadPreferenceId = $pref->id; // or first one if you prefer
                $counter++;
            }

            $lookUpId = ZohoLeadBuyers::getZohoLeadBuyerId($access_token, $userId);

            $payloadData[] = [
                'Question_Id'            => $leadPreferenceId,
                'Lead_Questions_Lookup'  => $lookUpId,
                'Name'                   => $categoryName,
                'QuestionAnswers'        => trim($formattedQA),
            ];
        }

        return ['data' => $payloadData];
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
