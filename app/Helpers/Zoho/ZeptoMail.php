<?php

namespace App\Helpers\Zoho;

use App\Helpers\CustomHelper;
use App\Models\D7LeadSupplier;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class ZeptoMail
{
    public static  function sendMailToD7Supplier(array $suppliers, string $keyword,  string $city,   string $country,   int $leadId,  array $questionsAndAnswers)
    {

        $zeptomailEndpoint = 'https://api.zeptomail.eu/v1.1/email';
        $zeptoApiKey = CustomHelper::setting_value('zeptomail_api_key');
        $fromEmail = CustomHelper::setting_value('zeptomail_from_email');
        $fromName  = CustomHelper::setting_value('zoho_default_from_name');

        foreach ($suppliers as $supplier) {

            if (empty($supplier['email'])) {
                continue;
            }

            try {

                $htmlView = view('emails.d7suppliers.lead_notification', [
                    'baseUrl' => config('app.react_base_url'),
                    'siteUrl' => config('app.url'),
                    'lead' => [
                        'id'        => $leadId,
                        'keyword'   => $keyword,
                        'city'      => $city,
                        'country'   => $country,
                        'questions' => $questionsAndAnswers,
                    ],
                    'supplier' => $supplier,
                ])->render();

                $htmlContent = (new CssToInlineStyles())->convert($htmlView);

                $payload = [
                    'from' => [
                        'address' => $fromEmail,
                        'name'    => $fromName,
                    ],
                    'to' => [
                        [
                            'email_address' => [
                                'address' => $supplier['email'],
                                'name'    => $supplier['name'] ?? '',
                            ],
                        ],
                    ],
                    'subject'  => 'Verfied Local lead Avail - Now',
                    'htmlbody' => $htmlContent,
                ];


                $response = Http::withHeaders([
                    'accept'        => 'application/json',
                    'authorization' => 'Zoho-enczapikey ' . trim($zeptoApiKey),
                    'content-type'  => 'application/json',
                ])->post($zeptomailEndpoint, $payload);

                $responseData = $response->json();

                // Log::info('API Response: ' . json_encode($responseData, JSON_PRETTY_PRINT));
                /// $responseData = [];
                $supplierResult =  self::addUpdateSuppliers($supplier, $keyword);

                $messageId =  null;
                $dataE['user_id'] = $supplierResult->id ?? 1;
                $dataE['from_email'] = $fromEmail;
                $dataE['to_email'] = $supplier['email'];
                $dataE['lead_id'] = $leadId ?? null;
                $dataE['message_id'] = $messageId;
                $dataE['subject'] = $payload['subject'];
                $dataE['setting_name'] = 'D7 Supplier Send Mail';
                $dataE['content'] = $htmlContent;
                $dataE['zoho_url'] = $zeptomailEndpoint;
                $dataE['response'] = json_encode($responseData);
                EmailLog::insertGetId($dataE);
            } catch (\Throwable $e) {
                $messageId =  null;
                $dataE['user_id'] =  1;
                $dataE['from_email'] = $fromEmail;
                $dataE['to_email'] = $supplier['email'];
                $dataE['lead_id'] = $leadId ?? null;
                $dataE['message_id'] = $messageId;
                $dataE['subject'] = 'Verfied Local lead Avail - Now';
                $dataE['setting_name'] = 'D7 Supplier Send Mail';
                $dataE['content'] = '';
                $dataE['zoho_url'] = $zeptomailEndpoint;
                $dataE['response'] = $e->getMessage();
                EmailLog::insertGetId($dataE);
            }
        }
    }


    public static  function addUpdateSuppliers(array $supplier, $keyword)
    {

        $data = [
            'name'                  => $supplier['name'] ?? null,
            'phone'                 => $supplier['phone'] ?? null,
            'website'               => $supplier['website'] ?? null,
            'category'              => $supplier['category'] ?? null,

            'address1'              => $supplier['address1'] ?? null,
            'address2'              => $supplier['address2'] ?? null,
            'region'                => $supplier['region'] ?? null,
            'zip'                   => $supplier['zip'] ?? null,
            'country'               => $supplier['country'] ?? null,

            'google_stars'          => $supplier['google_stars'] ?? 0,
            'google_review_count'   => $supplier['google_review_count'] ?? 0,

            'instagram_followers'   => $supplier['instagram_followers'] ?? 0,
            'instagram_follows'     => $supplier['instagram_follows'] ?? 0,
            'instagram_media_count' => $supplier['instagram_media_count'] ?? 0,

            'facebook_url'          => $supplier['facebook_url'] ?? null,
            'instagram_url'         => $supplier['instagram_url'] ?? null,
            'linkedin_url'          => $supplier['linkedin_url'] ?? null,
            'lead_service'          => $keyword ?? null,
        ];


        $data = array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });

        return D7LeadSupplier::updateOrCreate(
            ['email' => $supplier['email']],
            $data
        );
    }
}
