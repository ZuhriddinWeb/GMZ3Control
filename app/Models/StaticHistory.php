<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StaticParameters;
class StaticHistory extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'static_histories';

    protected $fillable = [
        'id',
        'static_id',
        'value',
        'period_start_date',
        'period_end_date',
        'period_type_id',
        'comment',
        'date',
    ];
    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'date' => 'date',
    ];
    public function static()
    {
        return $this->belongsTo(StaticParameters::class, 'static_id', 'id');
    }
}
