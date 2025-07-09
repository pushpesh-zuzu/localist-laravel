<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CompanyRegService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = '19248275-4e7b-4ae7-a628-e0efd694cc31';
    }

    public function getCompanyDetails($companyNumber)
    {
        $response = Http::withBasicAuth($this->apiKey, '')
            ->get("https://api.company-information.service.gov.uk/company/{$companyNumber}");

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'error' => 'Failed to fetch company details',
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }


}
