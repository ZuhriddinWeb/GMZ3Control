<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VPChangeSync extends Model
{
    use HasFactory;

    // Agar SQL Server ulanishidan foydalansangiz:
    protected $connection = 'sqlsrv';

    // Jadval nomi (schema kerak bo'lsa: 'dbo.values_parameters_change')
    protected $table = 'values_parameters_change';

    protected $primaryKey = 'id';
    public $incrementing = false;   // UUID bo'lsa
    protected $keyType = 'string';

    public $timestamps = true;      // created_at / updated_at mavjud
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'ParametersID',
        'SourcesID',
        'ChangeID',
        'Value',
        'Comment',
        'TimeID',
        'TimeStr',
        'FactoryStructureID',
        'BlogID',
        'GraphicsTimesID',
        'TermID',
        'Created',
        'Creator',
        'Changed',
        'Changer',
        'updated_at',
        'created_at'
    ];

    protected $casts = [
        'id'           => 'string',
        'ParametersID' => 'string',
        'Value'        => 'float',
        'TimeID'       => 'integer',
        'TimeStr'      => 'string',
        'Created'      => 'datetime', // agar faqat sana bo'lsa 'date' qiling
        'Changed'      => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // UUID avtomatik
    protected static function booted()
    {
        static::creating(function ($m) {
            if (empty($m->id)) {
                $m->id = (string) Str::uuid();
            }
        });
    }

    public function creatorUser()
    {
        return $this->belongsTo(User::class, 'Creator', 'id');
    }

    public function changerUser()
    {
        return $this->belongsTo(User::class, 'Changer', 'id');
    }
}
