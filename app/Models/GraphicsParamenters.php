<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraphicsParamenters extends Model
{
    use HasFactory;
    protected $fillable = [
        'OrderNumber',
        'ParametersID',
        'FactoryStructureID',
        'GrapicsID',
        'BlogsID',
        'CurrentTime',
        'EndingTime',
        'Min',
        'Max',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];
}
