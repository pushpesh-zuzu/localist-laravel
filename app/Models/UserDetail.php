<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $fillable = [
                            'is_autobid',
                            'user_id',
                            'company_photos',
                            'user_emails_reviews',
                            'is_youtube_video',
                            'company_youtube_link',
                            'is_fb',
                            'fb_link',
                            'is_twitter',
                            'twitter_link',
                            'is_link_desc',
                            'link_desc',
                            'is_accreditations',
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
