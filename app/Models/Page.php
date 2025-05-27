<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
     protected $fillable = ['page_title',
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

    
}
