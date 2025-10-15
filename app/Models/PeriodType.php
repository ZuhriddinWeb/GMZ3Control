<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodType extends Model
{
    use HasFactory;
    protected $table = 'period_types';    // id, Name, …
    protected $fillable = [
        'name',
        'OrderNumber',
    ];
}
