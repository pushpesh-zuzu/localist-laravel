<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadPrefrence extends Model
{
    protected $fillable = ['user_id','service_id','question_id','answers'];
    
    public function serquestions()
    {
        return $this->belongsTo(ServiceQuestion::class, 'question_id');
    }

    public function question()
    {
        return $this->belongsTo(ServiceQuestion::class, 'question_id', 'id');
    }
    
}
