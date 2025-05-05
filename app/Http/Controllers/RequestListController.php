<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Yajra\DataTables\Html\Builder;
use App\Models\LeadRequest;

class RequestListController extends Controller
{

    public function index(Request $request, Builder $builder){
        if ($request->ajax()) {
            $matched_leads = LeadRequest::with(['customer', 'category'])
                ->orderBy('id','desc');
            return Datatables::of($matched_leads)
                ->addIndexColumn()
                ->addColumn('action', function($item){
                    $html = '';
                    // $html    = '<div class="edit_details_box">';
                    // if(is_admin()){
                    //     // $html   .= '<a href="'.url(sprintf('campaign-lead/%s/edit',encrypt($item->id))).'" data-toggle="tooltip" title="Edit"><i class="fa fa-edit"></i></a>';
                    //     $html   .= ' <a href="javascript:void(0);" 
                    //         data-url="'.url(sprintf('admin/campaign-field/status/?id=%s&status=trashed',encrypt($item->id))).'" 
                    //         data-request="ajax-confirm"
                    //         data-ask="Are you sure you want to delete '.$item->account_name .' ?" data-toggle="tooltip" title="Delete"><i class="fa fa-fw fa-trash"></i></a>';
                        
                    // }

                    // $html   .= '</div>';
                                        
                    return $html;
                })
                ->editColumn('customer_id', function($item){
                    return $item->customer ? $item->customer->name : 'N/A';
                })
                ->editColumn('service_id', function($item){
                    return $item->category ? $item->category->name : 'N/A';
                })
                ->editColumn('questions', function($item){
                    $rel = "";
                    $quesArr = json_decode($item->questions,true);
                    $i =1;
                    foreach($quesArr as $q){
                        $rel .= "<b>Q$i.</b>" .$q['ques'] ."<br>";
                        $rel .="<b>Ans: &nbsp;</b>" .$q['ans'] ."<br><br>";
                        $i++;
                    }
                    return $rel;
                })
                ->editColumn('created_at', function($item){
                    return date('m/d/Y h:i a', strtotime($item->created_at));
                })
                ->rawColumns(['action','customer_id','service_id','questions','created_at'])
                ->make(true);
        }

        return view('request-list.index');
    }



    public function index2()
    {
        $aRows = LeadRequest::with(['customer','category'])->orderBy('id','DESC')->limit(50000)->get(); 
        // echo "<pre>";
        // print_r($aRows);
        // exit;
        return view('request-list.index2',get_defined_vars());
    }
    
}