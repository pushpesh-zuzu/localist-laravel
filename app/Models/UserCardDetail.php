<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCardDetail extends Model
{
    protected $fillable = ['user_id','card_number','expiry_date','cvc','is_primary','stripe_card_id'];
}
