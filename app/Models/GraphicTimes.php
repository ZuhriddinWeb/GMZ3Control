<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraphicTimes extends Model
{
    use HasFactory;
    protected $fillable = [
        'GraphicsID',
        'Change',
        'Name',
        'StartTime',
        'EndTime'
    ];
}
