<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes; // Enable soft deletes
    use HasSlug;

    protected $fillable = ['name', 'slug','description','parent_id','banner_image','banner_title','category_icon','seo_title','seo_description','is_home','status'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function userServices()
    {
        return $this->hasMany(UserService::class, 'service_id');
    }

    public function leadRequests()
    {
        return $this->hasMany(\App\Models\LeadRequest::class, 'service_id');
    }

    public function serviceQuestions()
    {
        return $this->hasMany(ServiceQuestion::class, 'category', 'id');
    }

}
