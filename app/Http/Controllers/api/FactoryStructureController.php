<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FactoryStructure;
use DB;
class FactoryStructureController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return $this->getRowUnit($id);
        }

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
        
        $fstructure = FactoryStructure::all();
        return response()->json($fstructure);
    }
    private function getRowUnit($id)
    {
        $unit = FactoryStructure::find($id);
        return response()->json($unit);
    }
    public function getForUser($id)
    {
        // dd($id);
        $idsArray = explode(',', $id);
        $unit = FactoryStructure::whereIn('id', $idsArray)->get();
        return response()->json($unit);
    }
    private function create(Request $request)
    {
        // $request->validate([
        //     'Name' => 'required|string|max:255',
        //     'ShortName' => 'required|string|max:255',
        //     'Comment' => 'nullable|string|max:255',
        // ]);

        $unit = FactoryStructure::create([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'ShortName' => $request->ShortName,
            'ShortNameRus' => $request->ShortNameRus,
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
            'Name' => 'required|string|max:255',
            'NameRus' => 'required|string|max:255',
            'ShortName' => 'required|string|max:255',
            'ShortNameRus' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $unit = FactoryStructure::find($request->id);
        $unit->update([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'ShortName' => $request->ShortName,
            'ShortNameRus' => $request->ShortNameRus,
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
            $unit = FactoryStructure::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
