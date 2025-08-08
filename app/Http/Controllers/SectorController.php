<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Yajra\Datatables\Datatables;
use Yajra\DataTables\Html\Builder;
use Illuminate\Support\Facades\DB;
use App\Helpers\CustomHelper;
use App\Models\Category;

class SectorController extends Controller{
    public function index(Request $request, Builder $builder){
        $data['sectorsList']= Category::with(['subsectors'])->where('parent_id', '0')->where('status','1')->get()->toArray();
        // echo "<pre>";
        // print_r($data['sectorsList']);
        // exit;
        return view('sectors.index', $data);
    }

    public function create(Request $request){
        $data['sectorsList']= Category::where('status','1')->get();   
        $data['sector'] = '';
        return view('sectors.create',$data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:categories,name',
            'homepage_display_name' => 'required',
            'breadcrumb_title' => 'required',
          ], [
            'name.unique' => 'Sector Name already exists.'
        ]);

        $validator->validate();

        if($request->is_subsector){
            $data['parent_id'] = $request->pr_id;
        }
        $data['name'] = $request->name;
        $data['homepage_display_name'] = $request->homepage_display_name;
        $data['description'] = $request->description;
        $data['breadcrumb_title'] = $request->breadcrumb_title;
        $data['seo_title'] = $request->seo_title;
        $data['seo_description'] = $request->seo_description;
        $data['is_home'] = $request->is_home;
        $data['is_popular'] = $request->is_popular;
        if($request->hasFile('category_icon')){ 
            $data['category_icon'] = CustomHelper::fileUpload($request->category_icon,'category');
        }
        if($request->hasFile('banner_image')){ 
            $data['banner_image'] = CustomHelper::fileUpload($request->banner_image,'category');
        }

        Category::create($data);
        return redirect()->route('sectors.index')->with('success', 'Sector created successfully.');
    

        // $this->validateSave($request);   
        // return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    // Show the form for editing the specified category
    public function edit(Request $request, $id)
    {
        $data['sectorsList']= Category::where('status','1')->get(); 
        $data['sector'] = Category::where('id', $id)->first()->toArray();
        // echo "<pre>";
        // print_r($data['sector']);
        // exit;
        return view('sectors.create',$data);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                Rule::unique('categories', 'name')->ignore($id),
            ],
            'homepage_display_name' => 'required',
            'breadcrumb_title' => 'required',
          ], [
            'name.exists' => 'Sector Name already exists.'
        ]);

        $validator->validate();

        if($request->is_subsector){
            $data['parent_id'] = $request->pr_id;
        }
        $data['name'] = $request->name;
        $data['homepage_display_name'] = $request->homepage_display_name;
        $data['description'] = $request->description;
        $data['breadcrumb_title'] = $request->breadcrumb_title;
        $data['seo_title'] = $request->seo_title;
        $data['seo_description'] = $request->seo_description;
        $data['is_home'] = $request->is_home;
        $data['is_popular'] = $request->is_popular;
        if($request->hasFile('category_icon')){ 
            $data['category_icon'] = CustomHelper::fileUpload($request->category_icon,'category');
        }
        if($request->hasFile('banner_image')){ 
            $data['banner_image'] = CustomHelper::fileUpload($request->banner_image,'category');
        }

        Category::where('id', $id)->update($data);
        return redirect()->route('sectors.index')->with('success', 'Sector updated successfully.');
    }


    // Remove the specified category from storage
    public function destroy(Request $request, $id)
    {
        Category::where('id', $id)->delete();
        return redirect()->route('sectors.index')
                         ->with('success', 'Sector deleted successfully.');
    }

}