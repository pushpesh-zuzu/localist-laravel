<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    protected $fillable = ['service_id', 'seller_id','buyer_id','lead_id','bid'];
}
