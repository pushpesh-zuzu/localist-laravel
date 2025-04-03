<?php

namespace App\Helpers;
use Illuminate\Support\Facades\{DB, Log, URL, Auth, File, Mail, Session};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class CustomHelper
{
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
}
