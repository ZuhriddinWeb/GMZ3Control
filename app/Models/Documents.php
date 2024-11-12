<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documents extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'FactoryStructureID',
        'ParametersID',
    ];
    protected $casts = [
        'FactoryStructureID' => 'array',
        'ParametersID' => 'array',
    ];
}
