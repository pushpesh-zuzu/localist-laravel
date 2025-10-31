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
         abort_if(!auth()->user()->can('plans.viewlist'), 403, __('User does not have the right permissions.'));
        $aRows = Plan::with(['category'])->get(); 
        return view('plans.index', compact('aRows'));
    }

    public function create()
    {
         abort_if(!auth()->user()->can('plans.create'), 403, __('User does not have the right permissions.'));
        $category = Category::where('status',1)->get();
        $aRow = array();
        return view('plans.create',compact('aRow','category'));
    }

    public function store(Request $request)
    {
         abort_if(!auth()->user()->can('plans.create'), 403, __('User does not have the right permissions.'));

        $this->validateSave($request);   
        return redirect()->route('plans.index')->with('success', 'Plan created successfully.');
    }

    public function show(Plan $plan)
    {
        return $plan;
    }

    public function edit(Plan $plan)
    { 
        abort_if(!auth()->user()->can('plans.edit'), 403, __('User does not have the right permissions.'));

        $category = Category::get();
        $aRow = $plan;
        return view('plans.create', compact('aRow','category'));
    }

    public function update(Request $request, Plan $plan)
    {
        abort_if(!auth()->user()->can('plans.edit'), 403, __('User does not have the right permissions.'));
        $this->validateSave($request,$plan);      
        return redirect()->route('plans.index')
                         ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan)
    {
        abort_if(!auth()->user()->can('plans.delete'), 403, __('User does not have the right permissions.'));
        $plan->delete();
        return redirect()->route('plans.index')
                         ->with('success', 'Plan deleted successfully.');
    }

    protected function validateSave(Request $request,$isEdit = "")
    {
        $aValids['category_id'] = 'required|numeric';
        $aValids['name'] =  'required|max:255';
        $aValids['price'] =  'required|numeric';
        $aValids['plan_type'] = 'required';
        $aValids['no_of_responses'] = 'required';

        if($isEdit)
        {
            $aValids['name'] =   'required|max:255';
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

