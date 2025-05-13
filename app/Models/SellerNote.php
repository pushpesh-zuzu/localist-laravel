<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerNote extends Model
{
    protected $fillable = ['lead_id','seller_id','buyer_id','notes'];
}
