<?php

namespace App\Http\Controllers;
use App\Models\Setting;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $aRows = Setting::first(); 
        return view('settings.index',get_defined_vars());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id=null)
    {
        if($id>0){
            $aRow = Setting::where('id',1)->first(); 
        }else{
            $aRow = null; 
        }
        
        return view('settings.create',compact('aRow'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $existing = Setting::exists();
        if ($existing) {
            return redirect()->route('settings.index')->with('error', 'You cannot add more than one.');
            // return back()->withErrors(['data' => 'You cannot add more than one.'])->withInput();
        }
        $this->validateSave($request); 
        return redirect()->route('settings.index')->with('success', 'Settings created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $settings)
    {
        return $settings;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $aRow = Setting::where('id',$id)->first();
        return view('settings.create', compact('aRow'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $settings = Setting::where('id',$id)->first();
        $this->validateSave($request,$settings);      
        return redirect()->route('settings.index')
                         ->with('success', 'Settings updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Setting::where('id',$id)->delete();
        return redirect()->route('settings.index')
                         ->with('success', 'Plan deleted successfully.');
    }

    protected function validateSave(Request $request,$isEdit = "")
    {

        $aValids['total_bid'] =  'required';
        $request->validate($aValids);
        $aVals = $request->all();

       
        
        if($isEdit)
        {
            $isEdit->update($aVals);
        }
        else{
            Setting::create($aVals);
        }

        
    }
}
