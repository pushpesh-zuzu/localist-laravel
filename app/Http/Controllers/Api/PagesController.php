<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Menu;
use App\Models\Page;
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

        // Fetching grouped menu items by menu_name
        $forCustomers = Menu::whereIn('menu_name', $forCustomersMenus)->get();
        $forProfessionals = Menu::whereIn('menu_name', $forProfessionalsMenus)->get();
        $about = Menu::whereIn('menu_name', $aboutMenus)->get();
        
        return $this->sendResponse(__('Pages Data'), [
            [
                'forCustomers' => $forCustomers,
                'forProfessionals' => $forProfessionals,
                'about' => $about,
            ]
        ]);
    }

    public function pageDetails($page_slug){
        $pageDetails = Page::where('slug', $page_slug)->get();
        
        return $this->sendResponse(__('Pages Data'), ['pageDetails' => $pageDetails]);
    }
}
