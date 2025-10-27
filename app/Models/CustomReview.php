<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CustomReview extends Model
{
  // use SoftDeletes;
   protected $table = 'custom_reviews';
   protected $fillable = ['user_id','review_platform','review_count','ratings'];
}
