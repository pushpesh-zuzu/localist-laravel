<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ZeroBounceService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('ZEROBOUNCE_API_KEY');
    }

    /**
     * Validate email using ZeroBounce API
     */
    public function validateEmail($email)
    {
        
        $response = Http::get('https://api.zerobounce.net/v2/validate', [
            'api_key' => $this->apiKey,
            'email'   => $email
        ]);
     
        return $response->json();
    }
}
