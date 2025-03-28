<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceQuestion;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CustomHelper;

class ServiceQuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $aRows = ServiceQuestion::with('categories')->orderBy('id','DESC')->get(); 
        return view('servicequestion.index',get_defined_vars());
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
        $this->validateSave($request);   
        return redirect()->route('servicequestion.index')->with('success', 'Leads created successfully.');
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
        $leads = ServiceQuestion::where('id',$id)->first();
        $this->validateSave($request,$leads);      
        return redirect()->route('servicequestion.index')
                         ->with('success', 'Leads updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ServiceQuestion::where('id',$id)->delete();
        return redirect()->route('servicequestion.index')
                         ->with('success', 'Leads deleted successfully.');
    }

    protected function validateSave(Request $request,$isEdit = "")
    {

        $aValids['category'] =  'required';
        $aValids['questions'] =  'required';
        $aValids['answer'] =  'required';

        // if($isEdit)
        // {
        //     $aValids['name'] =   'required|unique:categories,name,' . $isEdit->id . '|max:255';
        // }

        $request->validate($aValids);

 
        $aVals = $request->all();
        if (!empty($aVals['answer'])) {
            // Remove extra spaces around commas and entries
            $cleanedAnswer = preg_replace('/\s*,\s*/', ',', $aVals['answer']);
    
            // Remove trailing comma if it exists
            $cleanedAnswer = rtrim($cleanedAnswer, ',');
    
            // Filter out any empty values after splitting by comma
            $answerArray = array_filter(explode(',', $cleanedAnswer), function($value) {
                return trim($value) !== ''; // Ensure no empty entries
            });
    
            // Rebuild the answer string
            $aVals['answer'] = implode(',', $answerArray);
        }

        if($isEdit)
        {
            $isEdit->update($aVals);
        }
        else{
            ServiceQuestion::create($aVals);
        }

       

        
    }
}
