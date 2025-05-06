<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserResponseTime extends Model
{
    protected $fillable = ['lead_id','seller_id','buyer_id','is_clicked_whatsapp','is_clicked_email','is_clicked_mobile','is_clicked_sms','last_seen','button_clicked_time'];
}
