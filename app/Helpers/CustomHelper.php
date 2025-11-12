<?php

namespace App\Helpers;

use Illuminate\Support\Facades\{DB, Log, URL, Auth, File, Mail, Session, Http};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Events\NewNotificationEvent;
use App\Helpers\Zoho\ZohoEmails;
use App\Helpers\Zoho\ZohoFinance;
use App\Models\PurchaseHistory;
use App\Models\NotificationSetting;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Postcode;
use Illuminate\Support\Carbon;
use App\Jobs\IntegrateZohoPurchaseHistory;
use App\Jobs\RunCallableJob;
use App\Models\Invoice;
use App\Models\PlanHistory;
use App\Models\User;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use App\Models\UserCardDetail;
use App\Models\UserDetail;
use App\Models\Review;
use App\Models\CustomReview;


class CustomHelper
{

    public static function getAverageRating($user_id)
    {
        $localists_reviews = Review::where('user_id', $user_id)->where('source', 'localists')->count();
        $facebook_reviews = CustomReview::where('user_id', $user_id)->where('review_platform', 'facebook')->value('review_count');
        $google_reviews = CustomReview::where('user_id', $user_id)->where('review_platform', 'google')->value('review_count');
        $trustpilot_reviews = CustomReview::where('user_id', $user_id)->where('review_platform', 'trustpilot')->value('review_count');


        $localists_score = Review::where('user_id', $user_id)->where('source', 'localists')->avg('ratings');
        $facebook_score = CustomReview::where('user_id', $user_id)->where('review_platform', 'facebook')->value('ratings');
        $google_score = CustomReview::where('user_id', $user_id)->where('review_platform', 'google')->value('ratings');
        $trustpilot_score = CustomReview::where('user_id', $user_id)->where('review_platform', 'trustpilot')->value('ratings');

        $average_rating = 0;
        $avgCount = 0;
        $total_reviews = 0;
        $final_avg_rating = 0;

        if (!empty($facebook_reviews) && !empty($facebook_score)) {
            $average_rating += $facebook_score;
            $avgCount++;
            $total_reviews += $facebook_reviews;
        }

        if (!empty($google_reviews) && !empty($google_score)) {
            $average_rating += $google_score;
            $avgCount++;
            $total_reviews += $google_reviews;
        }

        if (!empty($trustpilot_reviews) && !empty($trustpilot_score)) {
            $average_rating += $trustpilot_score;
            $avgCount++;
            $total_reviews += $trustpilot_reviews;
        }

        if (!empty($localists_reviews) && !empty($localists_score)) {
            $average_rating += $localists_score;
            $avgCount++;
            $total_reviews += $localists_reviews;
        }

        if ($avgCount > 0  && $average_rating > 0) {
            $final_avg_rating = $average_rating / $avgCount;
            $data2['avg_rating'] = number_format($final_avg_rating, 1);
            $data2['updated_at'] = date('y-m-d H:i:s');
            User::where('id', $user_id)->update($data2);
        }

        return [
            'average_rating' => number_format($final_avg_rating, 2),
            'total_reviews' => $total_reviews
        ];
    }


    /**
     * Run any callable in the background.
     *
     * @param callable $callable
     * @return void
     */
    public static function runInBackground(callable $callable)
    {
        RunCallableJob::dispatch($callable);
    }

    public static function createSectorsRecursive($data, $index = '1', $space = 40)
    {
        if (count($data['subsectors']) > 0) {
            $i = 1;
            foreach ($data['subsectors'] as $d) {
                $currentIndex = $index . '.' . $i;
                $deleteFormId = 'delete-form-' . $d['id'];

                echo '<tr>
                        <td>
                            <span style="margin-left:' . $space . 'px;"></span>
                            <strong>' . $currentIndex . '.</strong>
                            <img src="' . \App\Helpers\CustomHelper::displayImage($d['category_icon'], 'category') . '" height="25" width="25" style="display: inline" /> &nbsp;' . $d['name'] . '
                        </td>
                        <td>' . ($d['status'] ? 'Active' : 'Inactive') . '</td>
                        <td>';

                        if (auth()->user()->can('sector.subsectoredit')) {
                           echo ' <a href="' . route('sectors.edit', $d['id']) . '" title="Edit"><i class="fas fa-edit"></i></a> &nbsp';
                        }
                           if (auth()->user()->can('sector.subsectordelete')) {
                           echo '  <a href="javascript:void(0);" onclick="event.preventDefault(); if(confirm(\'Are you sure to delete?\')) document.getElementById(\'' . $deleteFormId . '\').submit();" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>

                            <form id="' . $deleteFormId . '" action="' . route('sectors.destroy', $d['id']) . '" method="POST" style="display: none;">
                                ' . method_field('DELETE') . csrf_field() . '
                            </form>';
                            }
                       echo  '</td>
                    </tr>';

                // Recursive call
                if (count($d['subsectors']) > 0) {
                    self::createSectorsRecursive($d, $currentIndex, 2 * $space);
                }

                $i++;
            }
        }
    }


    public static function logNotifications($userId, $leadId, $notiName, $title, $message, $checkExisting = false, $notiType = 'browser', $userType = 'buyer')
    {
        $insertLog = true;
        $isSettingOn = NotificationSetting::where('user_id', $userId)
            ->where('noti_name', $notiName)
            ->where('user_type', $userType)
            ->where('noti_type', $notiType)
            ->value('noti_value');

        //check whether setting is turned on or not
        if (!$isSettingOn) {
            $insertLog = false;
        }

        // if checkExisting is true then check if the same lead exists or not
        if ($checkExisting) {
            $logExists = NotificationLog::where('user_id', $userId)
                ->where('lead_id', $leadId)
                ->where('noti_name', $notiName)
                ->where('user_type', $userType)
                ->where('noti_type', $notiType)
                ->value('id');
            //if notification exists then do not log the notification
            if ($logExists) {
                $insertLog = false;
            }
        }

        //insert if all conditions id fulfiled
        if ($insertLog && $userId) {
            NotificationLog::create([
                'user_id'  => $userId,
                'lead_id' => $leadId,
                'noti_name'  => $notiName,
                'title' => $title,
                'message'  => $message,
                'user_type' => $userType,
                'noti_type' => $notiType
            ]);
        }
    }

    public static function formatTimeDuration(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 mins';
        }

        $days = floor($minutes / 1440); // 1440 mins in a day
        $hours = floor(($minutes % 1440) / 60);
        $mins = $minutes % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' day' . ($days > 1 ? 's' : '');
        }
        if ($hours > 0) {
            $parts[] = $hours . ' hr' . ($hours > 1 ? 's' : '');
        }
        if ($mins > 0) {
            $parts[] = $mins . ' min' . ($mins > 1 ? 's' : '');
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);
        return implode(', ', $parts) . ' and ' . $last;
    }


    public static function getCurrentAutobidBatch(int $userId): ?array
    {
        // Get the most recent status log
        $latestLog = DB::table('autobid_status_logs')
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$latestLog || !in_array($latestLog->action, ['enabled', 'resumed'])) {
            return null; // Autobid is currently OFF or paused
        }

        // Now get the most recent time it was turned ON or resumed BEFORE any pause/disable
        $log = DB::table('autobid_status_logs')
            ->where('user_id', $userId)
            ->whereIn('action', ['enabled', 'resumed'])
            ->where('id', '<=', $latestLog->id)
            ->orderBy('id', 'desc')
            ->first();

        $activeSince = Carbon::parse($log->created_at);

        // Calculate current batch
        $today = Carbon::today();
        $daysSinceStart = $activeSince->diffInDays($today);
        $batchNumber = intdiv($daysSinceStart, 7); // 0-based

        $batchStart = $activeSince->copy()->addDays($batchNumber * 7);
        $batchEnd = $batchStart->copy()->addDays(6);

        return [
            'start' => $batchStart->format('Y-m-d'),
            'end' => $batchEnd->format('Y-m-d'),
            'batch_number' => $batchNumber + 1
        ];
    }


    public static function numberToWords($number)
    {
        $f = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number));
    }

    public static function getPostcodesWithinRadiusQuery($postcode, $radius = 0, $km = false)
    {
        $val = $km ? 6371 : 3959; // Earth radius

        // Get latitude and longitude of the given postcode
        $center = Postcode::where('postcode', $postcode)->first();
        if (!$center) {
            return null; // or throw exception if no postcode found
        }

        $lat = $center->latitude;
        $lng = $center->longitude;

        // Instead of fetching -> return a query builder (subquery)
        return \DB::table('postcodes')
            ->select('postcode')
            ->whereRaw("(
                $val * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )
            ) <= ?", [$lat, $lng, $lat, $radius]);
    }


    public static function getPostcodesWithinRadius($postcode, $radius = 0, $km = false)
    {
        $val = $km ? 6371 : 3959;

        // Get latitude and longitude of the given postcode
        $center = Postcode::where('postcode', $postcode)->first();
        if (!$center) {
            return []; // or throw exception
        }
        $lat = $center->latitude;
        $lng = $center->longitude;
        // Haversine formula to get nearby postcodes
        $results = \DB::table('postcodes')
            ->select(
                'postcode',
                'latitude',
                'longitude',
                DB::raw("(
                    $val * acos(
                        cos(radians(?)) *
                        cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(latitude))
                    )
                ) AS distance")
            )
            ->addBinding($lat)
            ->addBinding($lng)
            ->addBinding($lat)
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get()->toArray();
        $pureArray = json_decode(json_encode($results), true);
        return $pureArray;
    }

    public static function getCityNameFromPostcode($postcode)
    {
        $apiKey = CustomHelper::setting_value('google_maps_api');

        // Normalize input
        $postcode = strtoupper(trim($postcode));
        $postcode = preg_replace('/\s+/', '', $postcode); // remove all spaces

        // Auto-insert a space before the last 3 characters
        if (strlen($postcode) > 3) {
            $postcode = substr($postcode, 0, -3) . ' ' . substr($postcode, -3);
        }

        // ✅ Strict UK postcode regex
        $isValidFormat = preg_match('/^(GIR 0AA|[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2})$/', $postcode);

        if (!$isValidFormat) {
            return [
                'valid' => false,
                'postcode' => $postcode,
                'error' => 'Invalid UK postcode format.',
            ];
        }

        // --- First: Try Postcodes.io API (free & UK-specific) ---
        try {
            $response = Http::get("https://api.postcodes.io/postcodes/{$postcode}");
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 200 && isset($data['result'])) {
                    $result = $data['result'];
                    $city = $result['admin_district'] ?? null;
                    $region = $result['region'] ?? null;
                    $gPostcode = $result['postcode'] ?? $postcode;

                    if ($city && $region) {
                        return [
                            'valid' => true,
                            'city' => $city,
                            'region' => $region,
                            'postcode' => $gPostcode,
                            'formatted_address' => "{$city}, {$region}, UK",
                            'source' => 'postcodes.io',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // ignore fallback failure
        }

        // --- Fallback: Google Maps API (if Postcodes.io fails) ---
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
            'address' => $postcode,
            'key' => $apiKey,
            'region' => 'uk',
            'components' => 'country:GB',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (!empty($data['results'][0])) {
                $result = $data['results'][0];
                $country = $city = $region = $gPostcode = null;

                foreach ($result['address_components'] as $component) {
                    if (in_array('country', $component['types'])) {
                        $country = $component['short_name'];
                    }
                    if (in_array('postal_town', $component['types']) || in_array('locality', $component['types'])) {
                        $city = $component['long_name'];
                    }
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $region = $component['long_name'];
                    }
                    if (in_array('postal_code', $component['types'])) {
                        $gPostcode = $component['long_name'];
                    }
                }

                if ($country === 'GB' && !empty($city) && !empty($region)) {
                    return [
                        'valid' => true,
                        'city' => $city,
                        'region' => $region,
                        'postcode' => $gPostcode ?? $postcode,
                        'formatted_address' => $result['formatted_address'],
                        'source' => 'google',
                    ];
                }
            }
        }

        // --- If both fail ---
        return [
            'valid' => false,
            'postcode' => $postcode,
            'error' => 'Postcode not recognized or missing city/region information.',
        ];
    }



    public static function getCoordinates($postcode)
    {
        $apiKey = CustomHelper::setting_value('google_maps_api');
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
            'address' => $postcode,
            'key' => $apiKey,
        ]);

        $data = $response->json();
        if (!empty($data['results'][0])) {
            return json_encode($data['results'][0]['geometry']['location']); // ['lat' => ..., 'lng' => ...]
        }

        return null;
    }

    public static function update_setting_value($key, $value)
    {
        if (!empty($value)) {
            Setting::where('setting_name', $key)->update([
                'setting_value' => $value
            ]);
        }
    }

    public static function setting_value($key, $defaultValue = '')
    {
        $val = Setting::where('setting_name', $key)->value('setting_value');
        $rel = !empty($val) ? $val : $defaultValue;
        return $rel;
    }

    public static function getSingleNotificationSetting($user_id, $noti_name, $user_type = 'buyer', $noti_type = 'email')
    {
        $val = NotificationSetting::where('user_id', $user_id)
            ->where('noti_name', $noti_name)
            ->where('user_type', $user_type)
            ->where('noti_type', $noti_type)
            ->value('noti_value');

        $rel = !empty($val) ? $val : 0;
        return $rel;
    }

    public static function createTrasactionLogold($userId, $amount, $credits, $detail, $status = 1, $type = 0, $error_response = '')
    {

        static $zohoRegistered = false;
        $data['user_id'] = $userId;
        $data['purchase_date'] = date('Y-m-d');
        $data['price'] = $amount;
        $data['credits'] = $credits;
        $data['details'] = $detail;
        $data['payment_type'] = $type;
        $data['error_response'] = $error_response;
        $data['status'] = $status;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = PurchaseHistory::insertGetId($data);


        // if (!$zohoRegistered && $status === 1) {
        //     $zohoRegistered = true;
        //     CustomHelper::runInBackground(function() use ($userId, $id) {
        //         try {
        //             $zoho = new ZohoFinance();
        //             $zoho->integratePurchaseHistory($userId, $id);
        //         } catch (\Throwable $e) {
        //             Log::error('Zoho shutdown integration failed: ' . $e->getMessage());
        //         }
        //     });
        // }

        return $id;
    }



    public static function createTrasactionLog($userId, $amount, $credits, $detail, $status = 1, $type = 0, $error_response = '')
    {

        $debitTransactionId = PurchaseHistory::insertGetId([
            'user_id'        => $userId,
            'purchase_date'  => now()->toDateString(),
            'price'          => $amount,
            'credits'        => $credits,
            'details'        => $detail,
            'payment_type'   => $type,
            'error_response' => $error_response,
            'status'         => $status,
            'created_at'     => now(),
        ]);

        try {


            $planHistory  = PlanHistory::where('user_id', $userId)->latest()->first();

            if (!$planHistory) {
                Log::info("No previous purchase found for user {$userId}, skipping auto-pay.");
                return $debitTransactionId;
            }

            //  Get plan and user details

            $isTopup      = $planHistory->is_topup ?? 0;
            $user         = User::find($userId);
            $remaining    = $user->total_credit;

            $autopay_credit_percent = CustomHelper::setting_value('autopay_credit_percent') ?? 8;
            $threshold    = ($planHistory->credits * $autopay_credit_percent) / 100;

            //  Check auto-pay eligibility
            if ($remaining > $threshold || $isTopup != 1 || $type != 1) {
                Log::info("Auto-pay skipped for user {$userId}. Remaining: {$remaining}, Threshold: {$threshold}, is_topup: {$isTopup}, type: {$type}");
                return $debitTransactionId;
            }

            Stripe::setApiKey(CustomHelper::setting_value('stripe_secret'));
            $cards = UserCardDetail::where('user_id', $userId)->orderByDesc('is_primary')->get();

            if ($cards->isEmpty()) {
                PurchaseHistory::where('id', $debitTransactionId)->update([
                    'status' => 0,
                    'error_response' => 'No saved cards found for user.',
                ]);
                return $debitTransactionId;
            }

            $paymentSuccess = false;
            $lastError = '';
            $skipFirstCard = true;

            foreach ($cards as $index => $card) {

                // if ($skipFirstCard && $index === 0) {
                //     Log::info("Skipping first card temporarily for user {$userId}, card: {$card->stripe_card_id}");
                //     continue;
                // }
                try {

                    $planPrice = floatval(str_replace(',', '', $planHistory->price));
                    $amountInPence = (int) round($planPrice * 100);

                    $paymentIntent = PaymentIntent::create([
                        'amount'         => $amountInPence,
                        'currency'       => 'GBP',
                        'payment_method' => $card->stripe_card_id,
                        'confirm'        => true,
                        'off_session'    => true,
                        'customer'       => $user->stripe_customer_id ?? null,
                        'description'    => $detail,
                    ]);

                    if ($paymentIntent->status === 'succeeded') {
                        Log::info("Payment succeeded for user {$userId} using card {$card->stripe_card_id}");

                        $user->increment('total_credit', intval($planHistory->credits));

                        PlanHistory::create([
                            'user_id'      => $userId,
                            'is_topup'     => $isTopup,
                            'credits'      => $planHistory->credits,
                            'plan_name'    => $planHistory->plan_name,
                            'price'        => $planHistory->price,
                            'vat'          => $planHistory->vat,
                            'total_amount' => $planHistory->total_amount,
                            'purchase_type' => 'auto_topup',
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]);

                        $transactionId = PurchaseHistory::insertGetId([
                            'user_id'        => $userId,
                            'purchase_date'  => now()->toDateString(),
                            'price'          => $planHistory->total_amount,
                            'credits'        => $planHistory->credits,
                            'details'        => $planHistory->plan_name,
                            'payment_type'   => 0,
                            'error_response' => '',
                            'status'         => 1,
                            'created_at'     => now(),
                            'updated_at'   => now(),
                        ]);

                        // Create invoice
                        $invoiceNumber = "4152SX7I-" . $transactionId;
                        $userDetails   = UserDetail::where('user_id', $userId)->first();

                        $dataInv = [
                            'user_id'        => $userId,
                            'invoice_number' => $invoiceNumber,
                            'details'        => $planHistory->plan_name,
                            'period'         => 'One off charge',
                            'amount'         => $planHistory->price,
                            'vat'            => $planHistory->vat,
                            'total_amount'   => $planHistory->total_amount,
                            'created_at'     => now(),
                        ];

                        if ($userDetails?->billing_contact_name) {
                            $dataInv['name'] = $userDetails->billing_contact_name;
                            $dataInv['address'] = trim("{$userDetails->billing_address1}, {$userDetails->billing_address2}, {$userDetails->billing_city} - {$userDetails->billing_postcode}");
                            $dataInv['phone'] = $userDetails->billing_phone;
                        } else {
                            $dataInv['name'] = $user->name;
                            $dataInv['address'] = trim("{$user->apartment}, {$userDetails->address}, {$userDetails->city} - {$user->zipcode}");
                            $dataInv['phone'] = $user->phone;
                        }

                        $invId = Invoice::insertGetId($dataInv);

                        if ($invId) {
                            CustomHelper::runInBackground(function () use ($userId, $invId) {
                                ZohoEmails::sendPlanInvoiceEmail($userId, $invId);
                            });
                        }
                        $paymentSuccess = true;
                        break;
                    }
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    Log::error("Stripe Payment failed for user {$userId} using card {$card->stripe_card_id}: " . $e->getMessage());
                    $lastError = $e->getMessage();
                } catch (\Throwable $e) {
                    Log::error("Unexpected error for user {$userId} using card {$card->stripe_card_id}: " . $e->getMessage());
                    $lastError = $e->getMessage();
                }
            }


            if (!$paymentSuccess) {
                PurchaseHistory::insertGetId([
                    'user_id'        => $userId,
                    'purchase_date'  => now()->toDateString(),
                    'price'          => $amount,
                    'credits'        => $credits,
                    'details'        => $detail,
                    'payment_type'   => 0, // CREDIT attempt failed
                    'status'         => 2, // failed
                    'error_response' => 'Payment failed on all cards',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            return $debitTransactionId;
        } catch (\Throwable $e) {
            Log::error("Auto payment exception for user {$userId}: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());

            return $debitTransactionId;
        }
    }

    public static function getImagepath($type = 'dir')
    {
        $path = dirname(dirname(public_path())) . "/public";
        if ($type == "url") {
            $path = env('APP_URL') . "/public";
        }
        return $path;
    }

    public static function displayImage($image, $path = "uploads", $aType = "")
    {

        $imagePath = 'default_images/profile.png';
        $image_path = 'images/' . $path . '/' . $image;

        $localPath = storage_path('app/public/' . $image_path);

        if ($image && File::exists($localPath)) {
            $imageUrl = url('storage/app/public/' . $image_path);
            // $imageUrl = Storage::disk('public')->url($image_path);
        } else {
            $imageUrl = URL::asset($imagePath);
        }

        return $imageUrl;
    }


    public static function fileUpload($image, $destinationFolder = '', $chkext = true)
    {
        $imageArray = array("png", "jpg", "jpeg", "gif", "bmp", "svg");
        $imagename = "profile.png";
        if ($image) {
            $imageext = $image->extension();
            $imgname = $image->getClientOriginalName();

            if (!in_array($imageext, $imageArray) && $chkext) {
                return "";
            }
            $mimeType = $image->getMimeType();
            if (!in_array($mimeType, ['image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/bmp', 'image/svg+xml'])) {
                return "";
            }
            $imagename = uniqid() . '_' . time() . '.' . $imageext;

            $folderPath = 'images/' . $destinationFolder;
            $image->storeAs($folderPath, $imagename, 'public');
        }
        return  $imagename;
    }

    public static function accfileUpload($image, $destinationFolder = '', $chkext = true)
    {
        $imageArray = array("png", "jpg", "jpeg", "gif", "bmp", "svg", "pdf");
        $imagename = "profile.png";
        if ($image) {
            $imageext = $image->extension();
            $imgname = $image->getClientOriginalName();

            if (!in_array($imageext, $imageArray) && $chkext) {
                return "";
            }
            $mimeType = $image->getMimeType();
            if (!in_array($mimeType, ['image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/bmp', 'image/svg+xml', 'application/pdf'])) {
                return "";
            }
            $imagename =  time() . '.' . $imageext;

            $folderPath = 'images/' . $destinationFolder;
            $image->storeAs($folderPath, $imagename, 'public');
        }
        return  $imagename;
    }

    public static function sendEmail($config = array())
    {
        $response = false;
        try {
            $defaults = array_merge([
                'sendAs' => 'html',
                'template' => 'send',
                'from' => 'info@localists.zuzucodes.com'
            ], $config);

            // Validate required keys
            if (empty($defaults['to']) || empty($defaults['subject']) || empty($defaults['body'])) {
                throw new \Exception("Required mail fields missing.");
            }

            $body = $defaults['body'];

            Mail::send('emails.' . $defaults['template'], [
                'title' => $defaults['title'] ?? null,
                'link' => $defaults['link'] ?? null,
                'subject' => $defaults['subject'] ?? null,
                'body' => $body
            ], function ($message) use ($defaults) {
                $message->from($defaults['from']);
                $message->to($defaults['to']);
                $message->subject($defaults['subject']);
            });
            $response = true;
        } catch (\Exception $e) {

            \Log::error('Mail sending failed: ' . $e->getMessage());
        }

        return $response;
    }

    public function sendNotification(Request $request)
    {
        $userId = $request->user_id;
        $message = $request->message;

        event(new NewNotificationEvent($message, $userId));

        return response()->json(['sent' => true]);
    }

    // public static function sendEmail($config = array())
    // {
    //     $mailDriver = strtolower(config("mail.driver"));
    //     $response = false;

    //     try {
    //         $defaults = array_merge([
    //             'sendAs'   => 'html',
    //             'template' => 'send',
    //             'body'     => '',
    //             'from'     => 'info@localists.com',
    //             'to'       => '',
    //             'subject'  => '',
    //             'receiver' => '',
    //             'title'    => '',
    //             'link'     => '',
    //             'extra'    => [],
    //         ], $config);

    //         // Prepare data to pass into the email view
    //         $emailData = [
    //             'body'    => $defaults['body'],
    //             'receiver'=> $defaults['receiver']
    //         ];

    //         Mail::send('emails.' . $defaults['template'], $emailData, function ($message) use ($defaults) {
    //             $message->from($defaults['from']);
    //             $message->to($defaults['to']);
    //             $message->subject($defaults['subject']);
    //         });

    //         $response = true;
    //     } catch (\Exception $e) {
    //         // Log the error if needed: \Log::error($e->getMessage());
    //     }

    //     return $response;
    // }
    // public static function sendEmail($config = array())
    // {
    //     $mailDriver = strtolower(config("mail.driver"));
    //     $response = false;
    //     try {
    //         $defaults = array_merge(array('sendAs' => 'html', 'template' => 'send', 'body' => 'Thankyou for registration', 'from' => 'ankit@zuzucodes.com'), $config);
    //         $body = $defaults['body'];
    //         Mail::send('emails.' . $defaults['template'], ['title' => @$defaults['title'], 'link' => @$defaults['link'], 'body' => $body, 'extra' => (isset($defaults['extra']) ? $defaults['extra'] : [])], function ($message) use ($defaults) {
    //             $message->from($defaults['from']);
    //             $message->to($defaults['to']);
    //             $message->subject($defaults['subject']);
    //         });
    //         $response = true;
    //     } catch (Exception $e) {
    //     }
    //     return $response;
    // }

    public static function pp($data = '', $die = TRUE)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';

        if ($die) die;
    }
}
