<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecommendedLead;
use App\Models\UniqueVisitor;
use App\Models\LeadRequest;
use App\Models\LoginHistory;
use App\Models\Category;
use App\Models\User;
use App\Models\AbandonedUser;
use App\Models\ContactUs;
use Carbon\Carbon;
use DB;
use Yajra\Datatables\Datatables;
use Yajra\DataTables\Html\Builder;

class BuyerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = User::with(['leadRequests.category', 'lastLogin'])
                ->whereIn('user_type', [2, 3])
                ->where('form_status', 1)
                ->where(function ($q) {
                    $q->whereNull('name')
                        ->orWhere('name', 'not like', '%test%');
                })
                ->where(function ($q) {
                    $q->whereNull('email')
                        ->orWhere('email', 'not like', '%test%');
                })
                ->orderBy('id', 'DESC');
            if ($request->from_date && $request->to_date) {
                $query->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            } elseif ($request->from_date) {
                $query->whereDate('created_at', '>=', $request->from_date);
            } elseif ($request->to_date) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('postcode', function ($user) {
                    return $user->leadRequests->pluck('postcode')->implode('<br>');
                })
                ->addColumn('services', function ($user) {
                    return $user->leadRequests->map(fn($s) => $s->category->name ?? 'N/A')->implode('<br>');
                })
                ->addColumn('score', function ($user) {
                    return $user->leadRequests->pluck('credit_score')->implode('<br>');
                })
                ->addColumn('date', function ($user) {
                    return Carbon::parse($user->created_at)->format('m/d/Y h:i a');
                })->filterColumn('date', function ($query, $keyword) {
                    try {
                        $date = Carbon::parse($keyword)->format('Y-m-d');
                        $query->whereDate('created_at', $date);
                    } catch (\Exception $e) {
                    }
                })
                //  ->addColumn('entry_url', function ($user) {
                //     $url = $user->entry_url ?? '';
                //     return '<div style="word-break: break-all; max-width: 200px;">' . e($url) . '</div>';
                // })
                // ->addColumn('user_ip_address', fn($user) => $user->user_ip_address ?? '')
                ->addColumn('last_login', function ($user) {
                    return $user->lastLogin?->login_at
                        ? \Carbon\Carbon::parse($user->lastLogin->login_at)->format('m/d/Y h:i A')
                        : '';
                })
                ->filterColumn('last_login', function ($query, $keyword) {
                    try {
                        $parsed = \Carbon\Carbon::createFromFormat('m/d/Y h:i A', $keyword);
                        $query->whereHas('lastLogin', function ($q) use ($parsed) {
                            $q->whereDate('login_at', $parsed->toDateString());
                        });
                    } catch (\Exception $e) {
                        try {
                            $parsed = \Carbon\Carbon::createFromFormat('m/d/Y', $keyword);
                            $query->whereHas('lastLogin', function ($q) use ($parsed) {
                                $q->whereDate('login_at', $parsed->toDateString());
                            });
                        } catch (\Exception $ex) {
                            $query->whereHas('lastLogin', function ($q) use ($keyword) {
                                $q->where('login_at', 'like', "%{$keyword}%");
                            });
                        }
                    }
                })
                ->addColumn('status', fn($user) => 'Complete') // Always show Complete
                ->addColumn('action', function ($user) {
                    return '
                    <a href="' . route('buyer.buyerBids', $user->id) . '" class="text text-primary" title="Bids">
                        <i class="fa-solid fa-chess-pawn"></i>
                    </a>
                    <a href="' . route('buyer.viewCount', $user->id) . '" class="text text-primary" title="Unique Visitors">
                        <i class="fa-solid fa-users"></i>
                    </a>
                    <a href="' . route('buyer.buyerLogin', $user->id) . '" class="text text-primary" title="Login History">
                        <i class="fa-solid fa-history"></i>
                    </a>
                    <a href="' . route('buyer.show.custom', ['type' => 'complete', 'id' => $user->id]) . '" class="text text-primary" title="View">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    
                ';
                    // <a href="javascript:void(0)" onclick="deleteUser(' . $user->id . ', \'complete\', \'dataTable\')" class="text text-danger" title="Delete">
                    //     <i class="fa-solid fa-trash"></i>
                    //    </a>
                })
                ->filterColumn('postcode', function ($query, $keyword) {
                    $query->whereHas('leadRequests', fn($q) => $q->where('postcode', 'like', "%{$keyword}%"));
                })
                ->filterColumn('services', function ($query, $keyword) {
                    $query->whereHas('leadRequests.category', fn($q) => $q->where('name', 'like', "%{$keyword}%"));
                })
                ->filterColumn('score', function ($query, $keyword) {
                    $query->whereHas('leadRequests', fn($q) => $q->where('credit_score', 'like', "%{$keyword}%"));
                })
                // ->filterColumn('entry_url', function ($query, $keyword) {
                //     $query->where('entry_url', 'like', "%{$keyword}%");
                // })
                // ->filterColumn('user_ip_address', function ($query, $keyword) {
                //     $query->where('user_ip_address', 'like', "%{$keyword}%");
                // })
                ->rawColumns(['services', 'postcode', 'score', 'entry_url', 'user_ip_address', 'status', 'last_login', 'action'])
                ->make(true);
        }

        return view('buyer.index');
    }

    public function testUserCompleteList(Request $request)
    {
        $query = User::with('lastLogin')->whereIn('user_type', [2, 3])
            ->where('form_status', 1)
            ->where(function ($q) {
                $q->where('name', 'like', '%test%')
                    ->orWhere('email', 'like', '%test%');
            })
            ->orderBy('id', 'DESC');

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $testUsers = $query->get();

        return view('buyer.testuser-complete-list', compact('testUsers'));
    }

    public function testUserInCompleteList(Request $request)
    {

        $query = AbandonedUser::whereIn('user_type', [2, 3])
            ->where('form_status', 0)
            ->where(function ($q) {
                $q->where('name', 'like', '%test%')
                    ->orWhere('email', 'like', '%test%');
            })
            ->orderBy('id', 'DESC');

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $testUsers = $query->get();
        // $aRows = AbandonedUser::whereIn('user_type', [2, 3])->where('form_status',0)->orderBy('id','DESC')->get();
        return view('buyer.testuser-incomplete-list', compact('testUsers'));
    }



    public function incompletelist(Request $request)
    {


        if ($request->ajax()) {

            $query = AbandonedUser::with(['leadRequests.category'])
                ->whereIn('user_type', [2, 3])
                ->where('form_status', 0)
                ->where(function ($q) {
                    $q->whereNull('name')
                        ->orWhere('name', 'not like', '%test%');
                })
                ->where(function ($q) {
                    $q->whereNull('email')
                        ->orWhere('email', 'not like', '%test%');
                })
                ->orderBy('id', 'DESC');
            if ($request->from_date && $request->to_date) {
                $query->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            } elseif ($request->from_date) {
                $query->whereDate('created_at', '>=', $request->from_date);
            } elseif ($request->to_date) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('postcode', function ($user) {
                    return $user->zipcode ?? '';
                })
                ->addColumn('services', function ($user) {
                    $service = Category::where('id', $user->service_id)->pluck('name')->first();
                    return $service ?? '';  
                })
                ->addColumn('score', function ($user) {
                    return $user->leadRequests->pluck('credit_score')->implode('<br>');
                })
                ->addColumn('date', function ($user) {
                    return Carbon::parse($user->created_at)->format('m/d/Y h:i a');
                })->filterColumn('date', function ($query, $keyword) {
                    try {
                        $date = Carbon::parse($keyword)->format('Y-m-d');
                        $query->whereDate('created_at', $date);
                    } catch (\Exception $e) {
                    }
                })
                //  ->addColumn('entry_url', function ($user) {
                //     $url = $user->entry_url ?? '';
                //     return '<div style="word-break: break-all; max-width: 200px;">' . e($url) . '</div>';
                // })
                //  ->addColumn('user_ip_address', fn($user) => $user->user_ip_address ?? '')
                ->addColumn('status', fn($user) => 'Incomplete') // Always show Complete
                ->addColumn('action', function ($user) {
                    return '                   
                    <a href="' . route('buyer.show.custom', ['type' => 'abandoned', 'id' => $user->id]) . '" class="text text-primary" title="View">
                        <i class="bi bi-eye"></i>
                    </a>

                     
                ';
                    // <a href="javascript:void(0)" onclick="deleteUser(' . $user->id . ', \'abandoned\', \'dataTable\')" class="text text-danger" title="Delete">
                    //     <i class="fa-solid fa-trash"></i>
                    //    </a>
                })
                ->filterColumn('postcode', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->whereHas('leadRequests', function ($leadQuery) use ($keyword) {
                            $leadQuery->where('postcode', 'like', "%{$keyword}%");
                        })
                            ->orWhere('zipcode', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('services', function ($query, $keyword) {
                    $query->whereHas('leadRequests.category', fn($q) => $q->where('name', 'like', "%{$keyword}%"));
                })
                ->filterColumn('score', function ($query, $keyword) {
                    $query->whereHas('leadRequests', fn($q) => $q->where('credit_score', 'like', "%{$keyword}%"));
                })
                //  ->filterColumn('entry_url', function ($query, $keyword) {
                //     $query->where('entry_url', 'like', "%{$keyword}%");
                // })
                // ->filterColumn('user_ip_address', function ($query, $keyword) {
                //     $query->where('user_ip_address', 'like', "%{$keyword}%");
                // })
                ->rawColumns(['services', 'postcode', 'score', 'entry_url', 'user_ip_address', 'status', 'action'])
                ->make(true);
        }

        return view('buyer.incomplete');
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
    public function show(string $type, string $id)
    {
        $user_id = $id;
        if ($type === 'abandoned') {
            $aRows = AbandonedUser::where('id', $id)->with(['leadRequests.category'])->first();
        } else {
            $aRows = User::where('id', $id)->with(['leadRequests.category'])->first();
        }
        return view('buyer.view', compact('aRows', 'user_id'));
    }

    public function contactForm(Request $request)
    {
        $query = ContactUs::where('user_type', 2)
            ->orderBy('id', 'DESC');

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $aRows = $query->get();

        return view('buyer.contact_form_list', compact('aRows'));
    }

    public function viewContactForm(string $id)
    {
        DB::table('contact_us')
            ->where('id', $id)
            ->update(['status' => 1]);

        $aRows = DB::table('contact_us')->where('user_type', 2)->where('id', $id)->first();
        return view('buyer.contact_view', compact('aRows'));
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




    public function destroy(Request $request, string $id)
    {

        if ($request->type == 'abandoned') {
            \App\Models\AbandonedUser::find($id)?->delete();
            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully.'
            ]);
        }

        // Main user
        \App\Models\User::find($id)?->delete();

        \App\Models\UserAccreditation::where('user_id', $id)->delete();
        \App\Models\UserCardDetail::where('user_id', $id)->delete();
        \App\Models\UserDetail::where('user_id', $id)->delete();
        \App\Models\UserResponseTime::where('seller_id', $id)->delete();
        \App\Models\UserService::where('user_id', $id)->delete();
        \App\Models\UserServiceLocation::where('user_id', $id)->delete();
        \App\Models\ActivityLog::where('from_user_id', $id)->orWhere('to_user_id', $id)->delete();
        \App\Models\Invoice::where('user_id', $id)->delete();
        \App\Models\LeadPrefrence::where('user_id', $id)->delete();
        \App\Models\LeadRequest::where('customer_id', $id)->delete();
        \App\Models\LoginHistory::where('user_id', $id)->delete();
        \App\Models\PlanHistory::where('user_id', $id)->delete();
        \App\Models\ProfileQA::where('user_id', $id)->delete();
        \App\Models\PurchaseHistory::where('user_id', $id)->delete();
        \App\Models\RecommendedLead::where('seller_id', $id)->orWhere('buyer_id', $id)->delete();
        \App\Models\Review::where('user_id', $id)->delete();
        \App\Models\SaveForLater::where('seller_id', $id)->orWhere('user_id', $id)->delete();
        \App\Models\SellerNote::where('seller_id', $id)->orWhere('buyer_id', $id)->delete();
        \App\Models\SuggestedQuestion::where('user_id', $id)->delete();
        \App\Models\UniqueVisitor::where('seller_id', $id)->orWhere('buyer_id', $id)->delete();
        \App\Models\AutobidStatusLog::where('user_id', $id)->delete();
        \App\Models\EmailLog::where('user_id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Record deleted successfully.'
        ]);
    }


    public function deleteContact($id)
    {
        $contact = ContactUs::find($id);
        if ($contact) {
            $contact->delete(); // soft delete
            return redirect()->back()->with('success', 'Contact deleted successfully.');
        }
        return redirect()->back()->with('error', 'Contact not found.');
    }

    public function leadDetails($leadid)
    {
        $aRows =  LeadRequest::where('id', $leadid)->first();
        $user = User::where('id', $aRows->customer_id)->pluck('name')->first();
        return view('buyer.lead_details', get_defined_vars());
    }

    public function buyerBids($userid)
    {
        // $buyerIds = RecommendedLead::where('buyer_id', $userid)->pluck('seller_id')->unique()->toArray();
        $leads = LeadRequest::whereIn('customer_id', [$userid])->orderBy('id', 'DESC')->get();
        // Group all leads by customer_id
        $groupedLeads = $leads->groupBy('customer_id');

        $aRows = [];

        foreach ($groupedLeads as $customerId => $customerLeads) {
            $user = User::find($customerId);

            $aRows[] = [
                'buyer_name' => $user ? $user->name : '',
                'customer_id' => $customerId,
                'leads' => $customerLeads->map(function ($lead) {
                    $lead->service_name = Category::where('id', $lead->service_id)->pluck('name')->first();
                    return $lead;
                })
            ];
        }

        return view('buyer.autobid_leads', compact('aRows'));
    }

    public function buyerLogin($userid)
    {
        $aRows =  LoginHistory::where('user_id', $userid)->orderBy('id', 'DESC')->get();
        $user = User::where('id', $userid)->pluck('name')->first();
        return view('buyer.login_history', get_defined_vars());
    }

    public function viewCount($userid)
    {
        $leadIds = LeadRequest::whereIn('customer_id', [$userid])->pluck('id')->toArray();
        $aRows = UniqueVisitor::where('buyer_id', $userid)
            ->whereIn('lead_id', $leadIds)
            // ->select('buyer_id', 'lead_id', DB::raw('SUM(visitors_count) as total_views'))
            // ->groupBy('buyer_id', 'lead_id')
            ->get();
        foreach ($aRows as $key => $value) {
            $value['leadname'] = LeadRequest::where('id', $value->lead_id)->pluck('postcode')->first();
            $value['seller'] = User::where('id', $value->seller_id)->pluck('name')->first();
        }
        $user = User::where('id', $userid)->pluck('name')->first();
        return view('buyer.view_count', get_defined_vars());
    }
}
