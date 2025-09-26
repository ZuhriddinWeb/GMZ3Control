<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class FactoryStructure extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
        'NameRus',
        'ShortName',
        'ShortNameRus',
        'OrderNumberSex',
        'Comment',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];
    protected $table = 'factory_structures'; 

    public function blogs()
    {
        return $this->hasMany(Blogs::class, 'StructureID');
    }
}
