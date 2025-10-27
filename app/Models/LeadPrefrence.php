<?php

namespace App\Models;

use App\Helpers\Zoho\ZohoQuestionAnswer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\SoftDeletes;
class LeadPrefrence extends Model
{
    // use SoftDeletes;
    protected $fillable = ['user_id','service_id','question_id','answers'];

    public function serquestions()
    {
        return $this->belongsTo(ServiceQuestion::class, 'question_id');
    }

    public function question()
    {
        return $this->belongsTo(ServiceQuestion::class, 'question_id', 'id');
    }



    //  protected static function booted()
    // {

    //     static::created(function ($question) {

    //         self::handleZohoIntegration($question);
    //     });

    //     static::updated(function ($question) {

    //         self::handleZohoIntegration($question);
    //     });
    // }


    // protected static function handleZohoIntegration($question)
    // {
    //     try {
    //         $user = $question->user_id;
    //         $questionId = $question->id;

    //         if ($user) {
    //             //app(ZohoQuestionAnswer::class)->integrateServiceQa($user,$questionId);
    //         }
    //     } catch (\Throwable $e) {
    //         Log::error('Zoho question answer integration failed', [
    //             'user_id' => $question->user_id ?? null,
    //             'question_id' => $question->id,
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }

}
