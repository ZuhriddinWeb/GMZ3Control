<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SheetValue extends Model
{
    use HasFactory;
    protected $fillable = [
        'number_page', 'for_date', 'cell', 'value',
    ];
}
