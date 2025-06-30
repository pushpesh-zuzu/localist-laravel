<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\RecommendedLead;
use App\Models\ActivityLog;
use App\Models\UserService;
use App\Models\Blog;
use App\Models\Category;
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



        return view('dashboard',get_defined_vars());
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
