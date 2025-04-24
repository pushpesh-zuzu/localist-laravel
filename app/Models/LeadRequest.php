<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadRequest extends Model
{
    use SoftDeletes; // Enable soft deletes
    use HasSlug;

    protected $fillable = ['customer_id', 'service_id','postcode','questions','phone'];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id','id')->select('id','name','email','total_credit');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'service_id','id')->select('id','name');
    }
}
