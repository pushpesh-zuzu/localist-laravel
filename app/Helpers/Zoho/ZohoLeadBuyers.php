<?php
namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Support\Facades\Log;


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
        $recId = User::where('id', $userId)->value('zoho_record_id');
        if(!empty($recId)){
            return $recId;
        }
        
        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/search', [
                'criteria' => "(Lead_buyer_auto_id:equals:{$userId})"
            ]);

        $data = $response->json();
        
        if(!empty($data['data'][0]['id'])){
            $zohoId = User::where('id', $userId)->update([
                'zoho_record_id' => $data['data'][0]['id']
            ]);
            return $data['data'][0]['id'];
        }
        return null;
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
                'company_phone'                 => $user->company_phone,
                'phone'                         => $user->phone,
                'Email'                         => $user->email,
                'company_email'                 => $user->company_email,
                'company_total_years'           => $user->company_total_years,
                'country'                       => $user->country,
                'Onlines'                        => $user->is_online  == 1 ? 'Yes' : 'No',
                'city'                          => $user->city,
                'Single_Line_11'                => optional($user->primaryCategory)->name,
                'zipcode'                       => $user->zipcode,
                'company_location'              => $user->company_location,
                'apartment'                     => $user->apartment,
                'registration_type'             => $user->form_status  == 1 ? 'Completed' : 'Abandoned',
                'Active_Status'                 => $user->status  == 2 ? 'Rejected' : 'Accepted',
                'company_sales_team'            => $user->company_sales_team,
                'total_credit'                  => $user->total_credit,
                'company_size'                  => $user->company_size,
                'Social_Media'                  => $user->social_media == 1 ? 'Yes' : 'No',
                'Auto_Bid'                      => optional($user->userDetail)->is_autobid == 1 ? 'Yes' : 'No',
                'company_name'                  => $user->company_name,
                'avg_rating'                    => 'Nil',
                'company_website'               => $user->company_website,
                'address'                       => $user->address,
                'company_locaion_reason'       => $user->company_location_reason
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

        $response = Http::withToken($accessToken)
            ->get('https://www.zohoapis.eu/crm/v2/settings/fields', [
                'module' => 'Lead_Buyer_Registration'
            ]);

        $fields = $response->json();
        $formatted = collect($fields['fields'])->map(function ($field) {
            return [
                'api_name'    => $field['api_name'] ?? null,
                'field_label' => $field['field_label'] ?? null,
            ];
        });


        $formatted = $formatted->sortBy('field_label')->values()->all();

        Log::info('Zoho Lead_Buyer_Registration API Field Map:', $formatted);

        return Http::withToken($accessToken)->$method($url, $payload);
    }


}
