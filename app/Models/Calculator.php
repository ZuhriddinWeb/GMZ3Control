<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calculator extends Model
{
    use HasFactory;
    protected $fillable = [
        'TimeID',
        'ParametersID',
        'Calculate',
        'Comment',
    ];
    protected $casts = [
        'Calculate' => 'array',
    ];
}
