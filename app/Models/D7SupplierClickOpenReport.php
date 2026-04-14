<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class D7SupplierClickOpenReport extends Model
{
    protected $table = 'd7_supplier_click_open_reports';

    protected $fillable = [
        'message_id',
        'supplier_email',
        'open_count',
        'click_count',
        'open_at',
        'click_at',
        'webhook_request_id',
    ];

    protected $casts = [
        'open_count'  => 'integer',
        'click_count' => 'integer',
        'open_at'     => 'datetime',
        'click_at'    => 'datetime',
    ];
}