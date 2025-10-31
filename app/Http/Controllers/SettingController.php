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
         abort_if(!auth()->user()->can('generalsettings.viewlist'), 403, __('User does not have the right permissions.'));
        $data['settings'] = Setting::get(); 
        return view('settings.index',$data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id=null)
    {
        abort_if(!auth()->user()->can('generalsettings.create'), 403, __('User does not have the right permissions.'));
        $data['settings'] = '';
        
        return view('settings.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         abort_if(!auth()->user()->can('generalsettings.store'), 403, __('User does not have the right permissions.'));
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
        abort_if(!auth()->user()->can('generalsettings.edit'), 403, __('User does not have the right permissions.'));
        $data['settings'] = Setting::where('id',$id)->first();
        return view('settings.create', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         abort_if(!auth()->user()->can('generalsettings.edit'), 403, __('User does not have the right permissions.'));
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
         abort_if(!auth()->user()->can('generalsettings.delete'), 403, __('User does not have the right permissions.'));
        Setting::where('id',$id)->delete();
        return redirect()->route('settings.index')
                         ->with('success', 'Setting deleted successfully.');
    }

    
}
