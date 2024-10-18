<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Formula extends Model
{
    use HasFactory;
    protected $fillable = [
        'StructureID',
        'Name',
        'NameRus',
        'ParametersId',
        'Comment',
    ];
    protected $casts = [
        'ParametersId' => 'array',
    ];

    public function getParameters()
    {
        // Ensure ParametersId is an array and not empty
        if (!empty($this->ParametersId)) {
            return DB::table('parameters')->whereIn('id', $this->ParametersId)->get();
        }
        return collect(); // Return an empty collection if no IDs
    }
}
