<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Graphics extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
        'NameRus',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer'
    ];
}
