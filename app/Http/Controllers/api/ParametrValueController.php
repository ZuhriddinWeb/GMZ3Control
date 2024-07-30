<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ValuesParameters;
use DB;
use Illuminate\Support\Str;

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
    private function index()
    {
        $units = ValuesParameters::all();
        return response()->json($units);
    }
    private function getRowPram($id)
    {
        // Rizaqulov shuhrat sayfiddinovich ergashv kamoliddin xolboyevich
        $currentTime = date("H:i");
        $unit = DB::table('graphics_paramenters')
            ->join('graphic_times', 'graphics_paramenters.GrapicsID', '=', 'graphic_times.GraphicsID')
            ->join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->join('values_parameters', 'graphics_paramenters.ParametersID', '=', 'values_parameters.ParametersID')
            ->where('BlogsID', '=', $id)
            ->whereTime('graphic_times.StartTime', '>=', '8:00')
            ->whereTime('graphic_times.EndTime', '<=', '10:00')
            ->select('values_parameters.id as pvuid','graphic_times.id as GTid','graphic_times.Name as GTName', 'graphic_times.Change as Change', 'graphic_times.StartTime as STime', 'graphic_times.EndTime as ETime', 'parameters.Name as PName', 'parameters.Min as Min', 'parameters.Max as Max', 'graphics_paramenters.*')
            ->get();
            // dd($unit);
        return response()->json($unit);
    }
    private function create(Request $request)
    {
        // dd($request->GTid);
        $uuid = Str::uuid();
        $uuidString = $uuid->toString();

        try {
            ValuesParameters::updateOrInsert(
                [
                    'ParametersID' => $request->ParametersID,
                    'SourcesID' => $request->SourceID,
                ],
                [
                    'id' => $uuidString,
                    'Value' => $request->Value,
                    'TimeID' => $request->GTid,
                    'Comment' => $request->Comment,
                    'GraphicsTimesID' => $request->GrapicsID,
                    'updated_at' => now()
                ]
            );
            
            $unit = ValuesParameters::where('id', $uuidString)->first();


            return response()->json([
                'status' => 200,
                'message' => "Javob muvafaqiyatli qo'shildi",
                'unit' => $unit
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating/updating unit:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 500,
                'message' => 'There was an error processing the request.',
                'error' => $e->getMessage()
            ]);
        }
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
