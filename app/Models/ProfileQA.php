<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileQA extends Model
{
    protected $fillable = ['questions','answer','user_id'];
}
