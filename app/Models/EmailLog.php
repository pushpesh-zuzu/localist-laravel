<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmailLog extends Model
{
    use SoftDeletes;
    protected $fillable = ['user_id','from_email','to_email','message_id','subject','content','zoho_url','response'];
}
