<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\D7LeadSupplier;
use Yajra\Datatables\Datatables;
use App\Exports\d7LeadSupplierListExport;
use App\Helpers\CustomHelper;
use App\Helpers\Zoho\ZohoD7LeadSuppliers;
use App\Helpers\Zoho\ZohoHelper;
use App\Helpers\Zoho\ZohoImportService;
use App\Models\D7SupplierClickOpenReport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
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
                    return Carbon::parse($user->created_at)->format('d/m/Y h:i a');
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
                'id' => '1',
                'email' => 'ashishg@zuzucodes.com',
                'name'  => 'Test',
            ],
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


    public function testIntegrateD7LeadSuppliers()
    {
        $results = [];

        D7LeadSupplier::whereNull('zoho_record_id')
            ->chunk(50, function ($suppliers) use (&$results) {
                foreach ($suppliers as $supplier) {
                    try {

                        if (!empty($supplier->zoho_record_id)) {
                            $results[$supplier->id] = [
                                'skipped' => true,
                                'message' => 'Zoho record already exists',
                            ];
                            continue;
                        }

                        $results[$supplier->id] =
                            app(ZohoD7LeadSuppliers::class)->integrateD7LeadSupplier($supplier->id);

                        usleep(500000); // 0.5 sec delay

                    } catch (\Throwable $e) {
                        $results[$supplier->id] = [
                            'error' => true,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            });

        return $results;
    }


    public function testIntegrateD7LeadAccountSuppliers()
    {
        set_time_limit(0);

        D7LeadSupplier::whereNull('zoho_account_record_id')
            ->where('is_subscribed', '1')
            ->where('email_status', 'new')
            ->chunk(50, function ($suppliers) {

                foreach ($suppliers as $supplier) {

                    if (!empty($supplier->zoho_account_record_id)) {
                        continue;
                    }

                    $supplierId = $supplier->id;

                    CustomHelper::runInBackground(function () use ($supplierId) {

                        try {

                            app(ZohoD7LeadSuppliers::class)
                                ->syncD7SuppliersToZohoAccounts($supplierId);
                        } catch (\Throwable $e) {

                            Log::error('Zoho Supplier Sync Error', [
                                'supplier_id' => $supplierId,
                                'message' => $e->getMessage()
                            ]);
                        }
                    });

                    usleep(2000000);
                }
            });

        return "Background sync started";
    }





    public function testDeleteZohoRecord()
    {
        $zohoRecordId = '623840000007420131';

        try {

            $response = app(ZohoD7LeadSuppliers::class)->deleteZohoRecord($zohoRecordId);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function testDeleteZohoAccountRecord()
    {
        $zohoRecordId = '623840000009556048';

        try {
            $response = app(ZohoD7LeadSuppliers::class)->deleteZohoAccountRecord($zohoRecordId);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function zeptoWebhook(Request $request)
    {
        Log::info('Zepto Webhook Received', $request->all());

        $eventName = $request->input('event_name.0'); // softbounce | hardbounce | fbl_compliant

        $message   = $request->input('event_message.0');

        if (!$eventName || !$message) {
            return response()->json(['status' => 'invalid_payload'], 200);
        }

        $messageId = $message['request_id'] ?? null;

        $eventData = $message['event_data'][0] ?? [];
        $details   = $eventData['details'][0] ?? [];

        $email  = $details['bounced_recipient'] ?? null;
        $reason = $details['reason'] ?? null;

        $supplierLead = null;

        if ($messageId) {
            $supplierLead = D7LeadSupplier::where('message_id', $messageId)->first();
        }

        if (!$supplierLead && $email) {
            $supplierLead = D7LeadSupplier::where('email', $email)
                ->latest('id')
                ->first();
        }

        if (!$supplierLead) {
            return response()->json(['status' => 'not_found'], 200);
        }

        switch ($eventName) {

            case 'hardbounce':
                if ($supplierLead->email_status !== 'hard_bounced') {
                    $supplierLead->update([
                        'email_status'  => 'hard_bounced',
                        'bounce_reason' => $reason,
                    ]);

                    if ($supplierLead->zoho_record_id) {
                        app(ZohoD7LeadSuppliers::class)
                            ->deleteZohoRecord($supplierLead->zoho_record_id);
                    }

                    if ($supplierLead->zoho_account_record_id) {
                        app(ZohoD7LeadSuppliers::class)
                            ->deleteZohoAccountRecord($supplierLead->zoho_account_record_id);
                    }
                }
                break;

            case 'softbounce':
                $supplierLead->update([
                    'email_status'  => 'soft_bounced',
                    'bounce_reason' => $reason,
                ]);

                if ($supplierLead->zoho_record_id) {
                    app(ZohoD7LeadSuppliers::class)
                        ->deleteZohoRecord($supplierLead->zoho_record_id);
                }

                if ($supplierLead->zoho_account_record_id) {
                    app(ZohoD7LeadSuppliers::class)
                        ->deleteZohoAccountRecord($supplierLead->zoho_account_record_id);
                }
                break;

            case 'fbl_compliant': // spam complaint
                $supplierLead->update([
                    'email_status'  => 'spam',
                    'bounce_reason' => 'fbl_complaint',
                ]);

                if ($supplierLead->zoho_record_id) {
                    app(ZohoD7LeadSuppliers::class)
                        ->deleteZohoRecord($supplierLead->zoho_record_id);
                }
                if ($supplierLead->zoho_account_record_id) {
                    app(ZohoD7LeadSuppliers::class)
                        ->deleteZohoAccountRecord($supplierLead->zoho_account_record_id);
                }
                break;


            case 'email_open':

                $email = $request->input('event_message.0.email_info.to.0.email_address.address');
                $openTime = $request->input('event_message.0.event_data.0.details.0.time');

                $supplierLead = D7LeadSupplier::where('email', $email)
                    ->latest('id')
                    ->first();

                $openAt = $openTime ? \Carbon\Carbon::parse($openTime) : now();

                if ($email && $messageId) {

                    $report = D7SupplierClickOpenReport::firstOrCreate([
                        'message_id'     => $messageId,
                        'supplier_email' => $email,
                    ]);

                    $lastOpen = $report->open_at ? \Carbon\Carbon::parse($report->open_at) : null;

                    // agar same second hai to increment na kare
                    if (!$lastOpen || $lastOpen->timestamp !== $openAt->timestamp) {

                        $report->increment('open_count');

                        $report->update([
                            'open_at' => $openAt->format('Y-m-d H:i:s')
                        ]);
                    }
                }

                if ($supplierLead && $supplierLead->zoho_account_record_id) {
                    app(ZohoImportService::class)
                        ->addMarketingContactHistory($supplierLead->zoho_account_record_id, $messageId);
                }

                break;

            case 'email_link_click':

                $email = $request->input('event_message.0.email_info.to.0.email_address.address');
                $clickTime = $request->input('event_message.0.event_data.0.details.0.time');

                $supplierLead = D7LeadSupplier::where('email', $email)
                    ->latest('id')
                    ->first();

                $clickAt = $clickTime ? \Carbon\Carbon::parse($clickTime) : now();

                if ($email && $messageId) {

                    $report = D7SupplierClickOpenReport::firstOrCreate([
                        'message_id'     => $messageId,
                        'supplier_email' => $email,
                    ]);

                    $lastClick = $report->click_at ? \Carbon\Carbon::parse($report->click_at) : null;

                    // duplicate webhook check
                    if (!$lastClick || $lastClick->timestamp !== $clickAt->timestamp) {

                        $report->increment('click_count');

                        $report->update([
                            'click_at' => $clickAt->format('Y-m-d H:i:s')
                        ]);
                    }

                    if ($supplierLead && $supplierLead->zoho_account_record_id) {
                        app(ZohoImportService::class)
                            ->addMarketingContactHistory($supplierLead->zoho_account_record_id, $messageId);
                    }
                }

                break;

            default:
                // ignore
                break;
        }

        return response()->json(['status' => 'ok'], 200);
    }
}
