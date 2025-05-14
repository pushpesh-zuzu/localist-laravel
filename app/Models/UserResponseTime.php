<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserResponseTime extends Model
{
    protected $fillable = ['seller_id','minimum_duration','average'];
}
