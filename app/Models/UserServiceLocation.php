<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserServiceLocation extends Model
{
    protected $fillable = ['user_id', 'service_id','user_service_id','miles','postcode','nation_wide','city','travel_time','travel_by','type','is_default','status'];


    public static function createUserServiceLocation($aLocations)
    {
           // $aLocation = UserServiceLocation::create($aLocations);

           $aLocation = UserServiceLocation::updateOrCreate(
                ['user_id' => $aLocations['user_id'], 'service_id' => $aLocations['service_id'],'user_service_id' => $aLocations['user_service_id'], 'postcode' => $aLocations['postcode'], 'type' =>$aLocations['type'], 'city' =>$aLocations['city']], // Search criteria
                ['updated_at' => now(), 'miles' => $aLocations['miles'],'nation_wide' => $aLocations['nation_wide']] // Fields to update or insert
            );

            return $aLocation;
    }
    
    public function userServices()
    {
        return $this->hasMany(Category::class,'id','service_id');
    }
}
