<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calculator extends Model
{
    use HasFactory;
    protected $fillable = [
        'FormulaId',
        'Calculate',
        'Comment',
    ];
    protected $casts = [
        'Calculate' => 'array',
    ];
}
