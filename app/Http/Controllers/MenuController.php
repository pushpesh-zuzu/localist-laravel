<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\Category;
use App\Models\Menu;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CustomHelper;

class MenuController extends Controller
{
    public function index()
    {
        $aRows = Menu::with('parent')->get(); 
        return view('menus.index', compact('aRows'));
    }

    public function create()
    {
        $aRow = array();
        $parents = Menu::orderBy('id','DESC')->get();
        $pagemenu = Page::where('status',1)->get();
        return view('menus.create',get_defined_vars());
    }

    public function store(Request $request)
    {
        $this->validateSave($request);   
        return redirect()->route('menus.index')->with('success', 'Menu created successfully.');
    }

    public function show(Menu $menu)
    {
        return $menu;
    }

    public function edit(Menu $menu)
    {
        
        $aRow = $menu;
        $parents = Menu::orderBy('id','DESC')->get();
        $pagemenu = Page::where('status',1)->get();
        return view('menus.create',get_defined_vars());
    }

    public function update(Request $request, Menu $menus)
    {
        $this->validateSave($request,$menus);      
        return redirect()->route('menus.index')
                         ->with('success', 'Menu updated successfully.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('menus.index')
                         ->with('success', 'Menu deleted successfully.');
    }

    protected function validateSave(Request $request,$isEdit = "")
    {
        $aValids['menu_name'] =  'required';
    
        if ($isEdit) {
            $aValids['menu_pageid'] = 'required|unique:menus,menu_pageid,' . $isEdit->id;
        } else {
            $aValids['menu_pageid'] = 'required|unique:menus,menu_pageid';
        }

        if(!empty($request->menu_parent)){
          
            $request->merge(['menu_parent' => $request->menu_parent]);
        }else{
            $request->merge(['menu_parent' => 0]);
        }
        // if(!empty($request->menu_customlink)){
        //     $request->merge(['menu_customlink' => $request->menu_customlink]);
        // }else{
        //     $request->merge(['menu_customlink' => ""]);
        // }
        
        
        $validated = $request->validate($aValids);

        $aVals = $request->all();

        if($isEdit)
        {
            $isEdit->update($aVals);
        }
        else{
            $page  = Menu::create($aVals);
        }
    }
    
}

