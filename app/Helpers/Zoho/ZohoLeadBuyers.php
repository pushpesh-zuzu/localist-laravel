<?php
namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Support\Facades\Log;


class ZohoLeadBuyers
{
    public function integrateZohoLeadBuyers($userId)
    {


        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }

        //$zohoId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);

        $payload = $this->buildLeadBuyerPayload($userId);

        $response = $this->upsertToZohoService($access_token, $payload);

        $responseData = $response->json();

        if (
            isset($responseData['data'][0]['status']) &&
            $responseData['data'][0]['status'] === 'success' &&
            isset($responseData['data'][0]['details']['id'])
        ) {
            $zohoRecordId = $responseData['data'][0]['details']['id'];
            User::where('id', $userId)->update([
                'zoho_record_id' => $zohoRecordId,
            ]);
        }
        $usedCredits = $response->header('X-API-COST'); // this may return 24

        Log::info('Zoho API Credit Used for LeadBuyer Sync', [
            'user_id' => $userId,
            'credits_used' => $response->headers()
        ]);

        return $responseData;

    }


    protected function buildLeadBuyerPayload($userId)
    {
        $user = User::with('details','primaryCategory')->findOrFail($userId);
        $payload = [
            'data' => [[
                'Lead_buyer_auto_id'            => $user->id,
                'Lead Buyer Registration Name'  => $user->name,
                'Name'                          => $user->name,
                'Company_Registration_Number'   => $user->company_reg_number,
                'about_company'                 => $user->about_company,
                'company_phone'                 => $user->company_phone,
                'phone'                         => $user->phone,
                'Email'                         => $user->email,
                'company_email'                 => $user->company_email,
                'company_total_years'           => $user->company_total_years,
                'country'                       => $user->country,
                'Onlines'                       => $user->is_online  == 1 ? 'Yes' : 'No',
                'city'                          => $user->city,
                'Single_Line_11'                => optional($user->primaryCategory)->name,
                'zipcode'                       => ($user->zipcode) ? $user->zipcode : $user->details->billing_postcode,
                'company_location'              => $user->company_location,
                'apartment'                     => $user->apartment,
                'registration_type'             => $user->form_status  == 1 ? 'Completed' : 'Abandoned',
                //'Active_Status'                 => $user->status  == 2 ? 'Rejected' : 'Accepted',
                'Company_Sales_Team'            => $user->company_sales_team  == 1 ? 'Yes' : 'No',
                'total_credit'                  => $user->total_credit,
                'company_size'                  => $user->company_size,
                'New_Jobs_Per_Month'            => $user->new_jobs,
                //'Social_Media'                => $user->social_media == 1 ? 'Yes' : 'No',
                'phone'                         => $user->phone,
                'Auto_Bid'                      => optional($user->details)->is_autobid == 1 ? 'Yes' : 'No',
                'company_name'                  => $user->company_name,
                'Rating'                        => 0,
                'company_website'               => $user->company_website,
                'address'                       => $user->address,
                'company_locaion_reason'        => $user->company_locaion_reason,
                'YouTube'                       => $user->details->company_youtube_link,
                'Facebook'                      => $user->details->fb_link,
                'Twitter'                       => $user->details->twitter_link,
                'TikTok'                        => $user->details->tiktok_link,
                'Instagram'                     => $user->details->insta_link,
                'LinkedIn'                      => $user->details->linkedin_link,
                'Extra_Links'                   => $user->details->extra_links,
            ]],
            'duplicate_check_fields' => ['Lead_buyer_auto_id']
        ];

        return $payload;
    }

    protected function upsertToZohoService($accessToken, array $payload)
    {
        return Http::withToken($accessToken)
            ->post('https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/upsert', $payload);
    }

    // protected function sendToZoho($accessToken, array $payload, $zohoId = null)
    // {

    //     $url = $zohoId
    //         ? "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/{$zohoId}"
    //         : "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration";

    //     $method = $zohoId ? 'put' : 'post';

    //     // $response = Http::withToken($accessToken)
    //     //     ->get('https://www.zohoapis.eu/crm/v2/settings/fields', [
    //     //         'module' => 'Lead_Buyer_Registration'
    //     //     ]);

    //     // $fields = $response->json();
    //     // $formatted = collect($fields['fields'])->map(function ($field) {
    //     //     return [
    //     //         'api_name'    => $field['api_name'] ?? null,
    //     //         'field_label' => $field['field_label'] ?? null,
    //     //     ];
    //     // });


    //     // $formatted = $formatted->sortBy('field_label')->values()->all();

    //     // Log::info('Zoho Lead_Buyer_Registration API Field Map:', $formatted);

    //     return Http::withToken($accessToken)->$method($url, $payload);
    // }


    public function integrateZohoSocialMediaDetails($userId){
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }
        // $zohoId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);
        $user = User::with('details')->findOrFail($userId);
        $zohoId = $user->zoho_record_id;

        if (!$zohoId) {
            Log::warning("Zoho ID not found for user {$userId}");
            return false;
        }

         $payload = [
            'data' => [[

               'YouTube'     => $user->details->company_youtube_link,
                'Facebook'    => $user->details->fb_link,
                'Twitter'     => $user->details->twitter_link,
                'TikTok'      => $user->details->tiktok_link,
                'Instagram'   => $user->details->insta_link,
                'LinkedIn'    => $user->details->linkedin_link,
                'Extra_Links' => $user->details->extra_links,

            ]]
        ];

        $url = "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/{$zohoId}";
        $response = Http::withToken($access_token)->put($url, $payload);

        // if($zohoId){
        //     $response = $this->sendToZoho($access_token, $payload, $zohoId);
        // }
        // else{
        //     return false;
        // }


        $responseData = $response->json();

        return $responseData;
    }

    public function integrateZohoDetails($userId){
        $access_token = ZohoHelper::getAccessToken();

        if (!$access_token) {
            return null;
        }
        // $zohoId = ZohoHelper::getZohoLeadBuyerId($access_token, $userId);
        $user = User::with('details')->findOrFail($userId);
        $zohoId = $user->zoho_record_id;

        if (!$zohoId) {
            Log::warning("Zoho ID not found for user {$userId}");
            return false;
        }

         $payload = [
            'data' => [[

                'city'                          =>($user->city) ? $user->city : $user->details->billing_city,
                'zipcode'                       =>($user->zipcode) ? $user->zipcode : $user->details->billing_postcode,
                'phone'                         =>($user->phone) ? $user->phone : $user->details->billing_phone,
                'address'                       => ($user->address) ? $user->address : $user->details->billing_address1,

            ]]
        ];

        $url = "https://www.zohoapis.eu/crm/v2/Lead_Buyer_Registration/{$zohoId}";
        $response = Http::withToken($access_token)->put($url, $payload);

        // if($zohoId){
        //     $response = $this->sendToZoho($access_token, $payload, $zohoId);
        // }
        // else{
        //     return false;
        // }


        $responseData = $response->json();

        return $responseData;
    }



}
