<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_id',
        'phone_from',
        'phone_to',
        'call_time',
        'duration',
        'status',
        'last_seq',
        'client_id',
        'director_id'
    ];
}
