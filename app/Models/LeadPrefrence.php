<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadPrefrence extends Model
{
    protected $fillable = ['user_id','service_id','question_id','answers'];
}
