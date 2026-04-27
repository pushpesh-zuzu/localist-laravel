<?php

namespace App\Models;

use App\Helpers\Zoho\ZohoServiceLocations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserServiceLocation extends Model
{
    //  use SoftDeletes;
    protected $fillable = ['user_id', 'service_id', 'user_service_id', 'miles', 'postcode', 'nation_wide', 'city', 'travel_time', 'travel_by', 'type', 'is_default', 'status', 'coordinates'];


    public static function createUserServiceLocation($aLocations)
    {
        // $aLocation = UserServiceLocation::create($aLocations);

        $aLocation = UserServiceLocation::updateOrCreate(
            ['user_id' => $aLocations['user_id'], 'service_id' => $aLocations['service_id'], 'user_service_id' => $aLocations['user_service_id'], 'postcode' => $aLocations['postcode'], 'type' => $aLocations['type'], 'city' => $aLocations['city']], // Search criteria
            ['updated_at' => now(), 'miles' => $aLocations['miles'], 'nation_wide' => $aLocations['nation_wide'], 'coordinates' => $aLocations['coordinates']] // Fields to update or insert
        );

        return $aLocation;
    }

    public function userServices()
    {
        return $this->hasMany(Category::class, 'id', 'service_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceCategory()
    {
        return $this->belongsTo(Category::class, 'service_id', 'id');
    }
    // protected static function booted()
    // {

    //     static::created(function ($location) {

    //         self::handleZohoIntegration($location);
    //     });

    //     static::updated(function ($location) {

    //         self::handleZohoIntegration($location);
    //     });
    // }

    // protected static function handleZohoIntegration($location)
    // {
    //     try {
    //         $user = $location->user_id; // assuming relation exists
    //         $locationId = $location->id;
    //         if ($user) {
    //             app(ZohoServiceLocations::class)->integrateServiceLocations($user, $locationId);

    //         }
    //     } catch (\Throwable $e) {
    //         Log::error('Zoho service location integration failed', [
    //             'user_id' => $location->user_id ?? null,
    //             'location_id' => $location->id,
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }
}
