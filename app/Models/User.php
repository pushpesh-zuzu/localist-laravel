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

class User extends Authenticatable
{
    use SoftDeletes; // Enable soft deletes
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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

    public function details()
    {
        return $this->hasOne(UserDetail::class, 'user_id', 'id');
    }

    public function accreditations()
    {
        return $this->hasMany(UserAccreditation::class,'id','user_id');
    }

    public function services()
    {
        return $this->hasMany(UserService::class, 'user_id', 'id');
    }

    public function hiredLeads()
    {
        return $this->hasMany(LeadRequest::class, 'hired_by');
    }

    public function leadRequests()
    {
        return $this->hasMany(LeadRequest::class, 'customer_id', 'id');
    }


    public function responseTime()
    {
        return $this->hasOne(UserResponseTime::class, 'seller_id', 'id');
    }

    public function serviceLocations()
    {
        return $this->hasMany(UserServiceLocation::class, 'user_id', 'id');
    }

    public function profileQAs()
    {
        return $this->hasMany(ProfileQA::class, 'user_id', 'id');
    }

    public function getProfileCompletionPercentage(): int
    {
        // Fields directly in `users` table
        $userFields = ['company_name', 'company_logo', 'name', 'profile_image', 'company_email',
            'company_phone', 'company_website', 'company_location', 'company_locaion_reason', 'company_size',
            'company_total_years', 'about_company'];

        // Fields in `user_details` table
        $detailsFields = ['company_photos', 'company_youtube_link', 'fb_link', 'twitter_link', 'tiktok_link',
            'insta_link', 'linkedin_link', 'extra_links'];

        // Each Q&A counts individually (question's count)
        $qaSlots = 4;

        // Total field count
        $totalFields = count($userFields) + count($detailsFields) + 1 + $qaSlots;
        $completed = 0;

        // User fields
        foreach ($userFields as $field) {
            if (!empty($this->{$field})) {
                $completed++;
            }
        }

        // User detail fields
        if ($this->details) {
            foreach ($detailsFields as $field) {
                if (!empty($this->details->{$field})) {
                    $completed++;
                }
            }
        }

        // Accreditations count as 1 if at least one exists
        if ($this->accreditations()->exists()) {
            $completed++;
        }

        // Profile Q&A
        $qaCount = min($this->profileQAs()->count(), $qaSlots);
        $completed += $qaCount;

        return (int) round(($completed / $totalFields) * 100);
    }

    public function primaryCategory()
    {
        return $this->belongsTo(Category::class, 'primary_category');
    }

    public function userDetail()
    {
        return $this->hasOne(UserDetail::class, 'user_id', 'id');
    }

    protected static function booted()
    {

        static::created(function ($user) {
            self::handleZohoIntegration($user);
        });

        static::updated(function ($user) {
            self::handleZohoIntegration($user);
        });
    }
    protected static function handleZohoIntegration($user)
    {
        try {
            if ($user->user_type == 1) {

                return app(ZohoLeadBuyers::class)->integrateZohoLeadBuyers($user->id);

            } elseif ($user->user_type == 2) {
                return app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($user);
            }
        } catch (\Throwable $e) {
            Log::error('Zoho user integration failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class, 'user_id', 'id');
    }


}
