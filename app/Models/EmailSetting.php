<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EmailSetting extends Model
{
    protected $fillable = ['setting_name','setting_value'];
}
