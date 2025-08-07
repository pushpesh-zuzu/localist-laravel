<?php

namespace App\Helpers;
use Illuminate\Support\Facades\{DB, Log, URL, Auth, File, Mail, Session, Http};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Events\NewNotificationEvent;
use App\Helpers\Zoho\ZohoFinance;
use App\Models\PurchaseHistory;
use App\Models\NotificationSetting;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Postcode;
use Illuminate\Support\Carbon;
use App\Jobs\IntegrateZohoPurchaseHistory;

class CustomHelper
{

    public static function createSectorsRecursive($data, $index = '1', $space = 40){
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
                        <td>
                            <a href="' . route('sectors.edit', $d['id']) . '" title="Edit"><i class="fas fa-edit"></i></a> &nbsp;

                            <a href="javascript:void(0);" onclick="event.preventDefault(); if(confirm(\'Are you sure to delete?\')) document.getElementById(\'' . $deleteFormId . '\').submit();" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>

                            <form id="' . $deleteFormId . '" action="' . route('sectors.destroy', $d['id']) . '" method="POST" style="display: none;">
                                ' . method_field('DELETE') . csrf_field() . '
                            </form>
                        </td>
                    </tr>';

                // Recursive call
                if (count($d['subsectors']) > 0) {
                    self::createSectorsRecursive($d, $currentIndex, 2 * $space);
                }

                $i++;
            }
        }
    }


    public static function logNotifications($userId, $leadId, $notiName, $title, $message, $checkExisting=false, $notiType='browser', $userType='buyer'){
        $insertLog = true;
        $isSettingOn=NotificationSetting::where('user_id',$userId)
            ->where('noti_name',$notiName)
            ->where('user_type',$userType)
            ->where('noti_type',$notiType)
            ->value('noti_value');

        //check whether setting is turned on or not
        if(!$isSettingOn){
            $insertLog = false;
        }

        // if checkExisting is true then check if the same lead exists or not
        if($checkExisting){
            $logExists = NotificationLog::where('user_id',$userId)
                ->where('lead_id',$leadId)
                ->where('noti_name',$notiName)
                ->where('user_type',$userType)
                ->where('noti_type',$notiType)
                ->value('id');
            //if notification exists then do not log the notification
            if($logExists){
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

    public static function formatTimeDuration(int $minutes): string {
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


    public static function numberToWords($number){
        $f = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number));
    }
    public static function getPostcodesWithinRadius($postcode, $radius = 0, $km=false){
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
            ->select('postcode', 'latitude', 'longitude',
                DB::raw("(
                    $val * acos(
                        cos(radians(?)) *
                        cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(latitude))
                    )
                ) AS distance"))
            ->addBinding($lat)
            ->addBinding($lng)
            ->addBinding($lat)
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get()->toArray();
        $pureArray = json_decode(json_encode($results), true);
        return $pureArray;
    }

    public static function getCoordinates($postcode){
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

    public static function update_setting_value($key, $value){
        if(!empty($value)){
            Setting::where('setting_name',$key)->update([
                'setting_value' => $value
            ]);
        }
    }

    public static function setting_value($key, $defaultValue=''){
        $val = Setting::where('setting_name',$key)->value('setting_value');
        $rel = !empty($val) ? $val : $defaultValue;
        return $rel;
    }

    public static function createTrasactionLog($userId, $amount, $credits, $detail, $status=1, $type=0, $error_response=''){

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

        //     register_shutdown_function(function () use ($userId, $id) {
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

    public static function getImagepath($type = 'dir')
    {
        $path = dirname(dirname(public_path()))."/public";
        if($type == "url")
        {
            $path = env('APP_URL')."/public";
        }
        return $path;
    }

    public static function displayImage($image,$path = "uploads", $aType = "")
    {

        $imagePath = 'default_images/profile.png';
        $image_path = 'images/' . $path.'/'.$image;

        $localPath = storage_path('app/public/' . $image_path);

        if ($image && File::exists($localPath)) {
            $imageUrl = url('storage/app/public/' . $image_path);
            // $imageUrl = Storage::disk('public')->url($image_path);
        } else {
            $imageUrl = URL::asset($imagePath);
        }

        return $imageUrl;
    }


    public static function fileUpload($image, $destinationFolder = '',$chkext = true)
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
            $imagename =  time() . '.' . $imageext;

            if(env('APP_ENV', config('app.env')) == 'local'){
                $folderPath = 'images/' . $destinationFolder;
                $image->storeAs($folderPath, $imagename, 'public');
            }else if(env('APP_ENV', config('app.env')) == 'production'){
                $imagename = 'profile.png';
            }
        }
        return  $imagename;
    }

    public static function accfileUpload($image, $destinationFolder = '',$chkext = true)
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

            if(env('APP_ENV', config('app.env')) == 'local'){
                $folderPath = 'images/' . $destinationFolder;
                $image->storeAs($folderPath, $imagename, 'public');
            }else if(env('APP_ENV', config('app.env')) == 'production'){
                $imagename = 'profile.png';
            }
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

            \Log::error('Mail sending failed: ' .$e->getMessage() );
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

    public static function pp($data='',$die=TRUE){
        echo '<pre>';
        print_r($data);
        echo '</pre>';

        if($die)die;
    }

}
