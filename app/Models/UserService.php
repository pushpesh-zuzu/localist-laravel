<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserService extends Model
{
    //use SoftDeletes; // Enable soft deletes
    use HasSlug;

    protected $fillable = ['user_id', 'service_id','price','auto_bid','is_default','status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userServices()
    {
        return $this->hasMany(Category::class,'id','service_id');
    }

    public static function createUserService($user_id, $service_id, $auto_bid)
    {
        $aServices['service_id'] = $service_id;
        $aServices['user_id'] = $user_id;
        $aServices['auto_bid'] = $auto_bid;
        $service = UserService::create($aServices);
        return $service;
    }

}
