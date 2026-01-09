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


    public function getFacebookLeadsDrivewayInstallationFrom(Request $request, LeadService $leadService){
        $payload   = $request->all();
        $serviceId = 51;

        $fbArray = $this->getFacebookQuestionsInfoArray($request, $serviceId);
        
        if(!empty($fbArray['questions']) && !empty($fbArray['info'])){
            $questions = json_encode($fbArray['questions']);
            $name = $fbArray['info']['full_name'];
            $email = $fbArray['info']['email'];
            $phone = $fbArray['info']['phone'];
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
        /**
         * Determine source of Facebook fields:
         * - From internal call (payload.payload.mappable_field_data)
         * - From Make.com webhook (payload.mappable_field_data)
         */
        if (isset($payload['payload']['mappable_field_data'])) {
            $facebookFields = collect($payload['payload']['mappable_field_data']);
        } elseif (isset($payload['mappable_field_data'])) {
            $facebookFields = collect($payload['mappable_field_data']);
        } else {
            $facebookFields = collect();
        }

        /**
         * Map Facebook meta fields → info keys
         * These should NOT appear in questions.
         */
        $infoFieldMap = [
            'email'        => 'email',
            'full name'    => 'full_name',
            'phone_number' => 'phone',
            'post_code'    => 'postcode',
        ];

        /**
         * Fetch all service questions for this category.
         * These define canonical question text and answer options.
         */
        $serviceQuestions = ServiceQuestion::where('category', $serviceId)->get();

        /**
         * Normalize text to a comparable slug
         * - lowercase
         * - remove punctuation
         * - underscore separated
         */
        $normalize = fn ($text) =>
            Str::slug(
                strtolower(preg_replace('/[^\w\s]/', '', $text)),
                '_'
            );

        /**
         * Extract meaningful keywords from a slug.
         * Removes filler words (length <= 2).
         */
        $keywords = function (string $slug) {
            return collect(explode('_', $slug))
                ->reject(fn ($word) => strlen($word) <= 2)
                ->values();
        };

        $questions = [];
        $info = [];

        foreach ($facebookFields as $fbField) {

            /**
             * 1️⃣ Handle META / CONTACT fields → info[]
             */
            if (array_key_exists($fbField['name'], $infoFieldMap)) {
                $info[$infoFieldMap[$fbField['name']]] =
                    is_array($fbField['value'])
                        ? $fbField['value']
                        : trim($fbField['value']);

                continue;
            }

            /**
             * 2️⃣ Handle SERVICE QUESTIONS
             */
            $fbQuestionSlug = $normalize($fbField['name']);
            $fbAnswerSlug   = $normalize($fbField['value']);

            foreach ($serviceQuestions as $sq) {

                $serviceQuestionSlug = $normalize($sq->questions);

                /**
                 * QUESTION MATCHING STRATEGY
                 * Match based on keyword overlap (paraphrase-safe).
                 * If 3 or more meaningful words overlap → same question.
                 */
                $fbWords      = $keywords($fbQuestionSlug);
                $serviceWords = $keywords($serviceQuestionSlug);

                $questionMatches = $fbWords
                    ->intersect($serviceWords)
                    ->count() >= 3;

                if (! $questionMatches) {
                    continue;
                }

                /**
                 * Decode service answer options (canonical answers).
                 */
                $answers = json_decode($sq->answer, true) ?? [];
                $matchedAnswer = null;

                /**
                 * Try exact answer match against service options.
                 */
                foreach ($answers as $option) {
                    if ($normalize($option['option']) === $fbAnswerSlug) {
                        $matchedAnswer = $option['option']; // preserve original casing
                        break;
                    }
                }

                /**
                 * Fallback:
                 * If no option matched, format Facebook value cleanly.
                 */
                if ($matchedAnswer === null) {
                    $matchedAnswer = ucwords(str_replace('_', ' ', $fbField['value']));
                }

                /**
                 * Add to questions output.
                 */
                $questions[] = [
                    'ques' => $sq->questions,
                    'ans'  => $matchedAnswer,
                ];

                break; // stop after first successful match
            }
        }

        /**
         * Final structured payload
         */
        return [
            'questions' => $questions,
            'info'      => $info,
        ];
    }



}