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
        $aRow = array();
        $categories = Category::where('status',1)->get();
        return view('servicequestion.create',get_defined_vars());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|integer|exists:categories,id',
            'questions' => 'required',
            'answer' => 'required',
            'option_type' => 'required'
            ], [
            'service_id.exists' => 'Provided service id does not exists.',
            'option_type.required' => 'Option selection type is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $data['category'] = $request->category;
        $data['questions'] = $request->questions;
        $answer = "";
        if (!empty($request->answer)) {
            // Remove extra spaces around commas and entries
            $cleanedAnswer = preg_replace('/\s*,\s*/', ',', $request->answer);
    
            // Remove trailing comma if it exists
            $cleanedAnswer = rtrim($cleanedAnswer, ',');
    
            // Filter out any empty values after splitting by comma
            $answerArray = array_filter(explode(',', $cleanedAnswer), function($value) {
                return trim($value) !== ''; // Ensure no empty entries
            });
    
            // Rebuild the answer string
            $answer = implode(',', $answerArray);
        }
        $data['answer'] = $request->answer;
        $data['option_type'] = $request->option_type;
        $id = ServiceQuestion::insertGetId($data);
        return redirect()->route('servicequestion.index')->with('success', 'Questions created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceQuestion $leads)
    {
        return $leads;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $aRow = ServiceQuestion::where('id',$id)->first();
        $categories = Category::where('status',1)->get();
        return view('servicequestion.create',get_defined_vars());
    }

    // Update the specified Leads in storage
    public function update(Request $request, string $id)
    {
        // dd($request->all());
       
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ServiceQuestion::where('id',$id)->delete();
        return redirect()->route('servicequestion.index')
                         ->with('success', 'Question deleted successfully.');
    }

    
}
