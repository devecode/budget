<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Egress extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'amount',
        'is_fixed',
        'date_egreso',
        'status',
        'payment_method_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
