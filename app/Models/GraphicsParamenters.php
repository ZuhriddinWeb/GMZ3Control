<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Models\Parameters;
class GraphicsParamenters extends Model
{
    use HasFactory;
    protected $fillable = [
        'OrderNumber',
        'ParametersID',
        'FactoryStructureID',
        'GrapicsID',
        'WithFormula',
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
        'SourceID' => 'integer',
        'ParametersID' => 'string',
        'WithFormula'=>'integer'

    ];
    public function parameters()
{
    return $this->belongsTo(Parameters::class, 'ParametersID', 'id');
}
    
    
    
}
