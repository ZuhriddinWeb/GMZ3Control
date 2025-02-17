<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class ValuesParameters extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false; // ID auto-increment emas
    protected $fillable = [
        'id',
        'ParametersID',
        'SourcesID',
        'ChangeID',
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
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
    protected $casts = [
        'id' => 'string',
        'ParametersID'=>'string',
        'Value' => 'float',
    ];
}
