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
    public function predictCreditScore(Request $request)
    {
        

        $user_id = $request['user_id'];

        //remove unwanted params
        unset($request['user_id']);

        $input = $request->all();
        $jsonInput = json_encode($input);

        $pythonApiPath = base_path('python/landscaping.py');
        // Call Python script
        // echo PHP_INT_SIZE === 8 ? '64-bit PHP' : '32-bit PHP';
        // print_r(getenv('PYTHON_PATH'));

        
        $process = new Process([getenv('PYTHON_PATH'), $pythonApiPath, $jsonInput]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'success' => false,
                'error' => $process->getErrorOutput()
            ], 500);
        }

        $output = json_decode($process->getOutput(), true);

        return response()->json([
            'success' => true,
            'prediction' => $output['prediction'] ?? null
        ]);
    }
}