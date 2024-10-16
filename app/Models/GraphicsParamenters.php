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
        'PageId',
        'SourceID',
        'CurrentTime',
        'EndingTime',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];

    protected $casts = [
        'OrderNumber' => 'integer',
        'FactoryStructureID' => 'integer',
        'GrapicsID' => 'integer',
        'BlogsID' => 'integer',
        'PageId' => 'integer',
        'SourceID' => 'integer'


    ];
}
