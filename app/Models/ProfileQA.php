<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileQA extends Model
{
  //  use SoftDeletes;
    protected $fillable = ['questions','answer','user_id'];
}
