<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserHiringHistory extends Model
{
   protected $fillable = ['lead_id','user_id','name'];
}
