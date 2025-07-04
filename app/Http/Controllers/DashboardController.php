<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\RecommendedLead;
use App\Models\ActivityLog;
use App\Models\UserService;
use App\Models\Blog;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Helpers\CustomHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $now = Carbon::now()->toDateString();
         // CustomHelper::sendEmail(array("to" => "webplanetsoft@gmail.com","subject" => "test", "body" => "this is test body",'receiver' => "Ankit "));

        $totalusers = User::count();
        $totalsellers = User::where('user_type',[1, 3])->count();
        $totalbuyer = User::where('user_type',[2, 3])->count();
        $inactiveusers = User::where('status',0)->count();
        $activeusers = User::where('status',1)->count();
        $totalcategories = Category::count();
        $totalblogs = Blog::count();


        //---------Athira Code----------------------------------------------//

        $leadStats = RecommendedLead::selectRaw('COUNT(*) as total_hired, SUM(bid) as total_bid')
        ->where('status', 'hired')
        ->first();

        $sellerTotalHired=$leadStats->total_hired ?? 0;
        $sellerTotalBid=$leadStats->total_bid ?? 0;

        $activeSellerStats = $this->getActiveInActiveSellersCount();
        $dailyActiveSellers = $activeSellerStats['daily_active'] ?? 0;
        $dailyInActiveSellers = $activeSellerStats['daily_inactive'] ?? 0;
        $monthlyActiveSellers = $activeSellerStats['monthly_active'] ?? 0;
        $monthlyInActiveSellers = $activeSellerStats['monthly_inactive'] ?? 0;
        $quarterlyActiveSellers = $activeSellerStats['quarterly_active'];
        $quarterlyInActiveSellers = $activeSellerStats['quarterly_inactive'];
        $yearlyActiveSellers = $activeSellerStats['yearly_active'];
        $yearlyInActiveSellers = $activeSellerStats['yearly_inactive'];

        $categoryUserCounts = Category::withCount(['userServices as user_count'])
        ->having('user_count', '!=', 0)
        ->get()
        ->map(function ($item) {
            return [
                'category_name' => $item->name,
                'user_count' => $item->user_count,
                'category_id' => $item->id,
            ];
        });

        $categoriesWithAvgCredit = Category::withCount([
            'leadRequests as lead_requests_count' => function ($query) {
                $query->where('credit_score', '>', 0);
            }
        ])
        ->withSum([
            'leadRequests as total_credit_score' => function ($query) {
                $query->where('credit_score', '>', 0);
            }
        ], 'credit_score')
        ->get()
        ->map(function ($category) {
            $average = $category->lead_requests_count > 0
                ? round($category->total_credit_score / $category->lead_requests_count, 2)
                : 0;

            return [
                'category_name' => $category->name,
                'average_credit_score' => $average,
            ];
        })
        ->filter(fn ($cat) => $cat['average_credit_score'] > 0)
        ->values();

        $totalCreditsSold = LeadRequest::where('credit_score', '>', 0)
        ->where('status', 'hired')
        ->whereHas('customer',function($query){
            $query->whereIn('user_type',[2, 3]);
        })
        ->sum('credit_score');

       $leadBuyersCount = User::whereIn('user_type', [2, 3])
        ->where('form_status',1) 
        ->count();


        $abandonedSignUp = User::where('user_type',[2, 3])->where('form_status', 0)
        ->count();

        $valueleadsSold = LeadRequest::where('credit_score', '>', 0)
        ->where('status','hired')
        ->whereHas('customer',function($query){
            $query->whereIn('user_type',[2, 3]);
        })
        ->sum('credit_score');


        $leadsSold = LeadRequest::where('credit_score', '>', 0)
        ->where('status','hired')
        ->whereHas('customer',function($query){
            $query->whereIn('user_type',[2, 3]);
        })
        ->count();

        $leadsUnSold = LeadRequest::where('credit_score', '>', 0)
        ->whereIn('status',['pending','new'])
        ->whereHas('customer',function($query){
            $query->whereIn('user_type',[2, 3]);
        })
        ->count();
        $totalLeads = $leadsSold + $leadsUnSold;

        $percentageSold = $totalLeads > 0 ? round(($leadsSold / $totalLeads) * 100, 2) : 0;
        $percentageUnSold = $totalLeads > 0 ? round(($leadsUnSold / $totalLeads) * 100, 2) : 0;

        $valleadsUnSold = LeadRequest::where('credit_score', '>', 0)
        ->whereIn('status',['pending','new'])
        ->whereHas('customer',function($query){
            $query->whereIn('user_type',[2, 3]);
        })
        ->sum('credit_score');


        $dailyCreditsSold = LeadRequest::whereDate('created_at', $now)
        ->where('credit_score', '>', 0)
        ->where('status','hired')
        ->whereHas('customer',function($query){
            $query->whereIn('user_type',[2, 3]);
        })
        ->count();



        $monthlyCreditsSold = LeadRequest::whereBetween('created_at', [
            Carbon::now()->startOfMonth()->toDateString(),
            Carbon::now()->endOfMonth()->toDateString()
        ])->where('credit_score', '>', 0)->where('status','hired')
        ->whereHas('customer',function($query){
            $query->whereIn('user_type',[2, 3]);
        })->count();

        $quarterlyCreditsSold = LeadRequest::whereBetween('created_at', [
            Carbon::now()->startOfQuarter()->toDateString(),
            Carbon::now()->endOfQuarter()->toDateString()
        ])->where('credit_score', '>', 0)->where('status','hired')
        ->whereHas('customer',function($query){
            $query->whereIn('user_type',[2, 3]);
        })
        ->count();

        $yearlyCreditsSold = LeadRequest::whereBetween('created_at', [
            Carbon::now()->startOfYear()->toDateString(),
            Carbon::now()->endOfYear()->toDateString()
        ])->where('credit_score', '>', 0)->where('status','hired')
        ->whereHas('customer',function($query){
            $query->whereIn('user_type',[2, 3]);
        })
        ->count();

        $activeBuyers = $this->getActiveBuyers();
        $dailyActiveBuyers = $activeBuyers['dailyActiveBuyers'] ?? 0;
        $monthlyActiveBuyers = $activeBuyers['monthlyActiveBuyers'] ?? 0;
        $quarterlyActiveBuyers = $activeBuyers['quarterlyActiveBuyers'] ?? 0;
        $yearlyActiveBuyers = $activeBuyers['yearlyActiveBuyers'] ?? 0;


        return view('dashboard',get_defined_vars());
    }

    /**
     * Show Active Buyers
     */

    private function getActiveBuyers(){

        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfQuarter = $now->copy()->firstOfQuarter();
        $startOfYear = $now->copy()->startOfYear();


        $dailyActiveBuyers = User::whereIn('user_type', [2, 3])
            ->whereHas('hiredLeads', function ($q) use ($startOfDay) {
                $q->where('created_at', '>=', $startOfDay);
            })
            ->count();


        $monthlyActiveBuyers = User::whereIn('user_type', [2, 3])
            ->whereHas('hiredLeads', function ($q) use ($startOfMonth) {
                $q->where('created_at', '>=', $startOfMonth);
            })
            ->count();


        $quarterlyActiveBuyers = User::whereIn('user_type', [2, 3])
            ->whereHas('hiredLeads', function ($q) use ($startOfQuarter) {
                $q->where('created_at', '>=', $startOfQuarter);
            })
            ->count();


        $yearlyActiveBuyers = User::whereIn('user_type', [2, 3])
            ->whereHas('hiredLeads', function ($q) use ($startOfYear) {
                $q->where('created_at', '>=', $startOfYear);
            })
            ->count();

             return [
                'dailyActiveBuyers' => $dailyActiveBuyers,
                'monthlyActiveBuyers' => $monthlyActiveBuyers,
                'quarterlyActiveBuyers' => $quarterlyActiveBuyers,
                'yearlyActiveBuyers' => $yearlyActiveBuyers,
            ];
        }

    /**
     * Show Active and Inactive  Sellers
     */
    private function getActiveInActiveSellersCount()
    {
        $now = Carbon::now();
        $dayStart = $now->copy()->startOfDay()->toDateString();
        $dayEnd = $now->copy()->endOfDay()->toDateString();

        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $yearStart = $now->copy()->startOfYear()->toDateString();
        $yearEnd = $now->copy()->endOfYear()->toDateString();

        $quarterStart = $now->copy()->startOfQuarter()->toDateString();
        $quarterEnd = $now->copy()->endOfQuarter()->toDateString();

        $allSellers = User::where('user_type', 1)->pluck('id')->toArray();

        $getActiveSellers = function ($start, $end) use ($allSellers) {
            $from = ActivityLog::select('from_user_id as user_id')
                ->whereRaw("DATE(created_at) BETWEEN ? AND ?", [$start, $end])
                ->whereIn('from_user_id', $allSellers)
                ->get();

            $to = ActivityLog::select('to_user_id as user_id')
                ->whereRaw("DATE(created_at) BETWEEN ? AND ?", [$start, $end])
                ->whereIn('to_user_id', $allSellers)
                ->get();




            $combined = $from->merge($to);
            $activeUserIds = collect($combined)->pluck('user_id')->filter()->unique()->toArray();

            return [
                'active' => count($activeUserIds),
                'inactive' => count(array_diff($allSellers, $activeUserIds)),
            ];
        };

        return [
            'daily_active' => ($daily = $getActiveSellers($dayStart, $dayEnd))['active'],
            'daily_inactive' => $daily['inactive'],

            'monthly_active' => ($monthly = $getActiveSellers($monthStart, $monthEnd))['active'],
            'monthly_inactive' => $monthly['inactive'],

            'quarterly_active' => ($quarter = $getActiveSellers($quarterStart, $quarterEnd))['active'],
            'quarterly_inactive' => $quarter['inactive'],

            'yearly_active' => ($yearly = $getActiveSellers($yearStart, $yearEnd))['active'],
            'yearly_inactive' => $yearly['inactive'],
        ];
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
