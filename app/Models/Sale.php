<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_date',
        'client_id',
        'director_id',
        'service_or_product',
        'sport_type',
        'service_type',
        'product_type',
        'subscription_duration',
        'visits_per_week',
        'training_count',
        'trainer_category',
        'trainer',
        'subscription_start_date',
        'subscription_end_date',
        'cost',
        'paid_amount',
        'pay_method',
        'comment',
        'created_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
