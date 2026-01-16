<?php

namespace App\Http\Controllers\Api\Facebook\Forms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Log, Mail, Validator
};
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Helpers\CustomHelper;
use App\Helpers\CreditScorePredictor as CreditScore;
use App\Helpers\Zoho\ZohoCustomerQuestionAnswer;
use App\Helpers\Zoho\ZohoAbandonCustomerQuoteRequest;
use App\Helpers\Zoho\ZohoEmails;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoQuoteCustomers;
use App\Helpers\Zoho\ZohoQuoteRequest;
use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Models\ServiceQuestion;
use App\Http\Controllers\Api\ApiController;
use App\Models\EmailLog;
use App\Models\NotificationSetting;
use App\Models\UserDetail;
use App\Models\AutobidStatusLog;
use App\Models\AbandonedUser;
use App\Models\SmsLog;
use App\Models\Postcode;
use App\Services\D7LeadFinderService;
use App\Services\LeadService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Container\Attributes\Log as AttributesLog;

use App\Http\Controllers\Api\Customer\MyRequestController;


class DrivewayInstallationForm extends Controller
{
    public function tt(Request $request){
        $accessToken = ZohoHelper::getAccessToken();
        $userId = User::where('email','test-lead-new-14@test.com')->value('id');
        $zohoId = ZohoHelper::getZohoQuoteCustomerId($accessToken, $userId);
        print_r('zohoId: ' .$zohoId);
    }

    public function getFacebookLeadsDrivewayInstallationFrom(Request $request, LeadService $leadService){
        $payload   = $request->all();
        $serviceId = 51;

        $fbArray = $this->getFacebookQuestionsInfoArray($request, $serviceId);

        // echo "<pre>";
        // print_r($fbArray);
        // exit;
        
        if(!empty($fbArray['questions']) && !empty($fbArray['info'])){
            $questions = json_encode($fbArray['questions']);
            $name = $fbArray['info']['full_name'];
            $email = $fbArray['info']['email'];
            $phone = $fbArray['info']['phone_number'];
            $postcode = $fbArray['info']['postcode'];

            $vc = CustomHelper::getCityNameFromPostcode($postcode);
            $city = $vc['valid'] ? $vc['city'] : 'N/A';
            
            $user = User::where('email', $email)->first();

            if(empty($user)){
                $password = Str::random(8);
                $dataUser['password'] = Hash::make($password);
                $dataUser['name'] = $name;
                $dataUser['email'] = $email;
                if (isset($phone) && !empty($phone)) {
                    $cleanPhone = preg_replace('/\s+/', '', $phone); // remove spaces
                    if (strpos($cleanPhone, '+44') !== 0) {
                        $cleanPhone = ltrim($cleanPhone, '0');
                        $dataUser['phone'] = '+44' . $cleanPhone;
                    } else {
                        $dataUser['phone'] = $cleanPhone;
                    }
                }
                $dataUser['zipcode'] = $postcode;
                $dataUser['utm_source'] = "Facebook Form";
                $dataUser['city'] = $city;
                $dataUser['entry_url'] = $request->entry_url ?? null;
                $dataUser['user_ip_address'] = $request->user_ip_address ?? null;
                $dataUser['user_type'] = 2;
                $dataUser['active_status'] = 2;
                $dataUser['form_status'] = 1;
                $dataUser['phone_verified'] = 1;
                $dataUser['created_at'] = date('Y-m-d H:i:s');
                $dataUser['updated_at'] = date('Y-m-d H:i:s');

                $euId = User::insertGetId($dataUser);

                if(!empty($euId)){

                    UserDetail::create([
                        'user_id'  => $euId,
                        'is_autobid'  =>1,
                        'billing_contact_name' => $dataUser['name'],
                        'billing_phone' => $dataUser['phone'],
                        'billing_vat_register' => 1,
                    ]);

                    $dataAb['user_id'] = $euId;
                    $dataAb['action'] = 'enabled';
                    AutobidStatusLog::insertGetId($dataAb);


                    $now = now();
                    NotificationSetting::insert([
                        [
                            'user_id'   => $euId,
                            'noti_name' => 'customer_email_change_in_request',
                            'noti_value'=> 1,
                            'user_type' => 'customer',
                            'noti_type' => 'email',
                            'created_at'=> $now,
                            'updated_at'=> $now,
                        ],
                        [
                            'user_id'   => $euId,
                            'noti_name' => 'customer_email_reminder_to_reply',
                            'noti_value'=> 1,
                            'user_type' => 'customer',
                            'noti_type' => 'email',
                            'created_at'=> $now,
                            'updated_at'=> $now,
                        ],
                        [
                            'user_id'   => $euId,
                            'noti_name' => 'customer_email_update_about_new_feature',
                            'noti_value'=> 1,
                            'user_type' => 'customer',
                            'noti_type' => 'email',
                            'created_at'=> $now,
                            'updated_at'=> $now,
                        ],
                    ]);

                }
                // 1) Integrate Quote Customer
                CustomHelper::runInBackground(function() use ($euId){
                    app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($euId);
                    Log::info('finished quote customer background integration');
                });

                // 2) Send Welcome Email IF form_status = 1
                CustomHelper::runInBackground(function() use ($euId, $password) {
                    ZohoEmails::sendWelcomeEmailQuoteCustomer($euId, $password, "000");
                });
            }

            $user = User::where('email', $email)->first();
            $token = $user->createToken('authToken', ['user_id' => $user->id])->plainTextToken;
            $user->update(['remember_token' => $token]);

            $requestController = new MyRequestController();

            $newPayload = [
                'service_id'    => $serviceId,
                'postcode'    => $postcode,
                'questions' => $questions,
                'phone'  => str_replace('+44', '', $phone),
                'user_id' => $user->id,
                'city'   => $city,
            ];

            $request->replace($newPayload);

            $request->headers->set(
                'Authorization',
                'Bearer ' . $token
            );

            $response = $requestController->createNewRequest($request, $leadService);
            
            return $response;
        }else{
            Log::channel('single')->info('Facebook Form Lead Payload', [
                'payload' => $request->all(),
            ]);
        }
        

    }
    
    private function getFacebookQuestionsInfoArray($payload, $serviceId)
{
    // Handle Request or array
    if ($payload instanceof \Illuminate\Http\Request) {
        $payload = $payload->all();
    }

    /**
     * Resolve Facebook fields
     */
    if (isset($payload['payload']['mappable_field_data'])) {
        $facebookFields = collect($payload['payload']['mappable_field_data']);
    } elseif (isset($payload['mappable_field_data'])) {
        $facebookFields = collect($payload['mappable_field_data']);
    } else {
        $facebookFields = collect();
    }

    /**
     * Meta → info mapping
     */
    $infoFieldMap = [
        'email'        => 'email',
        'full_name'    => 'full_name',
        'phone_number' => 'phone_number',
        'postcode'    => 'postcode',
    ];

    /**
     * Load service questions
     */
    $serviceQuestions = ServiceQuestion::where('category', $serviceId)->get();

    /**
     * Normalize helper
     */
    $normalize = fn ($text) =>
        Str::slug(
            strtolower(preg_replace('/[^\w\s]/', '', $text)),
            '_'
        );

    /**
     * Build service question lookup
     */
    $serviceQuestionMap = [];
    foreach ($serviceQuestions as $sq) {
        $serviceQuestionMap[$normalize($sq->questions)] = $sq;
    }

    $questions = [];
    $info = [];
    $usedServiceQuestionIds = [];

    foreach ($facebookFields as $fbField) {

        /**
         * 1️⃣ Handle info fields
         */
        if (array_key_exists($fbField['name'], $infoFieldMap)) {
            $info[$infoFieldMap[$fbField['name']]] = trim($fbField['value']);
            continue;
        }

        $fbQuestionSlug = $normalize($fbField['name']);
        $fbAnswerSlug   = $normalize($fbField['value']);

        $matchedServiceQuestion = null;

        /**
         * 2️⃣ Try strict match first
         */
        foreach ($serviceQuestionMap as $serviceSlug => $sq) {
            if (
                ! in_array($sq->id, $usedServiceQuestionIds, true) &&
                $fbQuestionSlug === $serviceSlug
            ) {
                $matchedServiceQuestion = $sq;
                break;
            }
        }

        /**
         * 3️⃣ Try safe fallback match
         */
        if (! $matchedServiceQuestion) {
            foreach ($serviceQuestionMap as $serviceSlug => $sq) {
                if (
                    ! in_array($sq->id, $usedServiceQuestionIds, true) &&
                    (
                        str_contains($fbQuestionSlug, $serviceSlug) ||
                        str_contains($serviceSlug, $fbQuestionSlug)
                    )
                ) {
                    $matchedServiceQuestion = $sq;
                    break;
                }
            }
        }

        /**
         * 4️⃣ Resolve question + answer
         */
        if ($matchedServiceQuestion) {

            // Map answer via service options
            $answers = json_decode($matchedServiceQuestion->answer, true) ?? [];
            $matchedAnswer = null;

            foreach ($answers as $option) {
                if ($normalize($option['option']) === $fbAnswerSlug) {
                    $matchedAnswer = $option['option'];
                    break;
                }
            }

            if ($matchedAnswer === null) {
                $matchedAnswer = ucfirst(str_replace('_', ' ', $fbField['value']));
            }

            $questions[] = [
                'ques' => $matchedServiceQuestion->questions,
                'ans'  => $matchedAnswer,
            ];

            $usedServiceQuestionIds[] = $matchedServiceQuestion->id;

        } else {
            /**
             * 5️⃣ NO MATCH → include Facebook question as-is
             */
            $questions[] = [
                'ques' => ucfirst(str_replace('_', ' ', rtrim($fbField['name'], '?'))) ."?",
                'ans'  => ucfirst(str_replace('_', ' ', $fbField['value'])),
            ];
        }
    }

    return [
        'questions' => $questions,
        'info'      => $info,
    ];
}





}