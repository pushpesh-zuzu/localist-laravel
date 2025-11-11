<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Helpers\CustomHelper;
use App\Models\RecommendedLead;
class SellerCompleteListExport implements FromCollection, WithHeadings, WithMapping
{
    protected $fromDate;
    protected $toDate;
    protected $search;

    public function __construct($fromDate = null, $toDate = null, $search = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->search   = $search;
    }

    public function collection()
    {
       $query = User::query()
            ->whereIn('user_type', [1, 3])
            ->where('form_status', 1)
            ->whereNull('deleted_at') // 🧩 ensures soft-deleted users are excluded
            ->with([
                'lastLogin' => function ($q) {
                    $q->whereNull('deleted_at'); // exclude soft-deleted logins if model uses SoftDeletes
                },
                'services.category' => function ($q) {
                    $q->whereNull('deleted_at'); // optional, if these tables have SoftDeletes
                },
                'serviceLocations' => function ($q) {
            $q->whereNull('deleted_at'); // 👈 added this new relationship
        },
            ])
            ->withSum('planHistories as total_credits_bought', 'credits')
            ->orderBy('id', 'DESC');

        
        if ($this->fromDate && $this->toDate) {           
                $query->whereBetween('created_at', [
                    $this->fromDate . ' 00:00:00',
                    $this->toDate . ' 23:59:59',
                ]);
            } elseif ($this->fromDate) {
                $query->whereDate('created_at', '>=', $this->fromDate);
            } elseif ($this->toDate) {
                $query->whereDate('created_at', '<=', $this->toDate);
            }


      if ($this->search && $this->search !== '') {
    $search = $this->search;
    $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%")
          ->orWhere('total_credit', 'like', "%{$search}%")
          ->orWhere('form_status', $search === 'Complete' ? 1 : ($search === 'Incomplete' ? 0 : null)) 
          ->orWhere('status', $search === 'Active' ? 1 : ($search === 'Inactive' ? 0 : null))
          ->orWhereRaw("
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
    });
}


      

        return $query->get();
    }

    public function map($user): array
    {
    $ratingData = CustomHelper::getAverageRating($user->id);
    $averageRating = $ratingData['average_rating'] ?? 'N/A';
    $totalReviews = $ratingData['total_reviews'] ?? 0;

     $leadStats = RecommendedLead::selectRaw('COUNT(*) as total_hired, SUM(bid) as total_bid')
        ->whereIn('status', ['pending', 'hired'])->where('seller_id', $user->id)
        ->first();
         $sellerTotalHired=$leadStats->total_hired ?? 0;
        $sellerTotalBid=$leadStats->total_bid ?? 0;


$userServices = $user->services->map(function ($service) use ($user) {
    $categoryName = $service->category->name ?? '';

    // find matching service location
    $location = $user->serviceLocations
        ->where('user_service_id', $service->id)
        ->first();  

    if ($location) {
        return sprintf(
            '%s (Miles: %s, Postcode: %s)',
            $categoryName,
            $location->miles ?? 'N/A',
            $location->postcode ?? 'N/A'
        );
    }

    return $categoryName;
})->implode(', ');



        return [
            $user->name,
            $user->zipcode ?? '',
            $user->email,
            $user->total_credit ?? 0,
            $user->total_credits_bought ?? 0,
            $user->lastLogin?->login_at ? \Carbon\Carbon::parse($user->lastLogin->login_at)->format('m/d/Y h:i a') : '',
            (string) $sellerTotalHired ?? 0,
            (string) $sellerTotalBid ?? 0,
            url(config('app.react_base_url') . '/view-profile/' . 
            strtolower(preg_replace('/\s+/', '-', trim($user->name))) . 
            '/' . $user->id),           
            $userServices,           
            optional($user->serviceLocations->first())->miles ?? 'N/A',
            $user->created_at ? $user->created_at->format('d-m-Y') : '',
            $totalReviews,
            $averageRating,
            $user->company_website ?? '',
            $user->company_size ?? '',
            $user->company_total_years ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Postcode',
            'Email',
            'Total Credit',
            'Total Credits Bought',
            'Last Login',
            'No Leads Purchased',
            'Total Value of Leads Purchased',
            'Public Profile URL',
            'Services',
            'Radius (miles)',
            'Registration Date',
            'No. of Reviews',
            'Average Review Score',
            'Company Website',
            'Company Size',
            'Years in Business',
        ];
    }
}
