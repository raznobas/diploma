<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'call_time',
        'duration',
        'status',
        'answered_duration',
        'client_id',
        'director_id'
    ];
}
