<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CustomHelper;

class PlansController extends Controller
{
    public function index()
    {
        $aRows = Plan::with(['category'])->get(); 
        return view('plans.index', compact('aRows'));
    }

    public function create()
    {
        $category = Category::where('status',1)->get();
        $aRow = array();
        return view('plans.create',compact('aRow','category'));
    }

    public function store(Request $request)
    {
        $this->validateSave($request);   
        return redirect()->route('plans.index')->with('success', 'Plan created successfully.');
    }

    public function show(Plan $plan)
    {
        return $plan;
    }

    public function edit(Plan $plan)
    {
        $category = Category::get();
        $aRow = $plan;
        return view('plans.create', compact('aRow','category'));
    }

    public function update(Request $request, Plan $plan)
    {
        $this->validateSave($request,$plan);      
        return redirect()->route('plans.index')
                         ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return redirect()->route('plans.index')
                         ->with('success', 'Plan deleted successfully.');
    }

    protected function validateSave(Request $request,$isEdit = "")
    {
        $aValids['category_id'] = 'required|numeric';
        $aValids['name'] =  'required|unique:plans|max:255';
        $aValids['price'] =  'required|numeric';
        $aValids['plan_type'] = 'required';

        if($isEdit)
        {
            $aValids['name'] =   'required|unique:plans,name,' . $isEdit->id . '|max:255';
        }

        $request->validate($aValids);

 
        $aVals = $request->all();



       // dd($aVals);

        if($isEdit)
        {
            $isEdit->update($aVals);
        }
        else{
            Plan::create($aVals);
        }

        
    }
}

