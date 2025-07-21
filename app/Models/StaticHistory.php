<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaticHistory extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'static_id',
        'value',
        'period_start_date',
        'period_end_date',
    ];

    public function static()
    {
        return $this->belongsTo(static::class);
    }
}
