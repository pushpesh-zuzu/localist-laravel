<?php

namespace App\Services;

use App\Models\LeadRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZeptoMail;
use App\Models\D7LeadSupplier;
use App\Models\D7SearchLog;
use App\Models\EmailSetting;

class D7LeadFinderService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $defaultCountry;

    public function __construct()
    {
        $this->apiKey         = CustomHelper::setting_value('d7leadfinder_api_key');
        $this->baseUrl        = 'https://dash.d7leadfinder.com/app/api';
        $this->defaultCountry = 'GB';
    }


    public function fetchSuppliersByLeadId(int $leadId, ?string $country = 'GB'): ?array
    {
        try {

            $sendEmail = EmailSetting::where('setting_name', 'D7 Supplier Send Mail')
                ->value('setting_value');

            if (!$sendEmail) {
                return null;
            }

            $leadRequest = LeadRequest::with(['customer', 'category'])->find($leadId);
            $customerName  = strtolower($leadRequest->customer->name ?? '');
            $customerEmail = strtolower($leadRequest->customer->email ?? '');

            if (str_contains($customerName, 'test') ||   str_contains($customerEmail, 'test')) {
                Log::info('D7 Search skipped for test/testing customer', [
                    'lead_id' => $leadId,
                    'name'    => $customerName,
                    'email'   => $customerEmail,
                ]);
                return null;
            }

            if (!$leadRequest) {
                Log::warning('D7 Lead not found', ['lead_id' => $leadId]);
                return null;
            }


            $keyword = trim(optional($leadRequest->category)->name ?? '');
            $city    = trim($leadRequest->city ?? '');
            $country = strtoupper($country ?: $this->defaultCountry);

            if ($keyword === '' || $city === '') {
                Log::warning('D7 Missing keyword or city', [
                    'lead_id' => $leadId,
                    'keyword' => $keyword,
                    'city'    => $city,
                ]);
                return null;
            }

            $searchResponse = Http::timeout(10)->retry(2, 200)->get("{$this->baseUrl}/search/", [
                'keyword'  => $keyword,
                'country'  => $country,
                'location' => $city,
                'key'      => $this->apiKey,
            ]);

            if (!$searchResponse->successful()) {

                return null;
            }

            $searchData = $searchResponse->json() ?? [];

            if (!empty($searchData['error']) || empty($searchData['searchid'])) {
                $this->logSearchError(
                    $keyword,
                    $city,
                    $country,
                    $searchData['error'] ?? 'searchid_missing',
                    $leadId
                );
                return null;
            }

            $searchId = $searchData['searchid'];

            D7SearchLog::create([
                'search_id' => $searchId,
                'keyword'   => $keyword,
                'city'      => $city,
                'country'   => $country,
                'lead_id'   => $leadId,
                'status'    => 'new',
            ]);


            $resultsResponse = Http::timeout(15)->retry(2, 300)->get("{$this->baseUrl}/results/", [
                'id'  => $searchId,
                'key' => $this->apiKey,
            ]);

            if (!$resultsResponse->successful()) {
                Log::error('D7 Results HTTP Error', [
                    'search_id' => $searchId,
                    'status'    => $resultsResponse->status(),
                    'body'      => $resultsResponse->body(),
                ]);
                return null;
            }

            return $resultsResponse->json() ?? null;
        } catch (\Throwable $e) {
            Log::critical('D7 Unexpected Exception', [
                'lead_id' => $leadId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return null;
        }
    }


    private function logSearchError(string $keyword, string $city, string $country, string $error, $leadId): void
    {
        $messages = [
            'invalid_country'       => 'Country code should be 2 characters',
            'invalid_location'      => 'City empty or not found',
            'invalid_key'           => 'Invalid API key',
            'invalid_keyword'       => 'Keyword empty',
            'rate_limit_5_seconds'  => 'Rate limit exceeded (1 search / 5 sec)',
        ];

        D7SearchLog::create([
            'keyword' => $keyword,
            'city'    => $city,
            'country' => $country,
            'status'  => 'failed',
            'error'   => $messages[$error] ?? $error,
            'lead_id' => $leadId ?? '',
        ]);
    }

    /**
     * Get suppliers by searchid
     */

    public function getSearchSuppliers()
    {
        $sendEmail = EmailSetting::where('setting_name', 'D7 Supplier Send Mail')
            ->value('setting_value');

        if (!$sendEmail) {
            return;
        }

        $searches = D7SearchLog::where('status', 'new')
            ->whereNotNull('search_id')
            ->where('created_at', '<', now()->subMinutes(5))
            ->get();

        $skipEmails = [
            'willpulford@ravenstonegardenservices.com',
            'darren@newhomeimprovement.gro',
            'Newgardenhomecompany.com',
            'Newdrivewaycompany.com',
            'Newhomeimprovement.com',
            'willpulford@ravenstonegardenservices.com',
            'ravenstonegardenservices.com',
            'contact@ravenstonegardenservices.com',
            'ravenstonegardenservices.com',
        ];

        foreach ($searches as $search) {
            try {

                $search->update(['status' => 'processing']);

                $response = Http::timeout(15)->get("{$this->baseUrl}/results/", [
                    'id'  => $search->search_id,
                    'key' => $this->apiKey,
                ]);

                $response->throw();
                $suppliers = $response->json();

                if (empty($suppliers)) {
                    $search->update(['status' => 'completed']);
                    continue;
                }

                self::addUpdateSuppliers($suppliers, $search->keyword);

                $dbSuppliers = D7LeadSupplier::where('lead_service', $search->keyword)
                    ->whereNotNull('email')
                    ->where('mail_sent', 0)
                    ->where('is_subscribed', 1)
                    ->where(function ($q) use ($skipEmails) {
                        foreach ($skipEmails as $skip) {
                            if (str_contains($skip, '@')) {
                                $q->where('email', '!=', strtolower($skip));
                            } else {
                                $q->where('email', 'NOT LIKE', '%@' . strtolower($skip));
                            }
                        }
                    })
                    ->get();

                if ($dbSuppliers->isEmpty()) {
                    $search->update(['status' => 'completed']);
                    continue;
                }

                $leadRequest = LeadRequest::with(['customer', 'category'])
                    ->find($search->lead_id);

                $questionsAndAnswers = collect(json_decode($leadRequest->arrayed_questions, true))
                    ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                    ->map(fn($item) => [
                        'question' => $item['ques'],
                        'answer'   => implode(', ', $item['ans']),
                    ])
                    ->toArray();

                // suppliers (10 per batch)
                $batches = $dbSuppliers->chunk(10);

                foreach ($batches as $index => $batch) {

                    $batchArray = $batch->toArray();
                    $searchId = $search->id;
                    $keyword = $search->keyword;
                    $city = $search->city;
                    $country = $search->country;
                    $leadId = $search->lead_id;
                    $isLastBatch = $index + 1 === $batches->count();

                    CustomHelper::runInBackground(function () use ($batchArray, $searchId, $keyword, $city, $country, $leadId, $questionsAndAnswers, $isLastBatch) {
                        app(ZeptoMail::class)->sendMailToD7Supplier($batchArray, $keyword, $city, $country, $leadId, $questionsAndAnswers);

                        if ($isLastBatch) {
                            D7SearchLog::where('id', $searchId)->update(['status' => 'completed']);
                        }
                    });
                }
            } catch (\Throwable $e) {

                $search->update(['status' => 'failed']);

                Log::error('D7 Results API Error', [
                    'search_id' => $search->search_id,
                    'keyword'   => $search->keyword,
                    'city'      => $search->city,
                    'country'   => $search->country,
                    'message'   => $e->getMessage(),
                    'file'      => $e->getFile(),   // 👈 exact file
                    'line'      => $e->getLine(),   // 👈 exact line
                    'trace'     => collect($e->getTrace())->take(5)->toArray(), // 👈 first 5 stack calls
                ]);
            }
        }
    }




    public static function addUpdateSuppliers(array $suppliers, $keyword)
    {
        foreach ($suppliers as $supplier) {

            if (empty($supplier['email'])) {
                continue;
            }

            $email = strtolower($supplier['email']);

            $existing = D7LeadSupplier::where('email', $email)->first();

            if ($existing && $existing->is_subscribed == 0) {
                continue;
            }

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

                'lead_service'          => $keyword,
                'mail_sent'             => 0,
            ];

            $data = array_filter($data, fn($v) => $v !== null && $v !== '');

            D7LeadSupplier::updateOrCreate(
                ['email' => $email],
                $data
            );
        }
    }
}
