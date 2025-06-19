<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
