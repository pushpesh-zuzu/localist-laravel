<?php

namespace App\Models;

use App\Helpers\Zoho\ZohoSocialMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class UserDetail extends Model
{
    protected $fillable = [
                            'is_autobid',
                            'autobid_pause',
                            'user_id',
                            'company_photos',
                            'company_youtube_link',
                            'fb_link',
                            'twitter_link',
                            'tiktok_link',
                            'insta_link',
                            'linkedin_link',
                            'extra_links',
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

    protected static function booted()
    {

        static::created(function ($socailmedia) {

            self::handleZohoIntegration($socailmedia);
        });

        static::updated(function ($socailmedia) {

            self::handleZohoIntegration($socailmedia);
        });
    }

    protected static function handleZohoIntegration($socailmedia)
    {
        try {
            $user = $socailmedia->user_id; // assuming relation exists

            if ($user) {
                app(ZohoSocialMedia::class)->integrateSocialLinks($user);

            }
        } catch (\Throwable $e) {
            Log::error('Zoho social media integration failed', [
                'user_id' => $socailmedia->user_id ?? null,
                'social_media_id' => $socailmedia->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

}
