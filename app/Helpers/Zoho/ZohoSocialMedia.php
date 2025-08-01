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

        $zohoId = $this->getZohoSocialId($accessToken, $userDetail->id);
        $payload = $this->buildSocialPayload($accessToken, $userDetail,$userId);
        $result = [];
        if($payload){
            $response = $this->sendToZoho($accessToken, $payload, $zohoId);
            $result = $response->json();
        }

        return $result;
    }

    protected function buildSocialPayload($access_token,$userDetail,$userId)
    {
        $lookUpId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);
        $userName=User::find($userId)->name;

        if($lookUpId){
            return [
                'data' => [[
                    'Social_Media_Id'          => $userDetail->id,
                    'Lead_Social_Lookup'       => $lookUpId,
                    'Name'                     => ''.$userDetail->id,
                    //'Lead_Buyer_Name'          => $userName,

                    //'User Id'       => $userDetail->user_id,
                    'YouTube'                 => $userDetail->company_youtube_link,
                    'Facebook'                => $userDetail->fb_link,
                    'Twitter'                 => $userDetail->twitter_link,
                    'TikTok'                  => $userDetail->tiktok_link,
                    'Instagram'               => $userDetail->insta_link,
                    'LinkedIn'                => $userDetail->linkedin_link,
                    'Extra_Links'             => $userDetail->extra_links
                ]]
            ];
        }
        else{
            return false;
        }
    }

    protected function getZohoSocialId($accessToken, $userId)
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/Social_Media/search', [
                'criteria' => "(Social_Media_Id:equals:{$userId})"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }

    protected function sendToZoho($accessToken, array $payload, $zohoId = null)
    {
        $url = $zohoId
            ? "https://www.zohoapis.eu/crm/v2/Social_Media/{$zohoId}"
            : "https://www.zohoapis.eu/crm/v2/Social_Media";

        $method = $zohoId ? 'put' : 'post';

        return Http::withToken($accessToken)->$method($url, $payload);
    }

}
