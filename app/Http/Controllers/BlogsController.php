<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CustomHelper;

class BlogsController extends Controller
{
    public function index()
    {
        $aRows = Blog::get(); 
        return view('blogs.index', compact('aRows'));
    }

    public function create()
    {
        $aRow = array();
        return view('blogs.create',compact('aRow'));
    }

    public function store(Request $request)
    {
        $this->validateSave($request);   
        return redirect()->route('blogs.index')->with('success', 'Blog created successfully.');
    }

    public function show(Blog $blog)
    {
        return $blog;
    }

    public function edit(Blog $blog)
    {
        $aRow = $blog;
        return view('blogs.create', compact('aRow'));
    }

    public function update(Request $request, Blog $blog)
    {
        $this->validateSave($request,$blog);      
        return redirect()->route('blogs.index')
                         ->with('success', 'Blog updated successfully.');
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();
        return redirect()->route('blogs.index')
                         ->with('success', 'Blog deleted successfully.');
    }

    protected function validateSave(Request $request,$isEdit = "")
    {

        $aValids['name'] =  'required|unique:blogs|max:255';

        if($isEdit)
        {
            $aValids['name'] =   'required|unique:blogs,name,' . $isEdit->id . '|max:255';
        }

        $request->validate($aValids);

 
        $aVals = $request->all();

        if($request->hasFile('banner_image')){ 
            $aVals['banner_image'] = CustomHelper::fileUpload($request->banner_image,'blogs');
        }

       // dd($aVals);

        if($isEdit)
        {
            $isEdit->update($aVals);
        }
        else{
            Blog::create($aVals);
        }

        
    }
}

