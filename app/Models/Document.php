<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'volunteer_id',
        'vehicle_id',
        'category',
        'name',
        'path',
        'expiry_date',
    ];

    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
