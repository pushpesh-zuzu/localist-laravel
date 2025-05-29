<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
     protected $fillable = ['page_type',
                            'page_title',
                            'page_menu',
                            'category_id',
                            'slug',
                            'title_desc',
                            'page_details',
                            'banner_image',
                            'og_image',
                            'seo_title',
                            'seo_keyword',
                            'seo_description',
                            'page_script',
                            'lower_section_title',
                            'lower_section_desc',
                            'status'
                        ];

    public function faqs()
    {
        return $this->hasMany('App\Models\Faq','page_id');
    }   
    
    public function category()
    {
        return $this->belongsTo('App\Models\Category','category_id');
    } 

   
    
}
