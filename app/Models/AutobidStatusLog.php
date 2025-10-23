<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutobidStatusLog extends Model
{
    use SoftDeletes;
    protected $table = "autobid_status_logs";
    protected $fillable = ['user_id','action'];

    
}
