<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class Otp extends Model
{
    protected $table = 'otps';

    protected $fillable = ['phone_number','otp'];
    
}
