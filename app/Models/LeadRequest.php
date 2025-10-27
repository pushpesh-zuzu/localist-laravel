<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helpers\Zoho\ZohoLeads;
class LeadRequest extends Model
{
   // use SoftDeletes; // Enable soft deletes
    use HasSlug;

    protected $fillable = ['customer_id', 'service_id','postcode','questions','phone','should_autobid','city','arrayed_questions','credit_score'];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id','id')->select('id','name','email','total_credit','zipcode','phone');
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
    //         //app(ZohoLeads::class)->integrateLead($lead);
    //     });

    //     static::updated(function ($lead) {
    //         // Log::info('LeadRequest updated - triggering Zoho sync', ['lead_id' => $lead->id]);
    //         //app(ZohoLeads::class)->integrateLead($lead);
    //     });
    // }

}
