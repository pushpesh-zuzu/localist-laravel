<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AutobidStatusLog;
use Illuminate\Support\Carbon;
use App\Helpers\Zoho\ZohoQuoteCustomers;
use App\Helpers\Zoho\ZohoLeadBuyers;
use App\Helpers\Zoho\ZohoEmails;

use Illuminate\Support\Facades\Log;

class AbandonedUser extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'abandoned_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_reg_number',
        'business_profile_name',
        'company_name',
        'company_website',
        'is_company_website',
        'company_size',
        'company_sales_team',
        'company_logo',
        'company_email',
        'company_phone',
        'company_location',
        'company_locaion_reason',
        'company_total_years',
        'about_company',
        'phone',
        'address',
        'dob',
        'gender',
        'profile_image',
        'country_code',
        'new_jobs',
        'social_media',
        'suite',
        'total_credit',
        'status',
        'city',
        'state',
        'country',
        'postcode_new',
        'is_online',
        'sms_notification_no',
        'primary_category',
        'apartment',
        'is_zipcode',
        'zipcode',
        'user_type',
        'active_status',
        'form_status',
        'remember_token',
        'zoho_record_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    


}
