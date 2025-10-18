<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\Category;
use App\Models\Bid;
use App\Models\UserDetail;
use App\Models\UserAccreditation;
use App\Models\UserServiceDetail;
use App\Models\ProfileQuestion;
use App\Models\ProfileQA;
use App\Models\UserCardDetail;
use App\Models\UserService;
use App\Models\LeadRequest;
use App\Models\PurchaseHistory;
use App\Models\Plan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Otp;
use App\Models\AbandonedUser;
use App\Models\UserServiceLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Log as FacadesLog, Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use Log;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoLeadBuyers;
use App\Helpers\Zoho\ZohoQuoteCustomers;
use App\Models\SmsLog;
use \Carbon\Carbon;
use Exception;
use Illuminate\Container\Attributes\Log as AttributesLog;
use GuzzleHttp\Client;
use App\Services\OctoparseService;

class OctoparseController extends Controller
{
    public function getGoogleReviews(Request $request, OctoparseService $octoparseService)
    {
        $validator = Validator::make($request->all(), [
            'place_map_url' => 'required',
          ], [
            'place_map_url.required' => 'Google Place Map URL is required.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $taskId = CustomHelper::setting_value('octoparse_google_reviews_task_id', 'your_default_task_id'); // replace with your actual task ID
        $size = 500;

        try {
            $res = $octoparseService->getTaskData($taskId, $size);
            // echo "<pre>";
            // print_r($res);
            // die;

            // map raw data to cleaned structure (depends on how your Octoparse task fields are named)
            $rows = $res['data']['dataList'] ?? [];

            // Example transform (adjust keys to what your task returns)
            $clean = array_map(function ($row) {
                return [
                    'reviewer' => $row['Reviewer'] ?? null,
                    'rating' => preg_replace('/[^0-9.]/', '', $row['Rating']) ?? null,
                    'text' => $row['Review'] ?? null,
                    'date' => $row['Review_time'] ?? null,
                ];
            }, $rows);

            return $this->sendResponse('Google reviews fetched successfully.',[
                'status' => 'ok',
                'total' => $res['data']['total'] ?? null,
                'count' => count($clean),
                'reviews' => $clean,
            ]);
        } catch (\Throwable $e) {
            return $this->sendError('Failed to fetch reviews: ' . $e->getMessage(), [], 500);
        }
    }
}