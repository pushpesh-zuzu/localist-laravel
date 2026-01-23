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
use App\Exports\BuyerListExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Zoho\ZohoQuoteCustomers;
use App\Helpers\Zoho\ZohoQuoteRequest;
use App\Helpers\Zoho\ZohoAbandonCustomerQuoteRequest;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoHelper;

class BuyerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_if(!auth()->user()->can('quotecustomers.complete-viewlist'), 403, __('User does not have the right permissions.'));

        if ($request->ajax()) {

            $query = User::with(['leadRequests.category', 'lastLogin'])
                ->whereNull('deleted_at')
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
                ->addColumn('zoho_status', function ($user) {
                    return $user->zoho_record_id
                        ? 'Inserted'
                        : 'Not-inserted';
                })
                ->filterColumn('zoho_status', function ($query, $keyword) {
                    $keyword = trim(strtolower($keyword));
                    $notInsertedKeywords = ['not', 'not-inserted', 'notinserted'];
                    if ($keyword === 'inserted') {
                        $query->whereNotNull('zoho_record_id');
                    } elseif (in_array($keyword, $notInsertedKeywords)) {
                        $query->whereNull('zoho_record_id');
                    }
                })
                ->addColumn('date', function ($user) {
                    return Carbon::parse($user->created_at)->format('d/m/Y h:i a');
                })->filterColumn('date', function ($query, $keyword) {
                    try {
                        $date = Carbon::parse($keyword)->format('Y-m-d');
                        $query->whereDate('created_at', $date);
                    } catch (\Exception $e) {
                    }
                })

                ->addColumn('last_login', function ($user) {
                    return $user->lastLogin?->login_at
                        ? \Carbon\Carbon::parse($user->lastLogin->login_at)->format('d/m/Y h:i A')
                        : '';
                })
                ->filterColumn('last_login', function ($query, $keyword) {
                    // Try: Full datetime search
                    try {
                        $parsed = Carbon::createFromFormat('d/m/Y h:i A', $keyword);
                        return $query->whereHas('lastLogin', function ($q) use ($parsed) {
                            $q->whereDate('login_at', $parsed->toDateString());
                        });
                    } catch (\Exception $e) {
                    }

                    // Try: Date only search
                    try {
                        $parsed = Carbon::createFromFormat('d/m/Y', $keyword);
                        return $query->whereHas('lastLogin', function ($q) use ($parsed) {
                            $q->whereDate('login_at', $parsed->toDateString());
                        });
                    } catch (\Exception $e) {
                    }

                    // Fallback: safe LIKE search (only on DATE part)
                    return $query->whereHas('lastLogin', function ($q) use ($keyword) {
                        $q->whereRaw("DATE(login_at) LIKE ?", ["%{$keyword}%"]);
                    });
                })
                ->addColumn('status', fn($user) => 'Complete')
                ->addColumn('action', function ($user) {
                    $actions = '';

                    if (auth()->user()->can('quotecustomers.complete-sendtozoho')) {
                        if (!$user->zoho_record_id && !empty($user->name) && !empty($user->email)) {
                            $actions .= '<a href="' . route('zoho.send', ['type' => 'complete', 'id' => $user->id]) . '" class="text-primary text-decoration-none" title="Send to Zoho">
                                        <i class="fa-solid fa-cloud-arrow-up"></i> Send to Zoho |
                                    </a>';
                        }
                    }

                    if (auth()->user()->can('quotecustomers.bids')) {
                        $actions .= '<a href="' . route('buyer.buyerBids', $user->id) . '" class="text text-primary" title="Bids">
                                     <i class="fa-solid fa-chess-pawn"></i>
                                    </a>';
                    }

                    if (auth()->user()->can('quotecustomers.unique-visitors')) {
                        $actions .= '
                            <a href="' . route('buyer.viewCount', $user->id) . '" class="text text-primary" title="Unique Visitors">
                                <i class="fa-solid fa-users"></i>
                            </a>
                        ';
                    }

                    if (auth()->user()->can('quotecustomers.loginhistory')) {
                        $actions .= '
                            <a href="' . route('buyer.buyerLogin', $user->id) . '" class="text text-primary" title="Login History">
                                <i class="fa-solid fa-history"></i>
                            </a>
                        ';
                    }

                    if (auth()->user()->can('quotecustomers.view-details')) {
                        $actions .= '
                            <a href="' . route('buyer.show.custom', ['type' => 'complete', 'id' => $user->id]) . '" class="text text-primary" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        ';
                    }

                    if (auth()->user()->can('quotecustomers.complete-user-delete')) {
                        $actions .= '
                        <a href="javascript:void(0)" onclick="deleteUser(' . $user->id . ', \'complete\', \'dataTable\')" class="text text-danger" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                     ';
                    }

                    return $actions;
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
                ->rawColumns(['services', 'postcode', 'score', 'entry_url', 'user_ip_address', 'status', 'last_login', 'action', 'zoho_status'])
                ->make(true);
        }

        return view('buyer.index');
    }

    public function testUserCompleteList(Request $request)
    {
        abort_if(!auth()->user()->can('quotecustomers.test-complete-list'), 403, __('User does not have the right permissions.'));

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

        abort_if(!auth()->user()->can('quotecustomers.quote_test_incomplete_list'), 403, __('User does not have the right permissions.'));

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

        return view('buyer.testuser-incomplete-list', compact('testUsers'));
    }



    public function incompletelist(Request $request)
    {

        abort_if(!auth()->user()->can('quotecustomers.incom-viewlist'), 403, __('User does not have the right permissions.'));

        if ($request->ajax()) {

            $query = AbandonedUser::with(['categoryData'])
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
                ->addColumn('zipcode', function ($user) {
                    return $user->zipcode ?? '';
                })

                ->filterColumn('zipcode', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('zipcode', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('services', function ($user) {
                    return $user->categoryData->name ?? '';
                })
                ->addColumn('zoho_status', function ($user) {
                    return $user->zoho_record_id
                        ? 'Inserted'
                        : 'Not-inserted';
                })
                ->filterColumn('zoho_status', function ($query, $keyword) {
                    $keyword = trim(strtolower($keyword));
                    $notInsertedKeywords = ['not', 'not-inserted', 'notinserted'];
                    if ($keyword === 'inserted') {
                        $query->whereNotNull('zoho_record_id');
                    } elseif (in_array($keyword, $notInsertedKeywords)) {
                        $query->whereNull('zoho_record_id');
                    }
                })
                ->addColumn('date', function ($user) {
                    return Carbon::parse($user->created_at)->format('d/m/Y h:i a');
                })->filterColumn('date', function ($query, $keyword) {
                    try {
                        $date = Carbon::parse($keyword)->format('Y-m-d');
                        $query->whereDate('created_at', $date);
                    } catch (\Exception $e) {
                    }
                })
                ->addColumn('status', fn($user) => 'Incomplete') // Always show Complete
                ->addColumn('action', function ($user) {
                    $actions = '';

                    if (auth()->user()->can('quotecustomers.incom-sendtozoho')) {
                        if (!$user->zoho_record_id && !empty($user->name) && !empty($user->email)) {
                            $actions .= '<a href="' . route('zoho.send', ['type' => 'abandoned', 'id' => $user->id]) . '" class="text-primary text-decoration-none" title="Send to Zoho">
                                            <i class="fa-solid fa-cloud-arrow-up"></i> Send to Zoho |
                                        </a>';
                        }
                    }


                    if (auth()->user()->can('quotecustomers.incom-view-detail')) {
                        $actions .= '
                            <a href="' . route('buyer.show.custom', ['type' => 'abandoned', 'id' => $user->id]) . '" 
                            class="text text-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                        ';
                    }

                    // Example for future use (if you want to add delete)
                    if (auth()->user()->can('quotecustomers.incom-delete')) {
                        $actions .= '
                            <a href="javascript:void(0)" onclick="deleteUser(' . $user->id . ', \'abandoned\', \'dataTable\')" 
                               class="text text-danger" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        ';
                    }

                    return $actions;
                })

                ->filterColumn('services', function ($query, $keyword) {
                    $query->whereHas('categoryData', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['services', 'zipcode', 'zoho_status', 'status', 'action'])
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
            abort_if(!auth()->user()->canAny(['quotecustomers.quote_test_incomplete_view', 'quotecustomers.incom-view-detail']), 403, __('User does not have the right permissions.'));
            $aRows = AbandonedUser::where('id', $id)->with(['leadRequests.category'])->first();
        } else {
            abort_if(!auth()->user()->canAny(['quotecustomers.view-details', 'quotecustomers.test-complete-view-details']), 403, __('User does not have the right permissions.'));
            $aRows = User::where('id', $id)->with(['leadRequests.category'])->first();
        }
        return view('buyer.view', compact('aRows', 'user_id'));
    }

    public function contactForm(Request $request)
    {
        abort_if(!auth()->user()->can('quotecustomers.conatct-viewlist'), 403, __('User does not have the right permissions.'));

        $query = ContactUs::where('user_type', 1)
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
        abort_if(!auth()->user()->can('quotecustomers.contact-view-details'), 403, __('User does not have the right permissions.'));

        DB::table('contact_us')
            ->where('id', $id)
            ->update(['status' => 1]);

        $aRows = DB::table('contact_us')->where('user_type', 1)->where('id', $id)->first();
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
        abort_if(!auth()->user()->canAny(['quotecustomers.contact-delete', 'leadbuyerscontact.contact-delete']), 403, __('User does not have the right permissions.'));
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
        abort_if(!auth()->user()->canAny(['quotecustomers.bids', 'quotecustomers.test_complete_bids']), 403, __('User does not have the right permissions.'));
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
        abort_if(!auth()->user()->canAny(['quotecustomers.loginhistory', 'quotecustomers.test_complete_loginhistory']), 403, __('User does not have the right permissions.'));
        $aRows =  LoginHistory::where('user_id', $userid)->orderBy('id', 'DESC')->get();
        $user = User::where('id', $userid)->pluck('name')->first();
        return view('buyer.login_history', get_defined_vars());
    }

    public function viewCount($userid)
    {
        abort_if(!auth()->user()->canAny(['quotecustomers.unique-visitors', 'quotecustomers.test-complete-unique-visitors']), 403, __('User does not have the right permissions.'));
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


    public function exportBuyerExcelList(Request $request)
    {
        $fromDate = $request->start_date ?? null;
        $toDate = $request->end_date ?? null;
        $search = $request->search ?? null;
        $type = $request->type ?? null;


        if ($type == 'customer-complete-list') {
            $fileName = 'Quote Customers - Complete List' . '.xlsx';
        } elseif ($type == 'customer-incomplete-list') {
            $fileName = 'Quote Customers - Incomplete List' . '.xlsx';
        } elseif ($type == 'customer-testcomplete-list') {
            $fileName = 'Quote Customers - Test Complete List' . '.xlsx';
        } elseif ($type == 'customer-testincomplete-list') {
            $fileName = 'Quote Customers - Test Incomplete List' . '.xlsx';
        }

        return Excel::download(new BuyerListExport($fromDate, $toDate, $search, $type), $fileName);
    }

    public function exportBuyerCsvList(Request $request)
    {
        $fromDate = $request->start_date ?? null;
        $toDate = $request->end_date ?? null;
        $search = $request->search ?? null;
        $type = $request->type ?? null;

        if ($type == 'customer-complete-list') {
            $fileName = 'Quote Customers - Complete List' . '.csv';
        } elseif ($type == 'customer-incomplete-list') {
            $fileName = 'Quote Customers - Incomplete List' . '.csv';
        } elseif ($type == 'customer-testcomplete-list') {
            $fileName = 'Quote Customers - Test Complete List' . '.csv';
        } elseif ($type == 'customer-testincomplete-list') {
            $fileName = 'Quote Customers - Test Incomplete List' . '.csv';
        }

        return Excel::download(new BuyerListExport($fromDate, $toDate, $search, $type), $fileName);
    }



    public function sendToZoho($type = null, $userId)
    {
        try {
            // 🔥 Call Zoho integration
            $response = $type === 'abandoned'
                ? app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($userId, $type)
                : app(ZohoQuoteCustomers::class)->integrateQuoteCustomer($userId);


            // Validate response
            if (!isset($response['data'][0])) {
                \Log::error('Zoho Response Missing Data', compact('type', 'id', 'response'));
                return back()->with('error', 'Invalid Zoho response.');
            }

            $responseData = $response['data'][0];

            // 🔥 Success Condition
            if (
                isset($responseData['status']) &&
                $responseData['status'] === 'success' &&
                isset($responseData['details']['id'])
            ) {
                $zohoRecordId = $responseData['details']['id'];

                // 🔥 Update correct table
                if ($type === 'abandoned') {

                    // Update abandoned user record
                    AbandonedUser::where('id', $userId)->update([
                        'zoho_record_id' => $zohoRecordId
                    ]);

                    $user = AbandonedUser::find($userId); // cleaner

                    $zohoAbandonedQuoteId = $user->zoho_abandoned_quote_request_id ?? null;


                    // Delete old abandoned quote request
                    CustomHelper::runInBackground(function () use ($zohoAbandonedQuoteId, $userId) {
                        if (!empty($zohoAbandonedQuoteId)) {
                            app(ZohoAbandonCustomerQuoteRequest::class)
                                ->deleteAbandonedQuoteRequest($zohoAbandonedQuoteId, $userId);
                        }
                    });

                    // Create new integrate abandoned quote request
                    CustomHelper::runInBackground(function () use ($userId) {
                        app(ZohoAbandonCustomerQuoteRequest::class)
                            ->integrateAbandonQuoteRequest($userId);
                    });
                } else {

                    // Update normal user record
                    User::where('id', $userId)->update([
                        'zoho_record_id' => $zohoRecordId
                    ]);


                    $leadRequestArr = LeadRequest::with(['customer', 'category'])
                        ->where('customer_id', $userId)
                        ->get();

                    if ($leadRequestArr->isNotEmpty()) {

                        foreach ($leadRequestArr as $lrequest) {

                            $leadId  = $lrequest->id;
                            $zohoId  = $lrequest->zoho_quote_request_id;

                            CustomHelper::runInBackground(function () use ($userId, $leadId, $zohoId) {

                                if (!empty($zohoId)) {
                                    app(ZohoQuoteRequest::class)
                                        ->updateZohoQuoteAssignToCustomer($userId, $leadId);
                                } else {
                                    app(ZohoQuoteRequest::class)
                                        ->integrateQuoteRequest($userId, $leadId);
                                }

                                app(ZohoQuoteRequest::class)->updateZohoQuoteStatus($leadId);
                            });
                        }
                    }
                }



                $responseData = $response['data'][0] ?? null;
                $errorMessage = $response['data'][0]['message'] ?? null;

                $dbRecordId = $userId;           // jo record update ho raha hai
                $dbTable    = $type === 'abandoned' ? 'abandoned_users' : 'users';

                // Call ZohoHelper log
                ZohoHelper::logZohoRequest(
                    'sendToZoho',
                    'https://www.zohoapis.eu/crm/v2/Quote_Customers/upsert',
                    null,             // payload
                    $responseData,    // response
                    $errorMessage,
                    $userId ?? null,    // error message               
                    $dbRecordId,      // database record ID
                    $dbTable          // database table name
                );


                return back()->with('success', 'User pushed to Zoho successfully.');
            }



            return back()->with('error', 'Zoho push failed. Check logs.');
        } catch (\Throwable $e) {



            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
