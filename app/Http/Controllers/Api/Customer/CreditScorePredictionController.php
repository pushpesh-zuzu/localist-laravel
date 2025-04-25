<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Symfony\Component\Process\Process;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Helpers\CustomHelper as Util;

use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Http\Controllers\Api\ApiController;

class CreditScorePredictionController extends Controller
{
    public function predictCreditScore(Request $request){
        try {
            $json = file_get_contents('php://input');
            $escapedJson = escapeshellarg($json);
        
            // Adjust the path based on where you put predict.py
            $command = "python3 python/predict.py $escapedJson";
            $output = shell_exec($command);
        
            echo $output;
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }
}