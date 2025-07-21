<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\GraphicsParamenters;
class Parameters extends Model
{
    use HasFactory;
     protected $table = 'parameters';    // id, Name, …
     public    $incrementing = false;
     protected $keyType = 'string';
    protected $fillable = [
        'id',
        'Name',
        'NameRus',
        'ShortName',
        'ShortNameRus',
        'WinCC',
        'ServerId',
        'ParametrTypeID',
        'Min',
        'Max',
        'UnitsID',
        'Created',
        'Creator',
        'Changed',
        'Changer',
        'Comment'
    ];
    protected $casts = [
       
        'id' => 'string',

    ];
    public function graphicsParameters()
    {
        return $this->hasMany(GraphicsParamenters::class, 'ParametersID', 'id');
    }
}
