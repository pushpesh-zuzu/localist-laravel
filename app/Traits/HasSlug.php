<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug()
    {
        // static::saving(function ($model) {
        //     if (isset($model->name) && empty($model->slug)) {
        //         $model->slug = Str::slug($model->name, '-');
        //     }
        // });

        static::saving(function ($model) {
            if (isset($model->name)) {
                // Only update slug if the name is changed or slug is empty
                if (empty($model->slug) || $model->isDirty('name')) {
                    $model->slug = Str::slug($model->name, '-');
                }
            }
        });
    }
}
