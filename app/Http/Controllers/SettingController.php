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
        $data['settings'] = Setting::get(); 
        return view('settings.index',$data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id=null)
    {
        $data['settings'] = '';
        
        return view('settings.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'setting_name' => 'required|unique:settings',
            'setting_value' => 'required',
            ], [
            'postcode.required' => 'Location Postcode is required.',
        ]);

        $validator->validate();
        
        $data['setting_name'] = strtolower(str_replace(' ','_',$request->setting_name));
        $data['setting_value'] = $request->setting_value;
        Setting::insertGetId($data);
        
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
        $data['settings'] = Setting::where('id',$id)->first();
        return view('settings.create', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = \Validator::make($request->all(), [
            'setting_name' => 'required|unique:settings,setting_name,' . $id,
            'setting_value' => 'required',
            ], [
            'postcode.required' => 'Location Postcode is required.',
        ]);

        $validator->validate();
        $data['setting_name'] = strtolower(str_replace(' ','_',$request->setting_name));
        $data['setting_value'] = $request->setting_value;
        Setting::where('id',$id)->update($data);
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
                         ->with('success', 'Setting deleted successfully.');
    }

    
}
