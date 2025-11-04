<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceQuestion;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;

class ServiceQuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         abort_if(!auth()->user()->can('servicequestions.viewlist'), 403, __('User does not have the right permissions.'));
         // Get all category IDs that have service questions
        // $categoryIdsWithQuestions = ServiceQuestion::distinct()->pluck('category')->toArray();

        // // Fetch only those categories which have questions
        // $aRows = Category::whereIn('id', $categoryIdsWithQuestions)
        //                 ->where('status', 1)
        //                 ->get();

        // // Attach service questions to each category
        // foreach ($aRows as $key => $value) {
        //     $value['servQuestions'] = ServiceQuestion::where('category', $value->id)->get();
        // }
        $aRows = ServiceQuestion::with('categories')->get();
        

        return view('servicequestion.index', get_defined_vars());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
          abort_if(!auth()->user()->can('servicequestions.create'), 403, __('User does not have the right permissions.'));
        $aRow = array();
        $categories = Category::where('status',1)->get();
        return view('servicequestion.create',get_defined_vars());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('servicequestions.create'), 403, __('User does not have the right permissions.'));
        echo "<pre>";print_r($request->all());
        $validator = Validator::make($request->all(), [
            'category' => 'required|integer|exists:categories,id',
            'question_no' => 'required',
            'questions' => 'required',
            'ques_opt' => 'required',
            'next_ques' => 'required',
            'ques_score' => 'required',
            'option_type' => 'required',
            'question_type' => 'required'
            ], [
            'service_id.exists' => 'Provided service id does not exists.',
            'option_type.required' => 'Option selection type is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $data['category'] = $request->category;
        $data['question_no'] = $request->question_no;
        $data['questions'] = $request->questions;
        $answer = [];
        $ques_opt = $request->ques_opt;
        $ques_score = $request->ques_score;
        $next_ques = $request->next_ques;
        foreach($ques_opt as $i => $opt){
            $temp['option'] = $opt;
            $temp['score'] = $ques_score[$i];
            $temp['next_question'] = $next_ques[$i];
            array_push($answer, $temp);
        }

        
        $data['answer'] = json_encode($answer);
        $data['option_type'] = $request->option_type;
        $data['question_type'] = $request->question_type;
        print_r($data);
        // exit;
        $id = ServiceQuestion::insertGetId($data);
        return redirect()->route('servicequestion.index')->with('success', 'Questions created successfully.');
    }



    public function addMoreOption(Request $request){
        return response()->json([
            'status'    => true,
            'html'      => view("servicequestion.add-more-option",['type'=>$request->type,'count'=>$request->count])->render()
        ]);
    }

    
}
