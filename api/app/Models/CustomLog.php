<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomLog extends Model
{
    use HasFactory;

    public $fillable = [
        'user_id',
        'content',
        'operation',
    ];
}
