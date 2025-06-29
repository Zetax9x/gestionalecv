<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dpi extends Model
{
    use HasFactory;

    protected $fillable = [
        'volunteer_id',
        'name',
        'assigned_at',
        'expiry_date',
    ];

    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class);
    }
}
