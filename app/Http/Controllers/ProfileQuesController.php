<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfileQuestion;

class ProfileQuesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         abort_if(!auth()->user()->can('profilequestions.viewlist'), 403, __('User does not have the right permissions.'));
        $aRows = ProfileQuestion::orderBy('id','DESC')->get(); 
        return view('profilequestion.index',get_defined_vars());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(!auth()->user()->can('profilequestions.create'), 403, __('User does not have the right permissions.'));
        $aRow = array();
        return view('profilequestion.create',get_defined_vars());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         abort_if(!auth()->user()->can('profilequestions.create'), 403, __('User does not have the right permissions.'));
        $this->validateSave($request);   
        return redirect()->route('profilequestion.index')->with('success', 'Questions created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProfileQuestion $questions)
    {
        return $questions;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
         abort_if(!auth()->user()->can('profilequestions.edit'), 403, __('User does not have the right permissions.'));
        $aRow = ProfileQuestion::where('id',$id)->first();
        return view('profilequestion.create',get_defined_vars());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         abort_if(!auth()->user()->can('profilequestions.edit'), 403, __('User does not have the right permissions.'));
        $leads = ProfileQuestion::where('id',$id)->first();
        $this->validateSave($request,$leads);      
        return redirect()->route('profilequestion.index')
                         ->with('success', 'Questions updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
         abort_if(!auth()->user()->can('profilequestions.delete'), 403, __('User does not have the right permissions.'));
        ProfileQuestion::where('id',$id)->delete();
        return redirect()->route('profilequestion.index')
                         ->with('success', 'Question deleted successfully.');
    }
    protected function validateSave(Request $request,$isEdit = "")
    {
        $aValids['questions'] =  'required';

        $request->validate($aValids);
        $aVals = $request->all();
        if($isEdit)
        {
            $isEdit->update($aVals);
        }
        else{
            ProfileQuestion::create($aVals);
        }

       

        
    }
}
