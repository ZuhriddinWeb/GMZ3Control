<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class DocumentNumberPage extends Model
{
    use HasFactory;
    protected $table = 'document_number_pages';
    protected $primaryKey = 'IdNumberPage';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'IdBlog',
        'IdNumberPage',
        'Name',
        'NameRus',
        'FactoryStructureID',
        'NumberPageBlogs',
        'GroupBlogs',
        'ParameterBlogs',
        'Comment',
        'GroupBlogs', 'ParameterBlogs',
    ];
    protected $casts = [
        'FactoryStructureID' => 'array',
        'NumberPageBlogs' => 'array',
        'GroupBlogs' => 'array',
        'ParameterBlogs'     => 'array',
    ];
    public function structurePages()
    {
        return $this->hasMany(DocumentStructurePage::class, 'document_id');
    }
}
