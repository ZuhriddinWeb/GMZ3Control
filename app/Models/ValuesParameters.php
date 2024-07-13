<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValuesParameters extends Model
{
    use HasFactory;
    protected $fillable = [
        'ParametersID',
        'SourcesID',
        'Time',
        'Value',
        'Comment',
        'GraphicsTimesID',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];
}
