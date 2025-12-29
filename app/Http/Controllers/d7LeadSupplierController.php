<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\D7LeadSupplier;
use Yajra\Datatables\Datatables;
use App\Exports\d7LeadSupplierListExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class d7LeadSupplierController extends Controller
{

    public function d7LeadSupplierList(Request $request)
    {

        abort_if(!auth()->user()->can('d7leadsuppliers.viewlist'), 403, __('User does not have the right permissions.'));

        if ($request->ajax()) {

            $query = D7LeadSupplier::query();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('created_at', function ($user) {
                    return Carbon::parse($user->created_at)->format('m/d/Y h:i a');
                })
                ->rawColumns(['created_at'])
                ->make(true);
        }

        return view('d7supplier.index');
    }


    public function exportd7LeadSupplierExcel(Request $request)
    {
        $fileName = 'D7 Lead Supplier List.xlsx';

        return Excel::download(new d7LeadSupplierListExport(), $fileName);
    }

    public function exportd7LeadSupplierCsv(Request $request)
    {
        $fileName = 'D7 Lead Supplier List.csv';

        return Excel::download(new d7LeadSupplierListExport(), $fileName);
    }


    public function testZeptoMail($leadId)
    {
       
        $suppliers = [
            [
                'email' => 'ashishg@zuzucodes.com',
                'name'  => 'Test',
            ],
            [
                'email' => 'abuzer@zuzucodes.com',
                'name'  => 'New Supplier',
            ]
        ];

        $keyword = 'Driveway';
        $city    = 'London';
        $country = 'UK';

        $questionsAndAnswers = [
            [
                'question' => 'What type of property do you have?',
                'answer' => 'Business or Commercial Premises'
            ],
            [
                'question' => 'What kind of commercial building is it?',
                'answer' => 'Healthcare / Medical center'
            ],
            [
                'question' => 'What type of driveway work are you looking to have done?',
                'answer' => 'Add a driveway to a existing property'
            ],
            [
                'question' => 'How many vehicles should the new driveway accommodate?',
                'answer' => 'Space for 4 or more vehicles'
            ],
            [
                'question' => 'What driveway materials are you considering for the new driveway?',
                'answer' => 'Resin - bound surface, Asphalt - Tarmac'
            ],
            [
                'question' => 'Do you require any additional services?',
                'answer' => 'Outdoor lighting, Garden or landscape work'
            ],
            [
                'question' => 'When should the work begin?',
                'answer' => 'Within a week'
            ],
            [
                'question' => 'How likely are you to hire a professional?',
                'answer' => "I'm certain I'll be hiring someone"
            ]
        ];

        // Call the ZeptoMail helper
        app(\App\Helpers\Zoho\ZeptoMail::class)->sendMailToD7Supplier(
            $suppliers,
            $keyword,
            $city,
            $country,
            $leadId,
            $questionsAndAnswers
        );

        return "Test mail sent for Lead ID: $leadId. Check EmailLog for response.";
    }
}
