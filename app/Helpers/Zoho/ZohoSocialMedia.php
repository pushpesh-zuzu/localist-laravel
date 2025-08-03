<?php

namespace App\Helpers\Zoho;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Http;

class ZohoSocialMedia
{
    public function integrateSocialLinks($userId)
    {
        $accessToken = ZohoHelper::getAccessToken();

        if (!$accessToken) {
            return null;
        }

        $userDetail = UserDetail::where('user_id', $userId)->first();

        if (!$userDetail) {
            return null;
        }

        $payload = $this->buildSocialPayload($accessToken, $userDetail, $userId);
        $result=[];
        if ($payload) {
            $response = $this->upsertToZohoSocial($accessToken, $payload);
            $result=$response->json();
        }

        return $result;
    }

    protected function buildSocialPayload($access_token,$userDetail,$userId)
    {
        $lookUpId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);
        $userName=User::find($userId)->name;

        if($lookUpId){

            $socialFields = [
                'YouTube'     => $userDetail->company_youtube_link,
                'Facebook'    => $userDetail->fb_link,
                'Twitter'     => $userDetail->twitter_link,
                'TikTok'      => $userDetail->tiktok_link,
                'Instagram'   => $userDetail->insta_link,
                'LinkedIn'    => $userDetail->linkedin_link,
                'Extra_Links' => $userDetail->extra_links,
            ];

            $filteredSocialFields = array_filter($socialFields, function ($value) {
                return !empty($value);
            });

            if (empty($filteredSocialFields)) {
                return false;
            }

           return [
                'data' => [[
                    'Social_Media_Id'    => $userDetail->id,
                    'Lead_Social_Lookup' => $lookUpId,
                    'Name'               => (string)$userDetail->id,
                ] + $filteredSocialFields],
                'duplicate_check_fields' => ['Social_Media_Id']
            ];
        }
        else{
            return false;
        }
    }

    protected function upsertToZohoSocial($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Social_Media/upsert', $payload);
    }


    // protected function getZohoSocialId($accessToken, $userId)
    // {
    //     $response = Http::withToken($accessToken)
    //         ->get('https://www.zohoapis.eu/crm/v2/Social_Media/search', [
    //             'criteria' => "(Social_Media_Id:equals:{$userId})"
    //         ]);

    //     $data = $response->json();

    //     return $data['data'][0]['id'] ?? null;
    // }

    // protected function sendToZoho($accessToken, array $payload, $zohoId = null)
    // {
    //     $url = $zohoId
    //         ? "https://www.zohoapis.eu/crm/v2/Social_Media/{$zohoId}"
    //         : "https://www.zohoapis.eu/crm/v2/Social_Media";

    //     $method = $zohoId ? 'put' : 'post';

    //     return Http::withToken($accessToken)->$method($url, $payload);
    // }

}
