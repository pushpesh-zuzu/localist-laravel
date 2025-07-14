<?php
namespace App\Helpers\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

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
        $users->update([
            'zoho_record_id' => $zohoId
        ]);
        return $response->json();

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
                'Name'                          => $user->name,
                'Email'                         => $user->email,
                'email_verified_at'             => $user->email_verified_at,
                'created_at'                    => $user->created_at,
                'updated_at'                    => now()->format('c'),
                'phone'                         => $user->phone,
                'password'                      => $user->password,
                'phone_verified'                => $user->phone_verified,
                'about_company'                 => $user->about_company,
                'company_locaion_reason'        => $user->company_locaion_reason,
                'company_phone'                 => $user->company_phone,
                'company_email'                 => $user->company_email,
                'company_total_years'           => $user->company_total_years,
                'country'                       => $user->country,
                'is_online'                     => $user->is_online,
                'sms_notification_no'           => $user->sms_notification_no,
                'city'                          => $user->city,
                'primary_category'              => $user->primary_category,
                'last_login'                    => $user->last_login,
                'zipcode'                       => $user->zipcode,
                'stripe_payment_method_id'      => $user->stripe_payment_method_id,
                'deleted_at'                    => $user->deleted_at,
                'stripe_customer_id'            => $user->stripe_customer_id,
                'company_location'              => $user->company_location,
                'apartment'                     => $user->apartment,
                'user_type'                     => $user->user_type,
                'otp'                           => $user->otp,
                'active_status'                 => $user->active_status,
                'registration_type'             => $user->form_status  == 1 ? 'Completed' : 'Abandoned',
                'company_logo'                  => $user->company_logo,
                'uuid'                          => $user->uuid,
                'lead_buyer_status'             => 'Added',
                'remember_token'                => $user->remember_token,
                'company_sales_team'            => $user->company_sales_team,
                'total_credit'                  => $user->total_credit,
                'company_size'                  => $user->company_size,
                'social_media'                  => $user->social_media,
                'company_name'                  => $user->company_name,
                'new_jobs'                      => $user->new_jobs,
                'avg_rating'                    => $user->avg_rating,
                'country_code'                  => $user->country_code,
                'company_website'               => $user->company_website,
                'address'                       => $user->address,
                'gender'                        => $user->gender,
                'profile_image'                 => $user->profile_image,
                'is_company_website'            => $user->is_company_website
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
