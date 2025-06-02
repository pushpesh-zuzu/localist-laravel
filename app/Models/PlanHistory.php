<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanHistory extends Model
{
    protected $table = 'plan_histories';
    protected $fillable = ['user_id','plan_name','credits','is_topup','price','vat','total_amount'];
    
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }
    
    
}
