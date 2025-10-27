<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class UserAccreditation extends Model
{
    //use SoftDeletes;
    protected $fillable = ['user_id','is_accreditations','name','image'];

    public function users()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
}
