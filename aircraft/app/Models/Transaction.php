<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'flight_id',
        'flight_class_id',
        'name',
        'email',
        'phone',
        'number_of_passengers',
        'promo_code_id',
        'payment_status',
        'subtotal',
        'grandtotal'
    ];

    public function flight()
    {
        return $this->belongsTo(Flight:: class);
    }
    public function class()
    {
        return $this->belongsTo(FlightClass::class, 'flight_class_id');
    }

    public function promo()
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    public function passenger()
    {
        return $this->hasMany(TransactionPassenger::class);
    }
}
