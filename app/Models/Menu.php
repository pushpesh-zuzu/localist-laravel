<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Menu extends Model
{
   protected $fillable = ['menu_pageid','menu_name','menu_parent','menu_customlink'];
   
    public function pages()
    {
        return $this->belongsTo('App\Models\Page', 'menu_pageid');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\Menu', 'menu_parent');
    }
    public static function boot()
    {
        parent::boot();
        static::created(function ($product) {
            $product->menu_slug = $product->generateSlug($product->menu_name);
            $product->save();
        });
    }
    public function generateSlug($name)
    {
        if (static::whereMenuSlug($slug = Str::slug($name))->exists()) {
            $max = static::whereMenuName($name)->latest('menu_id')->skip(1)->value('menu_slug');
            if (isset($max[-1]) && is_numeric($max[-1])) {
                return preg_replace_callback('/(\d+)$/', function($mathces) {
                    return $mathces[1] + 1;
                }, $max);
            }
             return "{$slug}-1";
        }else{
            return $slug;
        }
    }
    
  
}
