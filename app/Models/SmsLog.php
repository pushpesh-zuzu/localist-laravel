<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'quote_id',
        'to_number',
        'message_id',
        'message',
        'status',
        'otp',
        'raw_response'
    ];

    protected $casts = [
        'raw_response' => 'array'
    ];
}
