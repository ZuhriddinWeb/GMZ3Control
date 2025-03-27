<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class ValuesParameters extends Model
{
    use HasFactory;
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
        'Created',
        'Creator',
        'Changed',
        'Changer',
        'updated_at',
        'created_at'
    ];
    protected $casts = [
        'id' => 'string',
        'ParametersID'=>'string',
        'Value' => 'float',
        'TimeStr'=>'string'
    ];
    public function creatorUser()
{
    return $this->belongsTo(User::class, 'Creator', 'id');
}

}
