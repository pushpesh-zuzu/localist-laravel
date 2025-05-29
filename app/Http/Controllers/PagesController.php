<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\Category;
use App\Models\Faq;
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
        $pagemenu = Page::where('status',1)->get();
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
        
        $aRow = $page->load('faqs');
        $categories = Category::where('status',1)->get();
        $pagemenu = Page::where('status',1)->where('page_title','!=',$page->page_title)->get();
        return view('pages.create',get_defined_vars());
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
        // dd($request->all());
        $aValids['slug'] =  'required';
        $aValids['seo_title'] =  'required';
    
        if ($isEdit) {
            $aValids['page_title'] = 'required|unique:pages,page_title,' . $isEdit->id;
        } else {
            $aValids['page_title'] = 'required|unique:pages,page_title';
        }

        // Conditionally require category_id if it is present in the form
        $pageType = $request->input('page_type'); 
        if ($pageType == 1) {
            
            if(!empty($request->page_menu)){
                $request->merge(['page_menu' => $request->page_menu]);
            }else{
                $request->merge(['page_menu' => ""]);
            }
            
            $request->merge(['category_id' => ""]);
        } elseif ($pageType == 2) {
            // Type = Category: require category_id, clear page_menu
            $aValids['category_id'] = 'required';
            $request->merge(['page_menu' => ""]);
        }
        
        $validated = $request->validate($aValids);

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
            $pageId = $isEdit->id;
        }
        else{
            $page  = Page::create($aVals);
            $pageId = $page->id;
        }

        // Save FAQs logic
        Faq::where('page_id', $pageId)->delete();

        if ($request->has('faq_ques') && is_array($request->faq_ques)) {
            foreach ($request->faq_ques as $key => $question) {
                if (!empty($question)) {
                    \App\Models\Faq::create([
                        'page_id' => $pageId,
                        'question' => $question,
                        'answer' => $request->faq_ans[$key] ?? '',
                    ]);
                }
            }
        }
    }
    
}

