<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Models\Invoice;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class PurchaseInvoiceHistoryController extends Controller
{

    public function purchaseInvoiceHistoryList(Request $request)
    {
        abort_if(!auth()->user()->can('invoicehistory.viewlist'), 403);

        if ($request->ajax()) {

            $query = Invoice::with('user')->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('user_name', function ($invoice) {
                    return $invoice->user->name ?? '-';
                })

                ->addColumn('user_email', function ($invoice) {
                    return $invoice->user->email ?? '-';
                })

                ->addColumn('amount', function ($invoice) {
                    return '£' . ($invoice->amount ?? '-');
                })->filterColumn('amount', function ($query, $keyword) {
                    $query->where('amount', 'like', "%{$keyword}%");
                })

                ->addColumn('vat', function ($invoice) {
                    return '£' . ($invoice->vat ?? '-');
                })->filterColumn('vat', function ($query, $keyword) {
                    $query->where('vat', 'like', "%{$keyword}%");
                })
                ->addColumn('total_amount', function ($invoice) {
                    return '£' . ($invoice->total_amount ?? '-');
                })->filterColumn('total_amount', function ($query, $keyword) {
                    $query->where('total_amount', 'like', "%{$keyword}%");
                })

                ->addColumn('created_at', function ($invoice) {
                    return Carbon::parse($invoice->created_at)->format('m/d/Y h:i a');
                })->filterColumn('created_at', function ($query, $keyword) {
                    try {
                        $date = Carbon::parse($keyword)->format('Y-m-d');
                        $query->whereDate('created_at', $date);
                    } catch (\Exception $e) {
                    }
                })

                ->addColumn('action', function ($invoice) {

                    $actions = '';
                    if (auth()->user()->can('invoicehistory.downloadinvoice')) {
                        $actions .= '
                    <form method="POST" action="' . route('plan.download-invoice') . '" target="_blank">
                        ' . csrf_field() . '
                        <input type="hidden" name="invoice_id" value="' . $invoice->id . '">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa fa-download"></i> Download
                        </button>
                    </form>
                ';
                    }
                    return $actions;
                })

                ->rawColumns(['action'])
                ->make(true);
        }

        return view('purchase-invoice-history.index');
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

        $keyword = 'Driveway Installation';
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
