<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MapController extends Controller
{
    /**
     * Show map view.
     */
    public function index()
    {
        return view('service-map.index');
    }

    /**
     * Return JSON data for buyers and leads.
     *
     * This fixes ambiguous column errors by qualifying columns and
     * joins using a closure with DB::raw(REPLACE(...)).
     */
    public function data(Request $request)
    {
        // Optional: bounding box to limit results for performance.
        // If frontend sends bbox (southWestLat, southWestLng, northEastLat, northEastLng),
        // uncomment and use in whereBetween. For now we return a limited dataset.
        $limit = 2000; // safe default; lower if you have many rows

        // BUYERS: user_type IN (1,3)
        $buyersQuery = DB::table('users')
            ->whereIn('users.user_type', [1, 3])
            ->whereNotNull('users.zipcode')
            // join postcodes on normalized postcode (remove spaces)
            ->join('postcodes', function ($join) {
                // join where REPLACE(users.zipcode,' ','') = postcodes.postcode
                $join->on(DB::raw("REPLACE(users.zipcode, ' ', '')"), '=', DB::raw('postcodes.postcode'));
            })
            ->whereNotNull('postcodes.latitude')
            ->whereNotNull('postcodes.longitude')
            ->select([
                'users.id as id',
                'users.name as name',
                'users.zipcode as zipcode',
                'users.total_credit as total_credit',
                'postcodes.latitude as latitude',
                'postcodes.longitude as longitude',
            ])
            ->limit($limit);

        // If you want to paginate or filter by bbox, apply here to $buyersQuery.

        $buyers = $buyersQuery->get();

        // LEADS: join lead_requests -> postcodes
        $leadsQuery = DB::table('lead_requests')
            ->whereNotNull('lead_requests.postcode')
            ->join('postcodes', function ($join) {
                $join->on(DB::raw("REPLACE(lead_requests.postcode, ' ', '')"), '=', DB::raw('postcodes.postcode'));
            })
            ->whereNotNull('postcodes.latitude')
            ->whereNotNull('postcodes.longitude')
            ->select([
                'lead_requests.id as id',
                'lead_requests.postcode as postcode',
                'postcodes.latitude as latitude',
                'postcodes.longitude as longitude',
                // optionally add status/created_at if needed:
                //'lead_requests.status as status',
                //'lead_requests.created_at as created_at',
            ])
            ->limit($limit);

        $leads = $leadsQuery->get();

        return response()->json([
            'buyers' => $buyers,
            'leads'  => $leads,
        ]);
    }
}
