<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['lead_id','from_user_id','to_user_id','activity_name','contact_type','duration','duration_minutes'];
}
