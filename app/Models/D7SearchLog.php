<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class D7SearchLog extends Model
{
    protected $fillable = [
        'search_id', 'keyword', 'city', 'country', 'status', 'error','lead_id'
    ];
}
