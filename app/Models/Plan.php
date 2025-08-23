<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{

    protected $fillable = ['category_id','name','description','price','no_of_leads', 'no_of_responses','plan_type','status'];
    

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id','id')->select('id','name');
    }

}
