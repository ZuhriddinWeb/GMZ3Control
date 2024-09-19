<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GraphicTimes extends Model
{
    use HasFactory;
    protected $fillable = [
        'GraphicsID',
        'Change',
        'Name',
        'StartTime',
        'EndTime',
        'Current'
    ];
     public function getStartTimeAttribute($value)
     {
         return Carbon::parse($value)->format('H:i');
     }
 
     public function getEndTimeAttribute($value)
     {
         return Carbon::parse($value)->format('H:i');
     }
}
