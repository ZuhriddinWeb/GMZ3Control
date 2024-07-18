<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ValuesParameters;
use DB;

class ParametrValueController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return $this->getRowPram($id);
        }

        switch ($request->method()) {
            case 'GET':
                return $this->index();
            case 'POST':
                return $this->create($request);
            case 'PUT':
                return $this->update($request, $id);
            case 'DELETE':
                return $this->delete($request, $id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }
// Ro'ziboyev muroddin  
    private function index()
    {
        $units = ValuesParameters::all();
        return response()->json($units);
    }
    private function getRowPram($id)
    {
        $currentTime = date("H:i");
        $unit = DB::table('graphics_paramenters')
            ->join('graphic_times', 'graphics_paramenters.GrapicsID', '=', 'graphic_times.GraphicsID')
            ->join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->where('BlogsID', '=', $id)
            ->whereTime('graphic_times.StartTime', '>=', '08:00')
            ->whereTime('graphic_times.EndTime', '<=', '20:00')
            ->select('graphic_times.Name as GTName', 'graphic_times.Change as Change', 'graphic_times.StartTime as STime', 'graphic_times.EndTime as ETime', 'parameters.Name as PName', 'graphics_paramenters.*')
            ->get();
        return response()->json($unit);
    }
    private function create(Request $request)
    {
        dd($request);
        $request->validate([
            'Name' => 'required|string|max:255',
            'ShortName' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $unit = ValuesParameters::updateOrCreate([
            'ParametersID' => $request->ParametersID,
            'ShortName' => $request->ShortName,
            'Comment' => $request->Comment,
        ]);
        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'unit' => $unit
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

        $unit = ValuesParameters::find($request->id);
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
            $unit = ValuesParameters::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }

}
