<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait ApiResponse
{
    public function sendResponse($message, $result = [])
    {
    	$response = [
            'success' => true,
            'message' => $message,
        ];

        if(!empty($result)){
            $response['data'] = $result;
        }

        return response()->json($response, 200);
    }

    public function sendError($errorMessage, $error = [], $code = 400)
    {
    	$response = [
            'success' => false,
            'message' => $errorMessage,
        ];

        if(!empty($error)){
            $response['data'] = $error;
        }

        return response()->json($response, $code);
    }
}
