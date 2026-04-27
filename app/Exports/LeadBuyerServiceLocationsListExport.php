<?php

namespace App\Exports;

use App\Models\UserServiceLocation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class LeadBuyerServiceLocationsListExport implements FromCollection,    WithHeadings,    WithMapping,    WithColumnWidths,    WithColumnFormatting
{
    protected $search;



    public function __construct($search = null)
    {

        $this->search = $search;
    }

    public function collection()
    {
        $query = UserServiceLocation::with('serviceCategory')
            ->orderBy('id', 'DESC');

        if (!empty($this->search)) {

            $search = trim($this->search);

            $noSpace = str_replace(' ', '', $search);

            $withSpace = substr($noSpace, 0, -3) . ' ' . substr($noSpace, -3);

            try {
               
                $formattedDate = \Carbon\Carbon::createFromFormat('m-d-Y', $search)->format('Y-m-d');

            } catch (\Exception $e) {
                $formattedDate = null;
            }
            // dd($formattedDate);

            $query->where(function ($q) use ($search, $noSpace, $withSpace, $formattedDate) {
                $q->where('postcode', 'like', "%{$search}%")
                    ->orWhere('postcode', 'like', "%{$noSpace}%")
                    ->orWhere('postcode', 'like', "%{$withSpace}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('miles', 'like', "%{$search}%");

                if ($formattedDate) {
                    $q->orWhereDate('created_at', $formattedDate); // ✅ correct filter
                }

                $q->orWhereHas('serviceCategory', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                });
            });
        }

        return $query->get();
    }
    public function map($serviceLocation): array
    {
        return [
            $serviceLocation->serviceCategory->name ?? '',
            $serviceLocation->postcode  ?? '',
            $serviceLocation->miles ?? '',
            $serviceLocation->city ?? '',
            ucfirst($serviceLocation->type) ?? '',
            $serviceLocation->created_at
                ? Carbon::parse($serviceLocation->created_at)->format('d-m-Y')
                : '',
        ];
    }

    public function headings(): array
    {
        return [
            'Service',
            'Postcode',
            'Radius',
            'City',
            'Type',
            'Created Date',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 25,
            'D' => 15,
            'E' => 25,
        ];
    }

    // ✅ Correct column format (postcode should be TEXT)
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT, // Postcode column
        ];
    }
}
