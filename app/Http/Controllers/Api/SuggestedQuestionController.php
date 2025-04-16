<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SuggestedQuestion;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;

class SuggestedQuestionController extends Controller
{
    public function addSuggestedQue(Request $request): JsonResponse
    {
        $user_id = $request->user_id;
        $aVals = $request->all();
        $questions = SuggestedQuestion::where('user_id',$user_id)
                                      ->where('service_id',$aVals['service_id'])
                                      ->where('question_id',$aVals['question_id'])
                                      ->first();
        if($aVals['type'] == 'add'){
            $questions = SuggestedQuestion::create([
                'user_id' => $user_id, //Required
                'answer_type' => $aVals['answer_type'], //Required
                'question_id' => $aVals['question_id'],
                'service_id' => $aVals['service_id'], //Required
                'question' => $aVals['question'], //Required
                'type' => $aVals['type'], //Required
                'answer' => $aVals['answer'], //Required(Comma Separeted)
                'reason' => $aVals['reason'],
            ]);     
        }
        if($aVals['type'] == 'edit'){
            $questions = SuggestedQuestion::create([
                'user_id' => $user_id, //Required
                'answer_type' => $aVals['answer_type'], //Required
                'question_id' => $aVals['question_id'], //Required
                'service_id' => $aVals['service_id'], //Required
                'question' => $aVals['question'], //Required
                'type' => $aVals['type'], //Required
                'answer' => $aVals['answer'], //Required(Comma Separeted)
                'reason' => $aVals['reason'],
            ]);   
        }
        if($aVals['type'] == 'remove'){
            $questions = SuggestedQuestion::create([
                'user_id' => $user_id, //Required
                'answer_type' => $aVals['answer_type'], 
                'question_id' => $aVals['question_id'], //Required
                'service_id' => $aVals['service_id'], //Required
                'question' => $aVals['question'], //Required
                'type' => $aVals['type'], //Required
                'answer' => $aVals['answer'],
                'reason' => $aVals['reason'], //Required
            ]);   
        }

        

        // if(!empty($questions)){
        //     $questions->update([
        //         'answer' => $aVals['answer'],
        //         'answer_type' => $aVals['answer_type']
        //     ]);
        // }else{
        //     $questions = SuggestedQuestion::create([
        //         'user_id' => $user_id,
        //         'question' => $aVals['question'],
        //         'answer_type' => $aVals['answer_type'],
        //         'answer' => $aVals['answer']
        //     ]);
        // }
    
        return $this->sendResponse(__('Thank you for the feedback'), []);
    }
}
