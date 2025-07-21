<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Models\Parameters;
use App\Models\NumberPage;
use App\Models\FactoryStructure;

class GraphicsParamenters extends Model
{
    use HasFactory;
    protected $table = 'graphics_paramenters';

    protected $fillable = [
        'OrderNumber',
        'ParametersID',
        'FactoryStructureID',
        'GrapicsID',
        'WithFormula',
        'BlogsID',
        'PageId',
        'GroupID',
        'SourceID',
        'CurrentTime',
        'EndingTime',
        'Created',
        'Creator',
        'Changed',
        'Changer',
    ];

    protected $casts = [
        'OrderNumber' => 'integer',
        'FactoryStructureID' => 'integer',
        'GrapicsID' => 'integer',
        'BlogsID' => 'integer',
        'PageId' => 'integer',
        'SourceID' => 'integer',
        'ParametersID' => 'string',
        'WithFormula' => 'integer',
        'GroupID' => 'integer'
    ];
    public function parameters()
    {
        return $this->belongsTo(Parameters::class, 'ParametersID', 'id');
    }
    public function parameter()                  // 🔹 faqat shu!
    {
        return $this->belongsTo(
            Parameters::class,    // foreign model
            'ParametersID',      // FK (graphics_paramenters)
            'id'                 // PK (parameters)
        );
    }
    public function numberPage()
    {
        return $this->belongsTo(NumberPage::class, 'PageId', 'NumberPage');
    }
    public function factoryStructure()
    {
        return $this->belongsTo(FactoryStructure::class, 'FactoryStructureID', 'id');
    }

    public function graphics()
    {
        return $this->belongsTo(Graphics::class, 'GraphicsID', 'id'); // diqqat: ID emas, GraphicsID
    }

}
