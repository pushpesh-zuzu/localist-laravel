<?php
namespace App\Helpers\Zoho;

use App\Models\UserService;
use App\Models\UserServiceLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ZohoServiceLocations
{
    public function integrateServiceLocations($user)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $services = UserService::with('locations')
            ->where('user_id', $user->id)
            ->get();

        $responses = [];
        $counter = 0;
        $services->each(function ($service) use ($access_token, $user, &$responses, &$payloads, &$counter) {
            foreach ($service->locations as $location) {
                $counter ++;
                $locationId = $location->id;

                $zohoServiceId = $this->getZohoBuyerServiceId($access_token, $locationId);

                $payload = $this->buildServicePayload($user, $locationId, $zohoServiceId,$counter);

                $response = $this->sendUserServiceToZoho($access_token, $payload, $zohoServiceId);

                $responses[] = [
                'location_id' => $locationId,
                'zoho_id'     => $zohoServiceId,
                'status'      => $response->status(),
                'body'        => $response->json(),
                ];


            }
        });

       return $responses;

    }

    protected function buildServicePayload($user, $locationId, $zohoServiceId = null,$counter)
    {
        $location = UserServiceLocation::find($locationId);

        $serviceDetails = UserService::with([
            'category.serviceQuestions.leadPreferences' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            },
            'user'
        ])->find($location->user_service_id);

        $questions = [];
        $answers = [];

        foreach ($serviceDetails->category->serviceQuestions as $question) {
            $questions[] = $question->questions;
            $answers[] = optional($question->leadPreferences->first())->answers;
        }


        $payload = [
            'data' => [[
                'Service_Id'      => $location->id,
                'Service Name'    => $serviceDetails->category->name,
                'Name'            => $serviceDetails->category->name,
                'Lead_Buyer_Name1' => $serviceDetails->user->name,
                'Miles'           => $location->miles,
                'Postcode'        => $location->postcode,
                'Nation_Wide'     => $location->nation_wide == 1 ? 'Yes' : 'No',
                'City'            => $location->city,
                'Status'          => $serviceDetails->status == 1 ? 'Added' : 'Rejected',
                'questions'       => $questions,
                'Answers'         => $answers
            ]]
        ];


        if (!$zohoServiceId) {
            $payload['data'][0]['created_at'] = now()->format('c');
        }



        return $payload;
    }



    protected function getZohoBuyerServiceId($accessToken, $serviceId)
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/Services_Locations/search', [
                'criteria' => "(Service Id:equals:{$serviceId})"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }



    protected function sendUserServiceToZoho($accessToken, array $payload, $zohoServiceId = null)
    {
        $url = $zohoServiceId
            ? "https://www.zohoapis.eu/crm/v2/Services_Locations/{$zohoServiceId}"
            : "https://www.zohoapis.eu/crm/v2/Services_Locations";

        $method = $zohoServiceId ? 'put' : 'post';
        return  Http::withToken($accessToken)->$method($url, $payload);
    }


}
