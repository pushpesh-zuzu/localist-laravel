<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaveForLater extends Model
{
  // use SoftDeletes;
   protected $fillable = ['seller_id','user_id','lead_id'];
}
