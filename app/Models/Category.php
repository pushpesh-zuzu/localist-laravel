<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    protected $fillable = ['name', 'homepage_display_name' , 'description','parent_id','banner_image','breadcrumb_title','category_icon','seo_title','seo_description','is_home','is_popular', 'show_in_search','status'];

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

    public function subsector(){
        return $this->hasMany('App\Models\Category','parent_id','id');
    }

    // recursive, loads all descendants with products
    public function subsectors()
    {
        return $this->subsector()->with('subsectors');
    }

}
