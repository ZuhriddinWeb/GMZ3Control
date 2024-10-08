<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValuesParameters extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'ParametersID',
        'SourcesID',
        'Value',
        'Comment',
        'TimeID',
        'FactoryStructureID',
        'BlogID',
        'GraphicsTimesID',
        'Created',
        'Creator',
        'Changed',
        'Changer',
        'updated_at',
        'created_at'
    ];
    protected $casts = [
        'id' => 'string',
        'ParametersID'=>'string',
        'Value' => 'integer',
    ];
}
