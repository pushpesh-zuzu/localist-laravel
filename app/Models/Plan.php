<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes; // Enable soft deletes
    use HasSlug;

    protected $fillable = ['category_id','name', 'slug','description','price','no_of_leads','plan_type','status'];
    

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id','id')->select('id','name');
    }

}
