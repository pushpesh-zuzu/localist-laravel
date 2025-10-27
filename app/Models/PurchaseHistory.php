<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseHistory extends Model
{
  //  use SoftDeletes;
    protected $fillable = ['user_id','plan_id','purchase_date','price','credits','response','status','details','payment_type'];
    
    public function plans()
    {
        return $this->belongsTo(Plan::class, 'plan_id','id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }
    
    
}
