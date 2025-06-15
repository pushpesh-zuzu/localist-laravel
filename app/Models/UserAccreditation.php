<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccreditation extends Model
{
    protected $fillable = ['user_id','is_accreditations','name','image'];

    public function users()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
}
