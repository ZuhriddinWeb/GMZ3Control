<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SheetFormulas extends Model
{
    use HasFactory;

 public $incrementing = false;
    protected $keyType = 'string';

    // Mass-assignmentga ruxsat bering:
    protected $fillable = [
        'id', 'param_id', 'number_page', 'for_date', 'date', 'cell', 'expr',
    ];

    // Agar timestamps kerak bo'lsa (default true):
    public $timestamps = true;
}
