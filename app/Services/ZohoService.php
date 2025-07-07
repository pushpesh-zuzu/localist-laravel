<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Helpers\ZohoHelper;

class ZohoService
{
    public function integrateUser($type,$user=null,$lead=null)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }


        if($type =='user' && $user){
            $searchResponse = Http::withToken($access_token)
            ->get('https://www.zohoapis.in/crm/v2/Quote_Customers/search', [
                'criteria' => "(User_auto_Id:equals:{$user->id})"
            ]);
        }
        elseif($type =='lead' && $lead){
             $searchResponse = Http::withToken($access_token)
            ->get('https://www.zohoapis.in/crm/v2/Leads/search', [
                'criteria' => "(Lead_auto_Id:equals:{$lead->id})"
            ]);
        }


        $searchData = $searchResponse->json();

        $zohoId = $searchData['data'][0]['id'] ?? null;

        if($type =='user' && $user){
            $payload = [
                'data' => [[
                    'User_auto_Id' => $user->id,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Mobile' => $user->phone,
                    'password' => $user->password,
                    'zipcode' => $user->zipcode,
                    'city' => $user->city,
                    'status' => ($user->status == 1) ? 'Added' : 'Rejected',
                    'otp' => $user->otp,
                    'active_status' => $user->active_status,
                    'form_status' => $user->form_status,
                    'user_type' => $user->user_type,
                    'updated_at' => now()->format('c'),
                ]]
            ];
        }
        elseif($type =='lead' && $lead){

            $payload = [
                'data' => [[
                    'Lead_auto_Id' => $lead->id,
                    'CustomerId' => $lead->customer_id,
                    'ServiceId' => $lead->service_id,
                    'City Name' => $lead->city,
                    'postcode' => $lead->postcode,
                    'questions' => $lead->questions,
                    'arrayed_questions' => $lead->arrayed_questions,
                    'Mobile Number' => $lead->phone,
                    'credit_score' => $lead->credit_score,
                    'recevive_online' => $lead->recevive_online,
                    'is_urgent' => $lead->is_urgent,
                    'is_high_hiring' => $lead->is_high_hiring,
                    'is_phone_verified' => $lead->is_phone_verified,
                    'is_frequent_user' => $lead->is_frequent_user,
                    'Last_Name' => "Nil",
                    'updated_at' => now()->format('c')
                ]]
            ];
        }

        if (!$zohoId) {
            $payload['data'][0]['created_at'] = now()->format('c');
        }
        if($type =='user' && $user){
            $url = $zohoId
                ? "https://www.zohoapis.in/crm/v2/Quote_Customers/{$zohoId}"
                : "https://www.zohoapis.in/crm/v2/Quote_Customers";
        }
        elseif($type =='lead' && $lead){
            $url = $zohoId
                ? "https://www.zohoapis.in/crm/v2/Leads/{$zohoId}"
                : "https://www.zohoapis.in/crm/v2/Leads";
        }

        $method = $zohoId ? 'put' : 'post';

        $response = Http::withToken($access_token)->$method($url, $payload);

        return $response->json();
    }
}
