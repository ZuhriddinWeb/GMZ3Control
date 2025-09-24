<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaticParameters extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'FactoryStructureID',
        'ParameterID',
        'NumberPage',
        'GroupID',
        'OrderNumber',
        'value',
        'Comment',
        'period_type_id',
        'period_start_date',
        'period_end_date',
    ];
    protected $casts = [

        'id' => 'string',
        'ParameterID'=>'string'
    ];
    // public function histories()
    // {
    //     return $this->hasMany(StaticHistory::class, 'static_id');
    // }
}
