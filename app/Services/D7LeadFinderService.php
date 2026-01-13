<?php

namespace App\Services;

use App\Models\LeadRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZeptoMail;
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
                Log::error('D7 Search HTTP Error', [
                    'lead_id' => $leadId,
                    'status'  => $searchResponse->status(),
                    'body'    => $searchResponse->body(),
                ]);
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
        ];

        foreach ($searches as $search) {
            try {
                $response = Http::timeout(15)->get("{$this->baseUrl}/results/", [
                    'id'  => $search->search_id,
                    'key' => $this->apiKey,
                ]);

                $response->throw();
                $suppliers = $response->json();

              
                if (empty($suppliers)) {
                    $search->update(['status' => 'completed']);
                    continue;
                } else {
                    // Lock search
                    $search->update(['status' => 'processing']);
                }

                $suppliers = array_filter($suppliers, function ($supplier) use ($skipEmails) {
                    return isset($supplier['email']) && !in_array(strtolower($supplier['email']), $skipEmails);
                });
                
               
                if (empty($suppliers)) {
                    // If all suppliers are skipped, mark search as completed
                    $search->update(['status' => 'completed']);
                    continue;
                }

                $leadRequest = LeadRequest::with(['customer', 'category'])->find($search->lead_id);

                $questionsAndAnswers = collect(json_decode($leadRequest->arrayed_questions, true))
                    ->filter(fn($item) => isset($item['ques'], $item['ans']) && is_array($item['ans']))
                    ->map(fn($item) => [
                        'question' => $item['ques'],
                        'answer'   => implode(', ', $item['ans']),
                    ])
                    ->toArray();



                // 🔹 Batch size = 10 suppliers per job
                $batches = array_chunk($suppliers, 10);

                foreach ($batches as $index => $batch) {
                    if (!empty($batch)) {
                        CustomHelper::runInBackground(function () use ($batch, $search, $questionsAndAnswers, $batches, $index) {
                            app(ZeptoMail::class)->sendMailToD7Supplier(
                                $batch,
                                $search->keyword,
                                $search->city,
                                $search->country,
                                $search->lead_id,
                                $questionsAndAnswers,
                            );

                            if ($index + 1 === count($batches)) {
                                D7SearchLog::find($search->id)->update(['status' => 'completed']);
                            }
                        });
                    }
                }

                Log::info('D7 Results API Success', [
                    'search_id'     => $search->search_id,
                    'keyword'       => $search->keyword,
                    'city'          => $search->city,
                    'country'       => $search->country,
                    'total_records' => count($suppliers),
                    'total_batches' => count($batches),
                ]);
            } catch (\Throwable $e) {

                $search->update(['status' => 'failed']);

                Log::error('D7 Results API Error', [
                    'search_id' => $search->search_id,
                    'keyword'   => $search->keyword,
                    'city'      => $search->city,
                    'country'   => $search->country,
                    'message'   => $e->getMessage(),
                ]);
            }
        }
    }
}
