<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationSetting extends Model
{
    use SoftDeletes; // Enable soft deletes
    use HasSlug;

    protected $fillable = ['user_id', 'noti_name','noti_value','user_type','noti_type'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id','id')->select('id','name','email');
    }

}
