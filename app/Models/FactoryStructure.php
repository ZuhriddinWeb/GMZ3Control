<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactoryStructure extends Model
{
    use HasFactory;
    protected $fillable = [
        'ParentID',
        'Name',
        'ShortName',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];
}
