<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\CustomHelper;
use App\Models\User;
use App\Models\WhatsappLog;
use Exception;

class WhatsAppMessage
{
    /**
     * Fetch WhatsApp configuration from settings table
     */
    protected static function getConfig(): array
    {
        $config = [
            'phone_number_id' => CustomHelper::setting_value('whatsapp_phone_number_id'),
            'access_token'    => CustomHelper::setting_value('whatsapp_access_token'),
            'app_secret'      => CustomHelper::setting_value('whatsapp_app_secret'),
            'verify_token'    => CustomHelper::setting_value('whatsapp_verify_token'),
        ];

        if (in_array(null, $config, true)) {
            Log::error('WhatsApp config missing', $config);
            throw new Exception('Incomplete WhatsApp configuration. Check your settings table.');
        }

        return $config;
    }

    /**
     * Send a text or image WhatsApp message with logging
     */

    public static function sendMessage(?int $userId = null, string $phoneNumber = '', string $message = '', ?string $imageUrl = null, ?string $subject = null, ?bool $previewUrl = null)
    {
        try {


            $to = $phoneNumber;

            // If userId is provided, fetch the phone from database
            if ($userId) {
                $user = User::select('phone')->where('id', $userId)->first();
                if ($user && !empty($user->phone)) {
                    $to = $user->phone;
                } else {
                    Log::warning("User $userId not found or phone missing. Using provided phone number.");
                }
            }

            if (empty($to)) {
                Log::warning("No phone number available to send WhatsApp message.");
                return; // stop execution safely
            }

            $toNumber =  ltrim($to, '+');
            $config = self::getConfig();
            // dd($config['phone_number_id']);
            $url = "https://graph.facebook.com/v22.0/{$config['phone_number_id']}/messages";

            if ($imageUrl) {
                // Image message
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'to' => $toNumber,
                    'type' => 'image',
                    'image' => [
                        'link' => $imageUrl,
                        'caption' => $message,
                    ],
                    // 'recipient_type' => 'individual',
                ];
            } else {
                // Text message
                $textPayload = ['body' => $message];

                if (!is_null($previewUrl)) {
                    $textPayload['preview_url'] = $previewUrl;
                }

                $payload = [
                    'messaging_product' => 'whatsapp',
                    'to' => $toNumber,
                    'type' => 'text',
                    'text' => $textPayload,
                    // 'recipient_type' => 'individual',
                ];
            }

            $response = Http::withToken($config['access_token'])
                ->acceptJson()
                ->post($url, $payload);

            $status = $response->successful() ? 1 : 2;

            // Insert log 
            WhatsappLog::create([
                'user_id'   => $userId ?? null,
                'to'        => $toNumber,
                'subject'   => $subject,
                'message'   => $message,
                'image_url' => $imageUrl,
                'payload'   => $payload,
                'response'  => $response->json(),
                'status'    => $status,
            ]);

            if ($status === 2) {
                Log::error('WhatsApp Message Failed', [
                    'to' => $toNumber,
                    'payload' => $payload,
                    'response' => $response->json(),
                ]);

                return;
            }

            Log::info('WhatsApp Message Sent', [
                'to' => $toNumber,
                'message' => $message,
                'image' => $imageUrl,
                'response' => $response->json(),
            ]);
        } catch (Exception $e) {

            // Insert into DB
            WhatsappLog::create([
                'user_id'   => $userId,
                'to'        => $toNumber,
                'subject'   => $subject,
                'message'   => $message,
                'image_url' => $imageUrl,
                'payload'   => $payload ?? null,
                'response'  => ['error' => $e->getMessage()],
                'status'    => 2,
            ]);

            Log::error('WhatsApp Message Exception', [
                'to' => $toNumber,
                'message' => $message,
                'image' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }



    public static function sendTemplate(?int $userId = null, ?string $phoneNumber = null, string $templateName, string $languageCode = 'en_US', array $components = [], ?string $subject = null)
    {
        try {
            // Determine recipient
            if ($userId) {
                $user = User::select('name', 'phone')->where('id', $userId)->first();
                $to = $user?->phone;
            } else {
                $to = $phoneNumber;
            }

            if (!$to) {
                Log::warning("Phone number not found or missing.");
                return ['success' => false, 'message' => 'Phone number missing.'];
            }

            $toNumber =  ltrim($to, '+');

            $config = self::getConfig();
            //  dd($config['phone_number_id']);
            $url = "https://graph.facebook.com/v20.0/{$config['phone_number_id']}/messages";

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $toNumber,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $languageCode],
                ],
            ];

            if (!empty($components)) {
                $payload['template']['components'] = $components;
            }

            $response = Http::withToken($config['access_token'])
                ->acceptJson()
                ->post($url, $payload);

            // Log in DB
            WhatsappLog::create([
                'user_id' => $userId,
                'to' => $toNumber,
                'subject' => $subject ?? $templateName,
                'message' => '', // template has no plain message
                'image_url' => null,
                'payload' => $payload,
                'response' => $response->json(),
                'status' => $response->successful() ? 1 : 2,
            ]);

            return $response->json();
        } catch (Exception $e) {
            Log::error('WhatsApp Template Exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }



    /**
     * Send a WhatsApp template message with logging
     */
    // public static function sendTemplate(int $userId, string $templateName, string $languageCode = 'en_US',  array $components = [],       ?string $subject = null)
    // {
    //     try {

    //         $user = User::select('name', 'phone')->where('id', $userId)->first();

    //         if (!$user || empty($user->phone)) {
    //             Log::warning("User $userId not found or phone number is missing.");
    //             return;
    //         }
    //         $to = $user->phone;
    //         $config = self::getConfig();
    //         $url = "https://graph.facebook.com/v20.0/{$config['phone_number_id']}/messages";

    //         $payload = [
    //             'messaging_product' => 'whatsapp',
    //             'to' => $to,
    //             'type' => 'template',
    //             'template' => [
    //                 'name' => $templateName,
    //                 'language' => ['code' => $languageCode],
    //             ],
    //         ];

    //         if (!empty($components)) {
    //             $payload['template']['components'] = $components;
    //         }

    //         $response = Http::withToken($config['access_token'])
    //             ->acceptJson()
    //             ->post($url, $payload);

    //         $status = $response->successful() ? 1 : 2;

    //         // Insert log into DB
    //         WhatsappLog::create([
    //             'user_id'   => $userId,
    //             'to'        => $to,
    //             'subject'   => $subject ?? $templateName,
    //             'message'   => '', // template has no plain message
    //             'image_url' => null,
    //             'payload'   => $payload,
    //             'response'  => $response->json(),
    //             'status'    => $status,
    //         ]);

    //         if ($status === 2) {
    //             Log::error('WhatsApp Template Message Failed', [
    //                 'to' => $to,
    //                 'payload' => $payload,
    //                 'response' => $response->json(),
    //             ]);
    //         }

    //         Log::info('WhatsApp Template Message Sent', [
    //             'to' => $to,
    //             'template' => $templateName,
    //             'response' => $response->json(),
    //         ]);
    //     } catch (Exception $e) {
    //         // Insert into DB
    //         WhatsappLog::create([
    //             'user_id'   => $userId,
    //             'to'        => $to,
    //             'subject'   => $subject ?? $templateName,
    //             'message'   => '',
    //             'image_url' => null,
    //             'payload'   => $payload ?? null,
    //             'response'  => ['error' => $e->getMessage()],
    //             'status'    => 2,
    //         ]);

    //         Log::error('WhatsApp Template Message Exception', [
    //             'to' => $to,
    //             'error' => $e->getMessage(),
    //         ]);
    //     }
    // }


    /**
     * Verify incoming webhook from WhatsApp
     */
    public static function verifyWebhook($request)
    {
        try {
            $config = self::getConfig();
            if ($request->hub_verify_token === $config['verify_token']) {
                Log::info('WhatsApp Webhook Verified Successfully');
                return response($request->hub_challenge, 200);
            }
            Log::warning('WhatsApp Webhook Verification Failed', ['provided_token' => $request->hub_verify_token]);
            return response('Invalid verify token', 403);
        } catch (Exception $e) {
            Log::error('WhatsApp Webhook Verification Exception', ['error' => $e->getMessage()]);
            return response('Error verifying webhook', 500);
        }
    }

    public static function handleWebhook($request)
    {
        try {

            $config = self::getConfig();
            $signature = $request->header('X-Hub-Signature-256');
            $body = $request->getContent();

            // Validate signature
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $body, $config['app_secret']);
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('WhatsApp Webhook Signature Mismatch', [
                    'provided' => $signature,
                    'expected' => $expectedSignature
                ]);
                return response('Invalid signature', 403);
            }

            $data = $request->all();

            Log::info('WhatsApp Webhook Received', ['payload' => $data]);

            if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
                $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
                $from = $message['from'] ?? null;
                $text = $message['text']['body'] ?? null;

                if ($from && $text) {
                    Log::info("WhatsApp Message Received", ['from' => $from, 'text' => $text]);

                    // Log incoming message
                    WhatsappLog::create([
                        'user_id'   => null,
                        'to'        => $from,
                        'subject'   => 'Incoming Message',
                        'message'   => $text,
                        'image_url' => null,
                        'payload'   => $message,
                        'response'  => null,
                        'status'    => 1
                    ]);

                    // Auto-reply using updated sendMessage()
                    WhatsAppMessage::sendMessage(
                        null,       // userId is null
                        $from,      // phone number to send to
                        "Thanks for your message: \"$text\""
                    );
                }
            }

            return response('EVENT_RECEIVED', 200);
        } catch (Exception $e) {
            Log::error('WhatsApp Webhook Handling Exception', ['error' => $e->getMessage()]);
            return response('Webhook handling error', 500);
        }
    }
}
