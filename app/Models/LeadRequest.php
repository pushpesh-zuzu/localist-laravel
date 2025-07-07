<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use App\Services\ZohoService;

class LeadRequest extends Model
{
    use SoftDeletes; // Enable soft deletes
    use HasSlug;

    protected $fillable = ['customer_id', 'service_id','postcode','questions','phone','should_autobid'];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id','id')->select('id','name','email','total_credit');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'service_id','id')->select('id','name');
    }

    public function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    // protected static function booted()
    // {
    //     static::created(function ($lead) {
    //         app(ZohoService::class)->integrateUser('lead', null, $lead);
    //     });

    //     static::updated(function ($lead) {
    //         app(ZohoService::class)->integrateUser('lead', null, $lead);
    //     });
    // }
}
