<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'director_id',
        'service_or_product',
        'sport_type',
        'service_type',
        'trainer',
        'training_date',
        'training_time',
        'status',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
