<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class D7LeadSupplier extends Model
{
    use HasFactory;

    protected $table = 'd7_lead_suppliers';

    protected $fillable = [
        'name',
        'phone',
        'website',
        'email',
        'category',

        'address1',
        'address2',
        'region',
        'zip',
        'country',

        'google_stars',
        'google_review_count',

        'facebook_followers',
        'instagram_followers',
        'instagram_follows',
        'instagram_is_business',
        'instagram_media_count',

        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'lead_service',
        'mail_sent',
        'is_subscribed'
    ];

    protected $casts = [
        'google_stars' => 'float',
        'google_review_count' => 'integer',

        'facebook_followers' => 'integer',
        'instagram_followers' => 'integer',
        'instagram_follows' => 'integer',
        'instagram_media_count' => 'integer',

        'instagram_is_business' => 'boolean',
    ];
}
