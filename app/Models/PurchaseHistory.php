<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseHistory extends Model
{
    protected $fillable = ['user_id','plan_id','purchase_date','price','credits','response','status'];
    
    
    
    
}
