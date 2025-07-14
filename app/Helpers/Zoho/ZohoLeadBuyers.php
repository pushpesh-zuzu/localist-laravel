<?php
namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class ZohoLeadBuyers
{
    public function integrateZohoLeadBuyers($user)
    {

        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        $zohoId = $this->getZohoLeadBuyerId($access_token, $user->id);
        $payload = $this->buildLeadBuyerPayload($user, $zohoId);
        $response = $this->sendToZoho($access_token, $payload, $zohoId);

        $responseData = $response->json();
        if (
            isset($responseData['data'][0]['status']) &&
            $responseData['data'][0]['status'] === 'success' &&
            isset($responseData['data'][0]['details']['id'])
        ) {
            $zohoRecordId = $responseData['data'][0]['details']['id'];
            User::where('id', $user->id)->update([
                'zoho_record_id' => $zohoRecordId,
            ]);
        }
        return $responseData;

    }
    public static function getZohoLeadBuyerId($accessToken, $userId)
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/search', [
                'criteria' => "(Lead_buyer_auto_id:equals:{$userId})"
            ]);

        $data = $response->json();

        return $data['data'][0]['id'] ?? null;
    }

    protected function buildLeadBuyerPayload($user, $zohoId = null)
    {
        $payload = [
            'data' => [[
                'Lead_buyer_auto_id'            => $user->id,
                'Lead Buyer Registration Name'  => $user->name,
                'Name'                          => $user->name,
                'Company_Registration_Number'  => $user->company_reg_number,
                'about_company'                 => $user->about_company,
                'company_phone'                 => $user->phone,
                'Email'                         => $user->email,
                'company_email'                 => $user->company_email,
                'company_total_years'           => $user->company_total_years,
                'country'                       => $user->country,
                'Onlines'                        => $user->is_online  == 1 ? 'Yes' : 'No',
                'city'                          => $user->city,
                'Single_Line_11'              => optional($user->primaryCategory)->name,
                'zipcode'                       => $user->zipcode,
                'company_location'              => $user->company_location,
                'apartment'                     => $user->apartment,
                'registration_type'             => $user->form_status  == 1 ? 'Completed' : 'Abandoned',
                'Active_Status'                        => $user->status  == 1 ? 'Added' : 'Rejected',
                'company_sales_team'            => $user->company_sales_team,
                'total_credit'                  => $user->total_credit,
                'company_size'                  => $user->company_size,
                'Social_Media'                  => optional($user->userDetail)->fb_link ?? 'Nil',
                'Auto_Bid'                      => optional($user->userDetail)->is_autobid == 1 ? 'Yes' : 'No',
                'company_name'                  => $user->company_name,
                'avg_rating'                        => 'Nil',
                'company_website'               => $user->company_website,
                'address'                       => $user->address
            ]]
        ];

        if (!$zohoId) {
            $payload['data'][0]['created_at'] = now()->format('c');
        }

        return $payload;
    }

    protected function sendToZoho($accessToken, array $payload, $zohoId = null)
    {
        $url = $zohoId
            ? "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/{$zohoId}"
            : "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration";

        $method = $zohoId ? 'put' : 'post';

        return Http::withToken($accessToken)->$method($url, $payload);
    }


}
