<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CustomHelper;
use App\Models\Postcode;

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
        // sleep(20); // simulate delay for testing

        // BUYERS: user_type IN (1,3) who have credits and valid postcode
        $crediBuyersQuery = DB::table('users')
            ->where('users.zipcode', '<>', '')
            ->leftJoin('postcodes', 'users.zipcode', '=', 'postcodes.postcode')
            ->select(
                'users.id as id',
                'users.name as name',
                'users.zipcode as zipcode',
                'users.total_credit as total_credit',
                'postcodes.latitude as latitude',
                'postcodes.longitude as longitude'
            )
            ->where('users.total_credit', '>', 10)
            ->whereIn('users.user_type', [1, 3]);
        
        // BUYERS: user_type IN (1,3) who does not have credits and valid postcode
        $noCreditBuyersQuery = DB::table('users')
            ->where('users.zipcode', '<>', '')
            ->leftJoin('postcodes', 'users.zipcode', '=', 'postcodes.postcode')
            ->select(
                'users.id as id',
                'users.name as name',
                'users.zipcode as zipcode',
                'users.total_credit as total_credit',
                'postcodes.latitude as latitude',
                'postcodes.longitude as longitude'
            )
            ->where('users.total_credit', '=', 0)
            ->whereIn('users.user_type', [1, 3]);
        // If you want to paginate or filter by bbox, apply here to $buyersQuery.

        // LEADS: join lead_requests -> postcodes
        $leadsQuery = DB::table('lead_requests')
            ->join('users', 'lead_requests.customer_id', '=', 'users.id') // INNER JOIN as in your SQL
            ->leftJoin('postcodes', 'lead_requests.postcode', '=', 'postcodes.postcode')
            ->select(
                'lead_requests.id',
                'lead_requests.city',
                'lead_requests.postcode',
                'postcodes.latitude',
                'postcodes.longitude'
            )
            ->where('lead_requests.status', '<>', 'hired');

        $creditBuyers = $crediBuyersQuery->get()->toArray();
        $noCreditBuyers = $noCreditBuyersQuery->get()->toArray();
        $leads = $leadsQuery->get()->toArray();

        $this->fillMissingCoordinates($creditBuyers);
        $this->fillMissingCoordinates($noCreditBuyers);
        $this->fillMissingCoordinates($leads);

        // $creditBuyersMissingGeo = array_filter($creditBuyers, function ($row) {
        //     return empty($row->latitude) || empty($row->longitude);
        // });

        // $noCreditBuyersMissingGeo = array_filter($noCreditBuyers, function ($row) {
        //     return empty($row->latitude) || empty($row->longitude);
        // });

        // $leadsMissingGeo = array_filter($leads, function ($row) {
        //     return empty($row->latitude) || empty($row->longitude);
        // });

        // echo "<pre>";
        // print_r($creditBuyersMissingGeo);
        // print_r($noCreditBuyersMissingGeo);
        // print_r($leadsMissingGeo);

        
        // die();

        return response()->json([
            'crediBuyers' => $creditBuyers,
            'creditBuyersCount' => count($creditBuyers),
            'noCreditBuyers' => $noCreditBuyers,
            'noCreditBuyersCount' => count($noCreditBuyers),
            'leads'  => $leads,
            'leadsCount' => count($leads)
        ]);
    }


    private function fillMissingCoordinates(array &$items)
    {
        foreach ($items as &$row) {
            $postcode = $row->postcode ?? $row->zipcode ?? null;
            if (!$postcode) continue;

            $lat = $row->latitude ?? null;
            $lng = $row->longitude ?? null;

            // missing = null, empty string, or zero values
            $isMissing = $lat === null || $lng === null ||
                        trim((string)$lat) === '' ||
                        trim((string)$lng) === '' ||
                        $lat == 0 || $lng == 0;

            if (!$isMissing) continue;

            // First check DB
            $db = Postcode::where('postcode', $postcode)->first();

            if ($db && $db->latitude && $db->longitude && $db->latitude != 0 && $db->longitude != 0) {
                // use DB data instead of Google
                $row->latitude = $db->latitude;
                $row->longitude = $db->longitude;
                continue;
            }

            // Otherwise, call Google API ONCE
            $coordsJson = CustomHelper::getCoordinates($postcode);
            if (!$coordsJson) continue;

            $coords = json_decode($coordsJson, true);
            if (!isset($coords['lat'], $coords['lng'])) continue;

            $latVal = (float)$coords['lat'];
            $lngVal = (float)$coords['lng'];

            if ($latVal == 0 || $lngVal == 0) continue; // reject invalid values

            // assign to array
            $row->latitude = $latVal;
            $row->longitude = $lngVal;

            // store in DB properly
            Postcode::updateOrCreate(
                ['postcode' => $postcode],
                ['latitude' => $latVal, 'longitude' => $lngVal]
            );
        }
    }

}
