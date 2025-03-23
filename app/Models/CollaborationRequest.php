<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollaborationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'director_id',
        'manager_id',
    ];

    // Отношение к менеджеру
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Отношение к директору
    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }
}
