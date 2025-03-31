<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($type = "")
    {
        $aRows = [];
        if($type == 'sellers'){
            $aRows = User::whereIn('user_type', [1, 3])->orderBy('id','DESC')->get(); 
            $type = 'Sellers';
        }
        if($type == 'buyers'){
            $aRows = User::whereIn('user_type', [2, 3])->orderBy('id','DESC')->get(); 
            $type = 'Buyers';
        }
        if($type == 'users'){
            $aRows = User::orderBy('id','DESC')->get(); 
            $type = 'Users';
        }
        if($type == 'inactive'){
            $aRows = User::where('status',0)->orderBy('id','DESC')->get(); 
            $type = 'InActive';
        }
        if($type == 'active'){
            $aRows = User::where('status',1)->orderBy('id','DESC')->get(); 
            $type = 'Active';
        }
        
        return view('users.index', compact('aRows','type'));
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
