<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceQuestion extends Model
{
    use SoftDeletes; // Enable soft deletes
    protected $fillable = ['category', 'questions','answer','seo_title','seo_description'];

    public function categories()
    {
        return $this->belongsTo(Category::class, 'category','id');
    }

}
