<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UniqueVisitor extends Model
{
   use SoftDeletes;
   protected $fillable = ['seller_id','buyer_id','ip_address','date','visitors_count','lead_id'];
}
