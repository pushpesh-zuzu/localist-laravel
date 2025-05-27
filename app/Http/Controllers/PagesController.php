<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CustomHelper;

class PagesController extends Controller
{
    public function index()
    {
        $aRows = Page::get(); 
        return view('pages.index', compact('aRows'));
    }

    public function create()
    {
        $aRow = array();
        $categories = Category::where('status',1)->get();
        return view('pages.create',get_defined_vars());
    }

    public function store(Request $request)
    {
        $this->validateSave($request);   
        return redirect()->route('pages.index')->with('success', 'Page created successfully.');
    }

    public function show(Page $page)
    {
        return $page;
    }

    public function edit(Page $page)
    {
        $aRow = $page;
        return view('pages.create', compact('aRow'));
    }

    public function update(Request $request, Page $page)
    {
        $this->validateSave($request,$page);      
        return redirect()->route('pages.index')
                         ->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->route('pages.index')
                         ->with('success', 'Page deleted successfully.');
    }

    protected function validateSave(Request $request,$isEdit = "")
    {

        $aValids['name'] =  'required|unique:pages|max:255';

        if($isEdit)
        {
            $aValids['name'] =   'required|unique:pages,name,' . $isEdit->id . '|max:255';
        }

        $request->validate($aValids);

 
        $aVals = $request->all();

        if($request->hasFile('banner_image')){ 
            $aVals['banner_image'] = CustomHelper::fileUpload($request->banner_image,'pages');
        }

         if($request->hasFile('og_image')){ 
            $aVals['og_image'] = CustomHelper::fileUpload($request->og_image,'pages');
        }

       // dd($aVals);

        if($isEdit)
        {
            $isEdit->update($aVals);
        }
        else{
            Page::create($aVals);
        }
    }
}

