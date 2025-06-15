<?php

namespace App\Helpers;
use Illuminate\Support\Facades\{DB, Log, URL, Auth, File, Mail, Session, Http};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Events\NewNotificationEvent;
use App\Models\PurchaseHistory;
use App\Models\Setting;
use App\Models\Postcode;

class CustomHelper
{
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

    public static function setting_value($key, $defaultValue=''){
        $val = Setting::where('setting_name',$key)->value('setting_value');
        $rel = !empty($val) ? $val : $defaultValue;
        return $rel;
    }

    public static function createTrasactionLog($userId, $amount, $credits, $detail, $status=1, $type=0, $error_response=''){
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
