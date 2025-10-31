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
        abort_if(!auth()->user()->can('blog.viewlist'), 403, __('User does not have the right permissions.'));
        $aRows = Blog::get(); 
        return view('blogs.index', compact('aRows'));
    }

    public function create()
    {
        abort_if(!auth()->user()->can('blog.create'), 403, __('User does not have the right permissions.'));
        $aRow = array();
        return view('blogs.create',compact('aRow'));
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('blog.create'), 403, __('User does not have the right permissions.'));
        $this->validateSave($request);   
        return redirect()->route('blogs.index')->with('success', 'Blog created successfully.');
    }

    public function show(Blog $blog)
    {
        return $blog;
    }

    public function edit(Blog $blog)
    {
        abort_if(!auth()->user()->can('blog.edit'), 403, __('User does not have the right permissions.'));
        $aRow = $blog;
        return view('blogs.create', compact('aRow'));
    }

    public function update(Request $request, Blog $blog)
    {
        abort_if(!auth()->user()->can('blog.edit'), 403, __('User does not have the right permissions.'));
        $this->validateSave($request,$blog);      
        return redirect()->route('blogs.index')
                         ->with('success', 'Blog updated successfully.');
    }

    public function destroy(Blog $blog)
    {
        abort_if(!auth()->user()->can('blog.delete'), 403, __('User does not have the right permissions.'));
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

