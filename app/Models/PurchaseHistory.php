<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseHistory extends Model
{
    protected $fillable = ['user_id','plan_id','purchase_date','price','credits','response','status'];
    
    public function plans()
    {
        return $this->belongsTo(Plan::class, 'plan_id','id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }
    
    
}
