<?php

namespace App\Exports;

use App\Models\D7LeadSupplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class d7LeadSupplierListExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnWidths,
    WithColumnFormatting
{
    public function collection()
    {
        return D7LeadSupplier::orderBy('id', 'DESC')->get();
    }

    public function map($supplier): array
    {
        return [
            $supplier->category ?? '',
            $supplier->lead_service ?? '',
            $supplier->name ?? '',
           "\t" . $supplier->phone, // 👈 force string
            $supplier->email ?? '',
            $supplier->address1 ?? '',
            $supplier->region ?? '',
            $supplier->zip ?? '',
            $supplier->website ?? '',
            $supplier->created_at
                ? Carbon::parse($supplier->created_at)->format('m/d/Y h:i A')
                : '',
        ];
    }

    public function headings(): array
    {
        return [
            'Category',
            'Service',
            'Supplier Name',
            'Phone',
            'Email',
            'Address',
            'Region',
            'ZIP Code',
            'Website',
            'Created Date',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 25,
            'D' => 15, // Phone
            'E' => 25,
            'F' => 30,
            'G' => 20,
            'H' => 12,
            'I' => 25,
            'J' => 20,
        ];
    }

    // 🔑 THIS FIXES THE SCIENTIFIC NOTATION ISSUE
    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_TEXT, // Phone column
        ];
    }
}
