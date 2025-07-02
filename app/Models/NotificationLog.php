<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $table = 'notification_logs';

    protected $fillable = [
        'user_id',
        'noti_name',
        'title',
        'message',
        'status',
        'type',
        'lead_id'
    ];
}
