<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallbackRequest extends Model
{
    use SoftDeletes; // Enable soft deletes

    protected $fillable = ['seller_id', 'service_id','city','name','email','phone'];

}
