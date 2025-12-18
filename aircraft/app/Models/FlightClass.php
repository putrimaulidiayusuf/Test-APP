<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class FlightClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'flight_id',
        'class_type',
        'price',
        'total_seats'
    ];

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'flight_class_facility_pivot', 'flight_class_id', 'facility_id');
    }

    public function transaction(){
        return $this->hasMany(Transaction::class);
    }
}
