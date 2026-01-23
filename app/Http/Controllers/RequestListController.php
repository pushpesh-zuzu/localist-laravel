<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Yajra\DataTables\Html\Builder;
use App\Models\LeadRequest;

class RequestListController extends Controller
{

    public function index(Request $request, Builder $builder)
    {
        abort_if(!auth()->user()->can('requestlist.viewlist'), 403, __('User does not have the right permissions.'));
        if ($request->ajax()) {
           
            $matched_leads = \DB::table('lead_requests')
                ->select(
                    'lead_requests.*',
                    'users.name as customer_name',
                    'categories.name as category_name',
                    'recommended_leads.final_price',
                    'recommended_leads.unit_type'
                )
                ->leftJoin('recommended_leads', function ($join) {
                    $join->on('recommended_leads.lead_id', '=', 'lead_requests.id')
                        ->whereNotNull('recommended_leads.final_price');
                })
                ->leftJoin('users', 'users.id', '=', 'lead_requests.customer_id')
                ->leftJoin('categories', 'categories.id', '=', 'lead_requests.service_id')
                ->orderBy('lead_requests.id', 'desc');


            return Datatables::of($matched_leads)
                ->addIndexColumn()

                ->editColumn('city', function ($item) {
                    $city = $item->city;
                    $city .= !empty($city) ? ', ' . $item->postcode : $item->postcode;
                    return $city ?: 'N/A';
                })
                ->editColumn('customer_name', function ($item) {
                    return $item->customer_name ?: 'N/A';
                })
                ->editColumn('category_name', function ($item) {
                    return $item->category_name ?: 'N/A';
                })
                ->editColumn('created_at', function ($item) {
                    return date('d/m/Y h:i A', strtotime($item->created_at));
                })
                ->editColumn('questions', function ($item) {
                    $output = "";
                    $quesArr = json_decode($item->questions, true);
                    if (is_array($quesArr)) {
                        foreach ($quesArr as $index => $q) {
                            if (!is_array($q) || !isset($q['ques'], $q['ans'])) {
                                continue; // skip null or invalid entries
                            }
                            $output .= "<b>Q" . ($index + 1) . ".</b> " . e($q['ques']) . "<br>";
                            $output .= "<b>Ans: </b>" . e($q['ans']) . "<br><br>";
                        }
                    }
                    return $output;
                })
                ->editColumn('hired_to', function ($item) {
                    $hired_user = "";
                    if ($item->status != 'hired') {
                        return '';
                    } else if ($item->status == 'hired' && $item->hired_to == null) {
                        return 'N/A';
                    } else if ($item->status == 'hired' && $item->hired_to == 0) {
                        $hired_user = "Someone not on Localists";
                    } else if ($item->status == 'hired' && !empty($item->hired_to)) {
                        $user = \DB::table('users')->where('id', $item->hired_to)->first();
                        if ($user) {
                            $hired_user = $user->name;
                        }
                    }
                    return $hired_user;
                })->addColumn('price_unit', function ($item) {
                    // Combine final_price and unit_type in one column
                    $price = $item->final_price ? '£' . $item->final_price : 'N/A';
                    $unit  = $item->unit_type ?: '';
                    return $price . ($unit ? ' / ' . $unit : '');
                })
                ->filterColumn('price_unit', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->orWhere('recommended_leads.final_price', 'like', "%{$keyword}%")
                            ->orWhere('recommended_leads.unit_type', 'like', "%{$keyword}%");
                    });
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

                ->rawColumns(['questions', 'customer_name', 'category_name', 'created_at', 'city'])
                ->make(true);
        }

        return view('request-list.index');
    }
}
