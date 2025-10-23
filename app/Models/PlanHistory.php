<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PlanHistory extends Model
{
     use SoftDeletes;
    protected $table = 'plan_histories';
    protected $fillable = ['user_id','plan_name','credits','is_topup','price','vat','total_amount'];
    
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }
    
    
}
