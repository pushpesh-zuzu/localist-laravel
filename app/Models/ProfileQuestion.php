<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileQuestion extends Model
{
    use SoftDeletes; // Enable soft deletes
    protected $fillable = ['questions'];
}
