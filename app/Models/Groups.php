<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Groups extends Model
{
    use HasFactory;
    protected $fillable = [
        'StructureID',
        'PageID',
        'Name',
        'NameRus',
        'Comment',
    ];
}
