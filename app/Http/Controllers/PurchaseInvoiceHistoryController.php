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


   
}
