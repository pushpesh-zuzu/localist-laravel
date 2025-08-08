<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Category;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Support\Facades\Storage;
use \Carbon\Carbon;

class PagesController extends Controller
{
    public function bottomPages(){
        $forCustomersMenus = ['Find a Professional', 'How it works', 'Login', 'Mobile App'];
        $forProfessionalsMenus = ['How it works', 'Pricing', 'Join as a Professional', 'Help Centre', 'Mobile App'];
        $aboutMenus = ['About Localists', 'Careers', 'Blog', 'Press'];

        $forCustomers = Menu::whereIn('menu_name', $forCustomersMenus)->get();
        $forProfessionals = Menu::whereIn('menu_name', $forProfessionalsMenus)->get();
        $about = Menu::whereIn('menu_name', $aboutMenus)->get();
        $help = Menu::where('menu_slug','help-center')->first();
        
        return $this->sendResponse(__('Pages Data'), [
            [
                'forCustomers' => $forCustomers,
                'forProfessionals' => $forProfessionals,
                'about' => $about,
                'help' => $help,
            ]
        ]);
    }

    public function pageDetails($page_slug){
        // Fetch page details
        $pageDetails = Page::where('slug', $page_slug)->first();

        if (empty($pageDetails)) {
            return $this->sendError('Page not found');
        }

        // Get the first page's category ID
        $categoryId = $pageDetails->category_id;

        // Build category hierarchy (levels)
        $levels = [];
        if ($categoryId) {
            $category = Category::find($categoryId);
            if ($category) {
                $stack = [];
                $current = $category;
                while ($current) {
                    $stack[] = $current;
                    $current = $current->parent; // parent() from your Category model
                }
                $stack = array_reverse($stack);

                foreach ($stack as $index => $cat) {
                    $levels[] = [
                        'name'  => $cat->name,
                        // 'slug'  => $cat->slug,
                        'level' => $index + 1
                    ];
                }
            }
        }

        //add the current as level 1 if no level is found
        if(empty($levels)){
            $levels[] = [
                'name'  => $pageDetails->page_title,
                // 'slug'  => $pageDetails->slug,
                'level' => 1
            ];
        }

        // show_form_no logic
        $show_form_no = count($levels) > 2 ? 2 : 1;

        // Final response
        return $this->sendResponse(__('Pages Data'), [
            'pageDetails'  => $pageDetails,
            'levels'       => $levels,
            'show_form_no' => $show_form_no
        ]);
    }

}
