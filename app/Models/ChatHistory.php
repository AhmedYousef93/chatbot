<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    protected $fillable = [ 'location', 'price', 'room', 'features','after_number'];

    protected $casts = [
        'features' => 'array',
    ];
}
