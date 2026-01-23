<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
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
                'users.city as city',
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
                'users.city as city',
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
                'users.name',
                'lead_requests.id',
                'lead_requests.city',
                'lead_requests.postcode',
                'postcodes.latitude',
                'postcodes.longitude'
            )
            ->where('lead_requests.status', '<>', 'hired')
            ->where('lead_requests.created_at', '>', Carbon::now()->subDays(21)->toDateString()); // do not include leads which are older than 21 days
        $leadSlotCount = CustomHelper::setting_value("lead_slot_count", 5);
        $slotFullLeads = DB::table('recommended_leads')
            ->select('lead_id')
            ->groupBy('lead_id')
            ->havingRaw('COUNT(*) >= ?', [$leadSlotCount])
            ->pluck('lead_id')
            ->toArray();
        if(!empty($slotFullLeads)){
            $leadsQuery = $leadsQuery->whereNotIn('lead_requests.id', $slotFullLeads); //do not include leads which has 5 slot full
        }
        

        // $creditBuyers = $crediBuyersQuery->get()->toArray();
        $creditBuyers = $crediBuyersQuery->get()->map(function ($buyer) {
            $buyer->profile_link = url(
                rtrim(config('app.react_base_url'), '/')
                . '/view-profile/'
                . strtolower(preg_replace('/\s+/', '-', trim($buyer->name)))
                . '/'
                . $buyer->id
            );

            return $buyer;
        })->toArray();
        $noCreditBuyers = $noCreditBuyersQuery->get()->map(function ($buyer) {
            $buyer->profile_link = url(
                rtrim(config('app.react_base_url'), '/')
                . '/view-profile/'
                . strtolower(preg_replace('/\s+/', '-', trim($buyer->name)))
                . '/'
                . $buyer->id
            );

            return $buyer;
        })->toArray();
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


    public function exportCsv(Request $request)
    {
        // Reuse existing data logic
        $response = $this->data($request)->getData(true);

        $filename = 'service_map_' . now()->format('d-m-Y_H-i-s') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        return response()->stream(function () use ($response) {
            $out = fopen('php://output', 'w');

            // ---- CSV HEADER ----
            fputcsv($out, [
                'Type',                // credit_buyer | no_credit_buyer | lead
                'ID',
                'Name',
                'City',
                'Postcode',
                'Total Credit',
                'Latitude',
                'Longitude',
                'Profile Link',
            ]);

            // ---- CREDIT BUYERS ----
            foreach ($response['crediBuyers'] ?? [] as $b) {
                fputcsv($out, [
                    'credit_buyer',
                    $b['id'] ?? '',
                    $b['name'] ?? '',
                    $b['city'] ?? '',
                    $b['zipcode'] ?? '',
                    $b['total_credit'] ?? '',
                    $b['latitude'] ?? '',
                    $b['longitude'] ?? '',
                    $b['profile_link'] ?? '',
                ]);
            }

            // ---- NO CREDIT BUYERS ----
            foreach ($response['noCreditBuyers'] ?? [] as $b) {
                fputcsv($out, [
                    'no_credit_buyer',
                    $b['id'] ?? '',
                    $b['name'] ?? '',
                    $b['city'] ?? '',
                    $b['zipcode'] ?? '',
                    $b['total_credit'] ?? '0',
                    $b['latitude'] ?? '',
                    $b['longitude'] ?? '',
                    $b['profile_link'] ?? '',
                ]);
            }

            // ---- LEADS ----
            foreach ($response['leads'] ?? [] as $l) {
                fputcsv($out, [
                    'lead',
                    $l['id'] ?? '',
                    $l['name'] ?? '',
                    $l['city'] ?? '',
                    $l['postcode'] ?? '',
                    '',
                    $l['latitude'] ?? '',
                    $l['longitude'] ?? '',
                    '',
                ]);
            }

            fclose($out);
        }, 200, $headers);
    }


}
