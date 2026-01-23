<?php

namespace App\Exports;

use App\Models\User;
use App\Models\AbandonedUser;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Carbon\Carbon;

class BuyerListExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths
{
    protected $fromDate;
    protected $toDate;
    protected $search;
    protected $type;

    public function __construct($fromDate = null, $toDate = null, $search = null, $type)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->search = $search;
        $this->type = $type;
    }

    public function collection()
    {
        if ($this->type == 'customer-complete-list') {

            $query = User::with(['leadRequests', 'lastLogin'])
                ->whereNull('deleted_at')
                ->whereIn('user_type', [2, 3])
                ->where('form_status', 1)
                ->where(function ($q) {
                    $q->whereNull('name')
                        ->orWhere('name', 'not like', '%test%');
                })
                ->where(function ($q) {
                    $q->whereNull('email')
                        ->orWhere('email', 'not like', '%test%');
                })
                ->orderBy('id', 'DESC');
        } elseif ($this->type == 'customer-incomplete-list') {

            $query = AbandonedUser::with(['categoryData'])
                ->whereIn('user_type', [2, 3])
                ->where('form_status', 0)
                ->where(function ($q) {
                    $q->whereNull('name')
                        ->orWhere('name', 'not like', '%test%');
                })
                ->where(function ($q) {
                    $q->whereNull('email')
                        ->orWhere('email', 'not like', '%test%');
                })
                ->orderBy('id', 'DESC');
        } elseif ($this->type == 'customer-testcomplete-list') {

            $query = User::with(['leadRequests', 'lastLogin'])
                ->whereNull('deleted_at')
                ->whereIn('user_type', [2, 3])
                ->where('form_status', 1)
                ->where(function ($q) {
                    $q->where('name', 'like', '%test%')
                        ->orWhere('email', 'like', '%test%');
                })
                ->orderBy('id', 'DESC');
        } elseif ($this->type == 'customer-testincomplete-list') {

            $query = AbandonedUser::with(['categoryData'])->whereIn('user_type', [2, 3])
                ->where('form_status', 0)
                ->where(function ($q) {
                    $q->where('name', 'like', '%test%')
                        ->orWhere('email', 'like', '%test%');
                })
                ->orderBy('id', 'DESC');
        } else {
            return collect([]);
        }


        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                $this->fromDate . ' 00:00:00',
                $this->toDate . ' 23:59:59'
            ]);
        } elseif ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        } elseif ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        if ($this->search) {

            $search = $this->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
                if (in_array($this->type, ['customer-complete-list', 'customer-testcomplete-list'])) {
                    $q->orWhereHas('leadRequests', function ($qr) use ($search) {
                        $qr->where('postcode', 'like', "%{$search}%")
                            ->orWhere('credit_score', 'like', "%{$search}%");
                    });

                    $q->orWhereHas('leadRequests.category', function ($qc) use ($search) {
                        $qc->where('name', 'like', "%{$search}%");
                    });
                } else {
                    $q->orWhere(function ($qx) use ($search) {
                        $clean = str_replace(' ', '', $search);

                        $qx->orWhereRaw("REPLACE(zipcode, ' ', '') LIKE ?", ["%{$clean}%"])
                            ->orWhereHas('categoryData', function ($qc) use ($search) {
                                $qc->where('name', 'like', "%{$search}%");
                            });
                    });
                }

                if (in_array($this->type, ['customer-complete-list', 'customer-testcomplete-list'])) {

                    $q->orWhereRaw("
                            DATE_FORMAT(
                                (
                                    SELECT login_at
                                    FROM login_histories
                                    WHERE login_histories.user_id = users.id
                                    ORDER BY login_at DESC
                                    LIMIT 1
                                ),
                                '%m/%d/%Y %h:%i %p'
                            ) LIKE ?
                        ", ["%{$search}%"]);
                }


                $q->orWhere(function ($qd) use ($search) {
                    try {
                        $date = \Carbon\Carbon::parse($search)->format('Y-m-d');
                        $qd->whereDate('created_at', $date);
                    } catch (\Exception $e) {
                    }
                });
            });
        }

        return $query->get();
    }

    public function map($user): array
    {


        $completeTypes = ['customer-complete-list', 'customer-testcomplete-list'];
        $isComplete = in_array($this->type, $completeTypes);
        // --------------------------------------
        // COMPLETE USERS → service from leadRequests
        // --------------------------------------
        if ($isComplete) {
            $combinedLeads = $user->leadRequests->map(function ($lead) {
                $serviceName = $lead->category->name ?? '-';
                $postcode    = $lead->postcode ?? '-';
                $score       = $lead->credit_score ?? '-';

                return "{$serviceName} (Postcode: {$postcode}, Score: {$score})";
            })->implode("\n");
        } else {
            $category = Category::where('id', $user->service_id)->value('name');
            $combinedLeads = $category ?? '-';
        }

        $row = [
            $user->name ?? '',
            $user->email ?? '',
            $user->phone ?? '',
            $user->zipcode ?? '',
            $user->city ?? '',
            $combinedLeads,
            $user->campaignid ?? '',
            $user->gclid ?? '',
            $user->keyword ?? '',
            $user->campaign ?? '',
            $user->adgroup ?? '',
            $user->targetid ?? '',
            $user->msclickid ?? '',
            $user->entry_url ?? '',
            $user->user_ip_address ?? '',
        ];


        if ($isComplete) {
            $row[] = $user->lastLogin?->login_at
                ? Carbon::parse($user->lastLogin->login_at)->format('d/m/Y h:i A')
                : '';
        }

        // Created Date
        $row[] = $user->created_at
            ? Carbon::parse($user->created_at)->format('d/m/Y h:i A')
            : '';

        // Status
        $row[] = $isComplete ? 'Complete' : 'Incomplete';

        return $row;
    }

    public function headings(): array
    {
        $completeTypes = ['customer-complete-list', 'customer-testcomplete-list'];
        $isComplete = in_array($this->type, $completeTypes);

        $headings = [
            'Name',
            'Email',
            'Phone',
            'Zipcode',
            'City',
            'Services',
            'Campaign Id',
            'GCLID',
            'Keyword',
            'Campaign',
            'AdGroup',
            'Target Id',
            'MS Click Id',
            'Entry URL',
            'User IP Address',
        ];

        if ($isComplete) {
            $headings[] = 'Last Login';
        }

        $headings[] = 'Date';
        $headings[] = 'Status';

        return $headings;
    }

    public function columnWidths(): array
    {
        return [
            'F' => 70,
        ];
    }
}
