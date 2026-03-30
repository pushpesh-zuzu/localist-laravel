<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class D7LeadSupplier extends Model
{
    use HasFactory;

    protected $table = 'd7_lead_suppliers';

    protected $fillable = [
        // Basic info
        'name',
        'phone',
        'website',
        'email',
        'category',

        // Address
        'address1',
        'address2',
        'region',
        'zip',
        'country',

        // Google
        'google_stars',
        'google_review_count',
        'google_rank',

        // Yelp
        'yelp_stars',
        'yelp_review_count',

        // Facebook
        'facebook_stars',
        'facebook_review_count',
        'facebook_followers',
        'facebook_url',

        // Instagram
        'instagram_followers',
        'instagram_follows',
        'instagram_is_business',
        'instagram_media_count',
        'instagram_url',

        // Other social
        'twitter_url',
        'linkedin_url',

        // Tech / tracking
        'facebook_pixel',
        'schema_enabled',
        'google_remarketing',
        'google_analytics',
        'linkedin_analytics',
        'uses_wordpress',
        'uses_shopify',
        'mobile_friendly',

        // CRM / Misc
        'lead_service',
        'mail_sent',
        'is_subscribed',
        'email_status',
        'bounce_reason',
        'message_id',
        'zoho_record_id',
        'zoho_account_record_id'
    ];

    protected $casts = [
        // Google
        'google_stars' => 'float',
        'google_review_count' => 'integer',
        'google_rank' => 'integer',

        // Yelp
        'yelp_stars' => 'float',
        'yelp_review_count' => 'integer',

        // Facebook
        'facebook_stars' => 'float',
        'facebook_review_count' => 'integer',
        'facebook_followers' => 'integer',

        // Instagram
        'instagram_followers' => 'integer',
        'instagram_follows' => 'integer',
        'instagram_media_count' => 'integer',
        'instagram_is_business' => 'boolean',

        // Tracking / Tech
        'facebook_pixel' => 'boolean',
        'schema_enabled' => 'boolean',
        'google_remarketing' => 'boolean',
        'google_analytics' => 'boolean',
        'linkedin_analytics' => 'boolean',
        'uses_wordpress' => 'boolean',
        'uses_shopify' => 'boolean',
        'mobile_friendly' => 'boolean',

        // CRM / Misc
        'mail_sent' => 'boolean',
        'is_subscribed' => 'boolean',
    ];
}