<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Blog;
use App\Models\Category;
use App\Helpers\CustomHelper;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
         // CustomHelper::sendEmail(array("to" => "webplanetsoft@gmail.com","subject" => "test", "body" => "this is test body",'receiver' => "Ankit "));
        
        $totalusers = User::count();
        $totalsellers = User::where('user_type',[1, 3])->count();
        $totalbuyer = User::where('user_type',[2, 3])->count();
        $inactiveusers = User::where('status',0)->count();
        $activeusers = User::where('status',1)->count();
        $totalcategories = Category::count();
        $totalblogs = Blog::count();
        return view('dashboard',get_defined_vars());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
