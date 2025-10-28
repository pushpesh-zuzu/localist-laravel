<?php

namespace App\Models;

use App\Helpers\Zoho\ZohoSocialMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\SoftDeletes;
class UserDetail extends Model
{
   //  use SoftDeletes;
    protected $fillable = [
                            'is_autobid',
                            'autobid_pause',
                            'user_id',
                            'company_photos',
                            'has_youtube_link',
                            'company_youtube_link',
                            'has_fb_link',
                            'fb_link',
                            'has_twitter_link',
                            'twitter_link',
                            'has_tiktok_link',
                            'tiktok_link',
                            'has_insta_link',
                            'insta_link',
                            'has_linkedin_link',
                            'linkedin_link',
                            'has_extra_links',
                            'extra_links',
                            'has_accreditations',
                            'billing_contact_name',
                            'billing_address1',
                            'billing_address2',
                            'billing_city',
                            'billing_postcode',
                            'billing_phone',
                            'billing_vat_register'
                        ];
    public function users()
    {
        return $this->belongsTo(User::class);
    }

    // protected static function booted()
    // {

    //     static::created(function ($socailmedia) {

    //         self::handleZohoIntegration($socailmedia);
    //     });

    //     static::updated(function ($socailmedia) {

    //         self::handleZohoIntegration($socailmedia);
    //     });
    // }

    // protected static function handleZohoIntegration($socailmedia)
    // {
    //     try {
    //         $user = $socailmedia->user_id; // assuming relation exists

    //         if ($user) {
    //             //app(ZohoSocialMedia::class)->integrateSocialLinks($user);

    //         }
    //     } catch (\Throwable $e) {
    //         Log::error('Zoho social media integration failed', [
    //             'user_id' => $socailmedia->user_id ?? null,
    //             'social_media_id' => $socailmedia->id,
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }

}
