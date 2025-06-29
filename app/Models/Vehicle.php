<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'license_plate',
        'model',
        'registration_date',
        'insurance_expiry',
        'inspection_expiry',
    ];
}
