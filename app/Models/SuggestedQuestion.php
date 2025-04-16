<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuggestedQuestion extends Model
{
   protected $fillable = ['user_id','service_id','question_id','answer_type','question','type','answer','reason'];


}
