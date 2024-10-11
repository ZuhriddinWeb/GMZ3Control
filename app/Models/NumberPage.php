<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberPage extends Model
{
    use HasFactory;
    protected $fillable = [
        'StructureID',
        'Name',
        'NameRus',
        'Comment',
        'NumberPage'
    ];
}
