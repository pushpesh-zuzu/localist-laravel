<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LoginHistory extends Model
{
   //  use SoftDeletes;
    protected $fillable = ['user_id','ip','user_agent','login_at'];
    public $timestamps = false;

    public function user() {
    return $this->belongsTo(User::class, 'user_id');
}
}
