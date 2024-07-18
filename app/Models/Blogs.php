<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blogs extends Model
{
    use HasFactory;
    protected $fillable = [
        'StructureID',
        'Name',
        'ShortName',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];
}
