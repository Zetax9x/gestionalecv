<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use HasFactory;

    protected $fillable = ['volunteer_id', 'action', 'description'];

    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class);
    }
}
