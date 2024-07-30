<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeFactory extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
        'NameRus',
        'ShortName',
        'ShortNameRus',

    ];
}
