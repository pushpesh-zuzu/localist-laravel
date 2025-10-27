<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class UserCardDetail extends Model
{
  //  use SoftDeletes;
    protected $fillable = ['user_id','card_number','expiry_date','cvc','is_primary','stripe_card_id'];
}
