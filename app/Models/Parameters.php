<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parameters extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'Name',
        'NameRus',
        'ShortName',
        'ShortNameRus',
        'ParametrTypeID',
        'UnitsID',
        'Created',
        'Creator',
        'Changed',
        'Changer',
        'Comment'
    ];
}
