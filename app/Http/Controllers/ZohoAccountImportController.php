<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\Zoho\ZohoImportService;

class ZohoAccountImportController extends Controller
{

    public function importZoho(Request $request)
    {
        return view('d7supplier.import-account');
    }


    public function importZohoAccounts(Request $request)
    {
        try {


            $request->validate([
                'file' => 'required|file|mimetypes:text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]);


            $data = Excel::toArray([], $request->file('file'))[0];

            if (empty($data) || count($data) < 2) {
                return response()->json(['error' => 'Empty file'], 400);
            }


            $header = array_map('strtolower', $data[0]);
            unset($data[0]);

            $rows = [];

            foreach ($data as $row) {
                $rows[] = array_combine($header, $row);
            }


            $result = app(ZohoImportService::class)->importAccountsWithRelated($rows);

            return response()->json([
                'status' => 'Import completed',
                'stats' => $result
            ]);
        } catch (\Exception $e) {

            Log::error('Zoho Import Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Import failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
