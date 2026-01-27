<?php

namespace App\Exports;

use App\Models\LoginHistory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoginHistoryListExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths
{
    protected $fromDate;
    protected $toDate;
    protected $userType;
    protected $search; // ✅ search keyword

    public function __construct($fromDate = null, $toDate = null,  $search = null,$userType = [1])
    {
       
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->userType = $userType;
        $this->search = $search; // assign search

      
    }

    public function collection()
    {
        $query = LoginHistory::select(
                'users.id as user_id',
                'users.name',
                'users.email',
                DB::raw('COUNT(login_histories.id) as total_logins'),
                DB::raw('MIN(login_histories.login_at) as first_login'),
                DB::raw('MAX(login_histories.login_at) as last_login'),
                DB::raw('(SELECT lh.ip FROM login_histories lh WHERE lh.user_id = users.id ORDER BY lh.login_at DESC LIMIT 1) as last_ip'),
                DB::raw('(SELECT lh.user_agent FROM login_histories lh WHERE lh.user_id = users.id ORDER BY lh.login_at DESC LIMIT 1) as last_device')
            )
            ->join('users', 'users.id', '=', 'login_histories.user_id')
            ->whereNull('users.deleted_at')
            ->whereIn('users.user_type',  [$this->userType])
            ->where('users.form_status', 1)
            ->where(function ($q) {
                    $q->whereNull('users.name')
                        ->orWhere('users.name', 'not like', '%test%');
                })
                ->where(function ($q) {
                    $q->whereNull('users.email')
                        ->orWhere('users.email', 'not like', '%test%');
                })
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('last_login', 'DESC');

        // Date filter
        if ($this->fromDate && $this->toDate) {
           
            $query->whereBetween('login_histories.login_at', [
                $this->fromDate . ' 00:00:00',
                $this->toDate . ' 23:59:59',
            ]);
        } elseif ($this->fromDate) {
            $query->whereDate('login_histories.login_at', '>=', $this->fromDate);
        } elseif ($this->toDate) {
            $query->whereDate('login_histories.login_at', '<=', $this->toDate);
        }

        // ✅ Search filter
        if ($this->search) {
            $search = "%{$this->search}%";
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'like', $search)
                  ->orWhere('users.email', 'like', $search)
                  ->orWhereRaw("(SELECT lh.ip FROM login_histories lh WHERE lh.user_id = users.id ORDER BY lh.login_at DESC LIMIT 1) LIKE ?", [$search])
                  ->orWhereRaw("(SELECT lh.user_agent FROM login_histories lh WHERE lh.user_id = users.id ORDER BY lh.login_at DESC LIMIT 1) LIKE ?", [$search]);
            });
        }

      
        return $query->get();
    }

    public function map($row): array
{
    return [
        $row->name ?? '',
        $row->email ?? '',
        $row->last_ip ?? '',
        $row->last_device ?? '',
        $row->first_login ? Carbon::parse($row->first_login)->format('d/m/Y h:i A') : '',
        $row->last_login ? Carbon::parse($row->last_login)->format('d/m/Y h:i A') : '',
        $row->total_logins ?? 0, // ✅ no count() needed
    ];
}

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Last IP',
            'Last Device',
            'First Login',
            'Last Login',
            'Total Logins',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 30,
            'C' => 20,
            'D' => 30,
            'E' => 25,
            'F' => 25,
            'G' => 15,
        ];
    }
}
