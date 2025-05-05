<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserResponseTime extends Model
{
    protected $fillable = ['lead_id','seller_id','buyer_id','status','clicked_name'];
}
