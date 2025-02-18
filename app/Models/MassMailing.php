<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MassMailing extends Model
{
    use HasFactory;

    protected $fillable = [
        'director_id',
        'block',
        'selected_categories',
        'message_text',
        'send_offset',
    ];
}
