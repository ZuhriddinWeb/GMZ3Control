<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SvodkaFormula extends Model
{
    use HasFactory;
    protected $fillable = [
        'page_id_blog','param_id', 'sex_id', 'page_id', 'group_id', 'tokens', 'comment',
    ];

    protected $casts = [
        'tokens' => 'array', // avtomatik json<->array
    ];
}
