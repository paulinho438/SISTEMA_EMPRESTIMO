<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public $table = 'companies';

    protected $fillable = [
        'company',
        'juros',
        'caixa'
    ];

    use HasFactory;

    public function users() {
        return $this->belongsToMany(User::class);
    }
}
