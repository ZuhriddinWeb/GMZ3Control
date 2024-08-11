<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GraphicsParamenters;
use DB;
use Carbon\Carbon;
class ParamsGraphController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return $this->getRowUnit($id);
        }
// asd
        switch ($request->method()) {
            case 'GET':
                return $this->index();
            case 'POST':
                return $this->create($request);
            case 'PUT':
                return $this->update($request,$id);
            case 'DELETE':
                return $this->delete($request,$id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }

    private function index()
    {
        $Gparams = GraphicsParamenters::join('parameters','graphics_paramenters.ParametersID','=','parameters.id')
        ->join('factory_structures','graphics_paramenters.FactoryStructureID','=','factory_structures.id')
        ->join('graphics','graphics_paramenters.GrapicsID','=','graphics.id')
        ->select('graphics.id as Gid','graphics.name as GName','parameters.id as Puuid','parameters.name as PName','parameters.name as PNameRus','factory_structures.id as Fid','factory_structures.name as FName','graphics_paramenters.*')
        ->get();
        return response()->json($Gparams);
    }
    private function getRowUnit($id)
    {
        $unit = GraphicsParamenters::join('parameters','graphics_paramenters.ParametersID','=','parameters.id')->where('BlogsID',$id)->get();
        return response()->json($unit);
    }
    public function getParamsForUser($id,$change_id)
    {
        $idArray = explode(',', $id);

        $query =  DB::table('graphics_paramenters')
            ->join('graphic_times', 'graphics_paramenters.GrapicsID', '=', 'graphic_times.GraphicsID')
            ->join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->whereIn('BlogsID', $idArray) 
            ->select('graphic_times.id as GTid','graphic_times.Name as GTName', 'graphic_times.Change as Change', 'graphic_times.StartTime as STime', 'graphic_times.EndTime as ETime', 'parameters.Name as PName', 'parameters.Min as Min', 'parameters.Max as Max', 'graphics_paramenters.*');
            if ($change_id == 1) {
                $query->whereTime('graphic_times.StartTime', '>=', '08:00')
                      ->whereTime('graphic_times.EndTime', '<=', '20:00');
            } elseif ($change_id == 2) {
                $query->where(function ($query) {
                    $query->whereTime('graphic_times.StartTime', '<', '08:00')
                          ->orWhereTime('graphic_times.EndTime', '>', '20:00');
                });
            }
            return $query->get();
    }
    public function getParamsForUserCount($id,$change_id)
    {
        $idArray = explode(',', $id);
    
        $query = DB::table('graphics_paramenters')
            ->join('graphic_times', 'graphics_paramenters.GrapicsID', '=', 'graphic_times.GraphicsID')
            ->join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->whereIn('BlogsID', $idArray)
            ->select('graphic_times.id as GTid', 'graphic_times.Name as GTName', 'graphic_times.Change as Change', 'graphic_times.StartTime as STime', 'graphic_times.EndTime as ETime', 'parameters.Name as PName', 'parameters.Min as Min', 'parameters.Max as Max', 'graphics_paramenters.*');
        
        if ($change_id == 1) {
            $query->whereTime('graphic_times.StartTime', '>=', '08:00')
                  ->whereTime('graphic_times.EndTime', '<=', '20:00');
        } elseif ($change_id == 2) {
            $query->where(function ($query) {
                $query->whereTime('graphic_times.StartTime', '<', '08:00')
                      ->orWhereTime('graphic_times.EndTime', '>', '20:00');
            });
        }
        $data = $query->count();
        return $data;
    }
    private function create(Request $request)
    {
        $GParams = GraphicsParamenters::create([
            'OrderNumber'=>$request->OrderNumber,
            'ParametersID' => $request->ParametersID['value'],
            'FactoryStructureID' => $request->FactoryStructureID['value'],
            'BlogsID'=>$request->BlogID['value'],
            'GrapicsID' => $request->GrapicsID['value'],
            'SourceID' => $request->SourceID['value'],
            'CurrentTime' => date('Y-m-d H:i:s', strtotime($request->CurrentTime)),
            'EndingTime' => date('Y-m-d H:i:s', strtotime($request->EndingTime)),
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'unit' => $GParams
        ]);
    }

    private function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:units,id',
            'Name' => 'required|string|max:255',
            'ShortName' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $unit = GraphicsParamenters::find($request->id);
        $unit->update([
            'Name' => $request->Name,
            'ShortName' => $request->ShortName,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli yangilandi",
            'unit' => $unit
        ]);
    }

    public function delete(Request $request, $id)
    {
        try {
            $unit = GraphicsParamenters::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
