<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AutobidStatusLog extends Model
{
    protected $table = "autobid_status_logs";
    protected $fillable = ['user_id','action'];
}
