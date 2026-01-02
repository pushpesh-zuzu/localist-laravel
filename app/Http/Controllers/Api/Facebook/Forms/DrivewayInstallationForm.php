<?php

namespace App\Http\Controllers\Api\Facebook\Forms;
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
use App\Models\ServiceQuestion;
use App\Services\LeadService;

class DrivewayInstallationForm extends Controller
{
    public function getFacebookLeadsDrivewayInstallationFrom(Request $request){
        FacadesLog::channel('single')->info('Facebook Lead Payload', [
            'payload' => $request->all(),
        ]);

        return response()->json([
            'status' => 'ok'
        ]);
    }
}