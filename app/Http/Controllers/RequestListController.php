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
            $matched_leads = \DB::table('lead_requests')
            ->select(
                'lead_requests.*',
                'users.name as customer_name',
                'categories.name as category_name'
            )
            ->leftJoin('users', 'users.id', '=', 'lead_requests.customer_id')
            ->leftJoin('categories', 'categories.id', '=', 'lead_requests.service_id')
            ->orderBy('lead_requests.id', 'desc');

        return Datatables::of($matched_leads)
            ->addIndexColumn()

            ->editColumn('city', function ($item) {
                $city = $item->city;
                $city .= !empty($city)? ', ' . $item->postcode : $item->postcode;
                return $city ?: 'N/A';
            })
            ->editColumn('customer_name', function ($item) {
                return $item->customer_name ?: 'N/A';
            })
            ->editColumn('category_name', function ($item) {
                return $item->category_name ?: 'N/A';
            })
            ->editColumn('created_at', function ($item) {
                return date('m/d/Y h:i A', strtotime($item->created_at));
            })
            ->editColumn('questions', function ($item) {
                $output = "";
                $quesArr = json_decode($item->questions, true);
                if (is_array($quesArr)) {
                    foreach ($quesArr as $index => $q) {
                        $output .= "<b>Q" . ($index + 1) . ".</b> " . e($q['ques']) . "<br>";
                        $output .= "<b>Ans: </b>" . e($q['ans']) . "<br><br>";
                    }
                }
                return $output;
            })

            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $search = $request->get('search')['value']) {
                    $query->where(function ($q) use ($search) {
                        $q->orWhere('lead_requests.postcode', 'like', "%{$search}%")
                        ->orWhere('lead_requests.city', 'like', "%{$search}%")
                        ->orWhere('lead_requests.status', 'like', "%{$search}%")
                        ->orWhere('lead_requests.details', 'like', "%{$search}%")
                        ->orWhere('lead_requests.questions', 'like', "%{$search}%")
                        ->orWhere('lead_requests.phone', 'like', "%{$search}%")
                        ->orWhere('users.name', 'like', "%{$search}%")
                        ->orWhere('categories.name', 'like', "%{$search}%");
                    });
                }
            })
            
            ->rawColumns(['questions', 'customer_name', 'category_name', 'created_at','city'])
            ->make(true);
        }

        return view('request-list.index');
    }

    
}