<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes; 

    protected $fillable = ['user_id', 'invoice_number','details','period','amount','vat','total_amount'];

}
