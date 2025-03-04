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
        'director_email',
        'manager_email',
    ];
}
