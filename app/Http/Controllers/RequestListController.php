<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadRequest;

class RequestListController extends Controller
{

    public function index()
    {
        $aRows = LeadRequest::with(['customer','category'])->orderBy('id','DESC')->get(); 
        // echo "<pre>";
        // print_r($aRows);
        // exit;
        return view('request-list.index',get_defined_vars());
    }
    
}