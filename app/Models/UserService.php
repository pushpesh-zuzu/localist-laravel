<?php

namespace App\Models;

use App\Helpers\Zoho\ZohoService as ZohoZohoService;
use App\Helpers\Zoho\ZohoServiceLocations;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class UserService extends Model
{
    //use SoftDeletes; // Enable soft deletes
    use HasSlug;

    protected $fillable = ['user_id', 'service_id','price','auto_bid','is_default','status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceLocations()
    {
        return $this->hasMany(UserServiceLocation::class, 'service_id', 'service_id');
    }
    public function userServices()
    {
        return $this->hasMany(Category::class,'id','service_id');
    }

    public static function createUserService($user_id, $service_id, $auto_bid=0)
    {
        $aServices['service_id'] = $service_id;
        $aServices['user_id'] = $user_id;
        // $aServices['auto_bid'] = $auto_bid;
        $service = UserService::create($aServices);
        return $service;
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'service_id');
    }

    public function locations()
    {
        return $this->hasMany(UserServiceLocation::class, 'user_service_id');
    }

    // protected static function booted()
    // {


    //     static::created(function ($service) {

    //         self::handleZohoIntegration($service);
    //     });

    //     static::updated(function ($service) {

    //         self::handleZohoIntegration($service);
    //     });

    //     static::deleted(function ($service) {
    //         self::handleZohoDeletion($service);
    //     });
    // }

    // protected static function handleZohoIntegration($service)
    // {
    //     try {
    //         $user = $service->user_id; // assuming relation exists
    //         $serviceId = $service->id;
    //         if ($user) {
    //             //app(ZohoZohoService::class)->integrateService($user, $serviceId);

    //         }
    //     } catch (\Throwable $e) {
    //         Log::error('Zoho service  integration failed', [
    //             'user_id' => $location->user_id ?? null,
    //             'service_id' => $service->id,
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }

    // protected static function handleZohoDeletion($service)
    // {
    //     try {
    //         //app(ZohoZohoService::class)->deleteBuyerService($service->id);
    //     } catch (\Throwable $e) {
    //         Log::error('Zoho Service deletion failed', [
    //             'service_id' => $service->id,
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }

}
