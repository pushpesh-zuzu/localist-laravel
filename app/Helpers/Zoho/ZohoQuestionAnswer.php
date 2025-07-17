<?php

namespace App\Helpers\Zoho;

use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ZohoQuestionAnswer
{
    public function integrateServiceQa($user)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }


        $services = UserService::with('category')
            ->where('user_id', $user->id)
            ->get();
        $responses = [];


        foreach ($services as $service) {
            $serviceId = $service->category->id;



            $zohoServiceId = $this->getZohoBuyerQaId($access_token, $user->id, $serviceId);


            $payload = $this->buildQaPayload($access_token, $user->id, $zohoServiceId, $serviceId);

            if (!empty($payload['data'])) {
                $response = $this->sendUserQaToZoho($access_token, $payload, $zohoServiceId);
                $responses[] = [

                    'status'      => $response->status(),
                    'body'        => $response->json(),
                ];
            }
        }





        return $responses;
    }

    protected function buildQaPayload($access_token, $userId, $zohoServiceId = null, $serviceId)
    {
        $userServices = UserService::with([
            'category.serviceQuestions.leadPreferences' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }
        ])
            ->where('user_id', $userId)
            ->when($serviceId, fn($q) => $q->where('service_id', $serviceId))
            ->get();

        $payloadData = [];

        foreach ($userServices as $service) {
            $questions = [];
            $answers = [];
            $formattedQA = '';
            $leadPreferenceId = null;
            $counter = 1;

            foreach ($service->category->serviceQuestions as $question) {
                $leadPref = $question->leadPreferences->first();

                if ($leadPref) {
                    $leadPreferenceId = $leadPref->id;
                }

                $questionText = $question->questions;
                $answerText = optional($leadPref)->answers;

                $questions[] = $questionText;
                $answers[] = $answerText;


                $formattedQA .= "Q{$counter}. {$questionText}\nAns: {$answerText}\n\n";
                $counter++;
            }

            $lookUpId = ZohoLeadBuyers::getZohoLeadBuyerId($access_token, $userId);

            if ($leadPreferenceId !== null) {
                $payloadData[] = [
                    'Question_Id'            => $leadPreferenceId,
                    'Lead_Questions_Lookup'  => $lookUpId,
                    'Name'                   => $service->category->name,
                    'QuestionAnswers'       => trim($formattedQA),
                ];
            }
        }


        return [
            'data' => $payloadData
        ];
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
