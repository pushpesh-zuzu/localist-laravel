<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniqueVisitor extends Model
{
   protected $fillable = ['seller_id','buyer_id','ip_address','date','visitors_count','lead_id'];
}
