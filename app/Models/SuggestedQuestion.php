<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuggestedQuestion extends Model
{
  //  use SoftDeletes;
   protected $fillable = ['user_id','service_id','question_id','answer_type','question','type','answer','reason'];

   public function services()
    {
        return $this->belongsTo(Category::class, 'service_id','id');
    }
}
