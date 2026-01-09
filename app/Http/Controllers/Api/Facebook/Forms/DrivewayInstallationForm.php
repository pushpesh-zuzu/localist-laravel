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

        $questions = $this->getFacebookQuestions($request, $serviceId);
        $name = $payload['data']['full name'];
        $email = $payload['data']['email'];
        $phone = $payload['data']['phone_number'];
        $postcode = $payload['data']['post_code'];

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
            CustomHelper::runInBackground(function() use ($euId) {
                app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($euId);
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

    }
    
    private function getFacebookQuestions($payload, $serviceId)
    {
        

        // If coming from internal call
        if (isset($payload['payload']['mappable_field_data'])) {
            $facebookFields = collect($payload['payload']['mappable_field_data']);
        }
        // If coming from Make (Facebook)
        elseif (isset($payload['mappable_field_data'])) {
            $facebookFields = collect($payload['mappable_field_data']);
        }
        else {
            $facebookFields = collect();
        }

        /**
         * Explicitly exclude non-question fields.
         * These should NEVER appear in the final output.
         */
        $excludeFields = [
            'email',
            'full name',
            'phone_number',
            'post_code',
        ];

        /**
         * Fetch all service questions for this category.
         * These define the canonical wording and answer options.
         */
        $serviceQuestions = ServiceQuestion::where('category', $serviceId)->get();

        /**
         * Normalize any text into a comparable slug:
         * - lowercase
         * - remove punctuation
         * - convert spaces to underscores
         */
        $normalize = fn ($text) =>
            Str::slug(
                strtolower(preg_replace('/[^\w\s]/', '', $text)),
                '_'
            );

        /**
         * Split a slug into meaningful keywords.
         * - ignores filler words (length <= 2)
         */
        $keywords = function (string $slug) {
            return collect(explode('_', $slug))
                ->reject(fn ($word) => strlen($word) <= 2)
                ->values();
        };

        $output = [];

        foreach ($facebookFields as $fbField) {

            /**
             * Skip contact / meta fields.
             */
            if (in_array($fbField['name'], $excludeFields, true)) {
                continue;
            }

            $fbQuestionSlug = $normalize($fbField['name']);
            $fbAnswerSlug   = $normalize($fbField['value']);

            foreach ($serviceQuestions as $sq) {

                $serviceQuestionSlug = $normalize($sq->questions);

                /**
                 * QUESTION MATCHING STRATEGY
                 *
                 * Facebook questions are paraphrased versions of service questions.
                 * Exact or substring matches are NOT sufficient.
                 *
                 * We match based on keyword overlap:
                 * - Extract keywords from both questions
                 * - If at least 3 meaningful words overlap → same question
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
                 * Decode service answer options.
                 * These define the canonical answer casing.
                 */
                $answers = json_decode($sq->answer, true) ?? [];
                $matchedAnswer = null;

                /**
                 * Try to match Facebook answer to a service option.
                 */
                foreach ($answers as $option) {
                    if ($normalize($option['option']) === $fbAnswerSlug) {
                        $matchedAnswer = $option['option']; // preserve original case
                        break;
                    }
                }

                /**
                 * Fallback:
                 * If Facebook sent a value that doesn't exist in service options,
                 * format it nicely instead of dropping it.
                 */
                if ($matchedAnswer === null) {
                    $matchedAnswer = ucwords(str_replace('_', ' ', $fbField['value']));
                }

                /**
                 * Final output item:
                 * - Question text comes ONLY from service questions
                 * - Answer text preserves service casing
                 */
                $output[] = [
                    'ques' => $sq->questions,
                    'ans'  => $matchedAnswer,
                ];

                /**
                 * Stop searching once matched.
                 */
                break;
            }
        }

        return json_encode($output);
    }


}