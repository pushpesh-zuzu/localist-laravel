<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellerNote extends Model
{
    use SoftDeletes;
    protected $fillable = ['lead_id','seller_id','buyer_id','notes'];
}
