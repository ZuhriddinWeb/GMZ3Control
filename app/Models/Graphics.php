<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\GraphicTimes;

class Graphics extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
        'NameRus',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer'
    ];

    public function graphicTimes()
{
    return $this->hasMany(GraphicTimes::class, 'GraphicsID', 'id');
}
}
