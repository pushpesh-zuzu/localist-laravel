<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FacebookPage extends Model {
    protected $fillable = ['page_id','seller_id','name','access_token','token_expires_at','meta'];
    protected $casts = [
        'meta' => 'array',
        'token_expires_at' => 'datetime',
    ];
}
