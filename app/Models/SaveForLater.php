<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaveForLater extends Model
{
   protected $fillable = ['seller_id','user_id','lead_id'];
}
