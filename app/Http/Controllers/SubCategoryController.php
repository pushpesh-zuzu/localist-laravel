<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CustomHelper;

class SubCategoryController extends Controller
{
    // Display a listing of categories
    public function index()
    {
        $aRows = Category::where('parent_id','>',0)->with('parent')->get(); // Returns all categories
        return view('subcategories.index', compact('aRows'));
    }

    // Show the form for creating a new category
    public function create()
    {
        $aRow = array();
        $aCategories = Category::where('parent_id',0)->get()->pluck('name', 'id')->toArray(); 
        return view('subcategories.create',compact('aRow','aCategories'));
    }

    // Store a newly created category in storage
    public function store(Request $request)
    {
        $this->validateSave($request);   
        return redirect()->route('subcategories.index')->with('success', 'Category created successfully.');
    }

    // Display the specified category
    public function show(Category $subcategory)
    {
        return $subcategory;
    }

    // Show the form for editing the specified category
    public function edit(Category $subcategory)
    {     
        $aRow = $subcategory    ;
        $aCategories = Category::where('parent_id',0)->get()->pluck('name', 'id')->toArray(); 
        return view('subcategories.create',compact('aRow','aCategories'));
    }

    // Update the specified category in storage
    public function update(Request $request, Category $subcategory)
    {
        $this->validateSave($request,$subcategory);      
        return redirect()->route('subcategories.index')
                         ->with('success', 'Category updated successfully.');
    }

    // Remove the specified category from storage
    public function destroy(Category $subcategory)
    {
        $subcategory->delete();
        return redirect()->route('subcategories.index')
                         ->with('success', 'Category deleted successfully.');
    }

    protected function validateSave(Request $request,$isEdit = "")
    {

        $aValids['name'] =  'required|unique:categories|max:255';
        $aValids['category_icon'] =  'image|mimes:jpg,png,jpeg,gif,svg|max:2048';

        if(isset($isEdit) && $isEdit)
        {
            $aValids['name'] =   'required|unique:categories,name,' . $isEdit->id . '|max:255';
        }

        $request->validate($aValids);

 
        $aVals = $request->all();

        if($request->hasFile('category_icon')){ 
            $aVals['category_icon'] = CustomHelper::fileUpload($request->category_icon,'category');
        }
        if($request->hasFile('banner_image')){ 
            $aVals['banner_image'] = CustomHelper::fileUpload($request->banner_image,'category');
        }

       // dd($aVals);

        if($isEdit)
        {
            $isEdit->update($aVals);
        }
        else{
            Category::create($aVals);
        }

       

        
    }
}

