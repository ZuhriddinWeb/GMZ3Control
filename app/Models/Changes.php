<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Changes extends Model
{
    use HasFactory;
    protected $fillable = [
        'FactoryID',
        'Change',
        'StartingDay',
        'StartingTime',
        'EndingDay',
        'EndingTime',
        'Comment'
    ];

    protected $casts = [
        'Change' => 'integer'
    ];
}
