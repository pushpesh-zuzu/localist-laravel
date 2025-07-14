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
                'Company Registration Number'  => $user->company_reg_number,
                'About company'                 => $user->about_company,
                'Company Phone'                 => $user->company_phone,
                'Company Email'                 => $user->company_email,
                'Company Total Years'           => $user->company_total_years,
                'Country'                       => $user->country,
                'Online'                        => $user->is_online  == 1 ? 'Yes' : 'No',
                'City'                          => $user->city,
                'Primary Category'              => optional($user->primaryCategory)->name,
                'Zipcode'                       => $user->zipcode,
                'Company Location'              => $user->company_location,
                'Apartment'                     => $user->apartment,
                'Registration Type'             => $user->form_status  == 1 ? 'Completed' : 'Abandoned',
                'Status'                        => $user->status  == 1 ? 'Added' : 'Rejected',
                'Company Sales Team'            => $user->company_sales_team,
                'Total Credit'                  => $user->total_credit,
                'Company Size'                  => $user->company_size,
                'Social Media'                  => optional($user->userDetail)->fb_link ?? 'Nil',
                'Auto Bid'                      => optional($user->userDetail)->is_autobid == 1 ? 'Yes' : 'No',
                'Company Name'                  => $user->company_name,
                'Rating'                        => 'Nil',
                'Company Website'               => $user->company_website,
                'Address'                       => $user->address
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
