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
use DB;



class BuyerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $query = User::whereIn('user_type', [2, 3])
            ->where('form_status', 1)
            // ->where(function ($q) {
            //     $q->where('name', 'not like', '%test%')
            //         ->where('email', 'not like', '%test%');
            // })
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
        //  $aRows = User::whereIn('user_type', [2, 3])->where('form_status',1)->orderBy('id','DESC')->get();
        return view('buyer.index', compact('aRows'));
    }

    public function completeListOfTestUsers(Request $request)
    {
        $query = User::whereIn('user_type', [2, 3])
            ->where('form_status', 1)
            // ->where(function ($q) {
            //     $q->where('name', 'like', '%test%')
            //         ->orWhere('email', 'like', '%test%');
            // })
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

        return view('buyer.test-list-of-complete', compact('testUsers'));
    }


    public function incompletelist(Request $request)
    {

        $query = AbandonedUser::whereIn('user_type', [2, 3])
            ->where('form_status', 0)
            // ->where(function ($q) {
            //     $q->where('name', 'not like', '%test%')
            //         ->where('email', 'not like', '%test%');
            // })
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
        // $aRows = AbandonedUser::whereIn('user_type', [2, 3])->where('form_status',0)->orderBy('id','DESC')->get();
        return view('buyer.incomplete', compact('aRows'));
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
        // $aRows = DB::table('contact_us')->where('user_type', 2)->orderBy('id','DESC')->get();


        $query = DB::table('contact_us')->where('user_type', 2)
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
        // dd($user);
        if ($request->type == 'abandoned') {
            \DB::table('abandoned_users')->where('id', $id)->delete();
            return redirect()->route('buyer.incompletelist')
                ->with('success', 'Abandoned Buyer deleted successfully.');
        }

        \DB::table('users')->where('id', $id)->delete();
        \DB::table('user_accreditations')->where('user_id', $id)->delete();
        \DB::table('user_card_details')->where('user_id', $id)->delete();
        \DB::table('user_details')->where('user_id', $id)->delete();
        \DB::table('user_response_times')->where('seller_id', $id)->delete();
        \DB::table('user_services')->where('user_id', $id)->delete();
        \DB::table('user_service_locations')->where('user_id', $id)->delete();
        \DB::table('activity_logs')->where('from_user_id', $id)->orWhere('to_user_id', $id)->delete();
        \DB::table('invoices')->where('user_id', $id)->delete();
        \DB::table('lead_prefrences')->where('user_id', $id)->delete();
        \DB::table('lead_requests')->where('customer_id', $id)->delete();
        \DB::table('login_histories')->where('user_id', $id)->delete();
        \DB::table('plan_histories')->where('user_id', $id)->delete();
        \DB::table('profile_q_a_s')->where('user_id', $id)->delete();
        \DB::table('purchase_histories')->where('user_id', $id)->delete();
        \DB::table('recommended_leads')->where('seller_id', $id)->orWhere('buyer_id', $id)->delete();
        \DB::table('reviews')->where('user_id', $id)->delete();
        \DB::table('save_for_laters')->where('seller_id', $id)->orWhere('user_id', $id)->delete();
        \DB::table('seller_notes')->where('seller_id', $id)->orWhere('buyer_id', $id)->delete();
        \DB::table('suggested_questions')->where('user_id', $id)->delete();
        \DB::table('unique_visitors')->where('seller_id', $id)->orWhere('buyer_id', $id)->delete();
        \DB::table('autobid_status_logs')->where('user_id', $id)->delete();
        \DB::table('email_logs')->where('user_id', $id)->delete();
        return redirect()->route('buyer.index')
            ->with('success', 'Buyer deleted successfully.');
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
