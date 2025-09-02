<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SvodkaFormula;
class SvodkaFormulaController extends Controller
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
        $units = SvodkaFormula::all();
        return response()->json($units);
    }
    private function getRowUnit($id)
    {
        $row = SvodkaFormula::where('param_id', $id)->first();
        return response()->json([
            'status' => 200,
            'data' => $row,
        ]);
    }
    private function create(Request $request)
    {
        // dd($request);

        $row = SvodkaFormula::updateOrCreate(
            ['param_id' => $request['param_id']],
            [
                'page_id_blog'=>$request['page_id_blog'],
                'sex_id'   => $request['sex_id'],
                'page_id'  => $request['page_id'],
                'group_id' => $request['group_id'],
                'tokens'   => $request['tokens'],   // casts -> json
                'comment'  => $request['comment'] ?? null,
            ]
        );

        return response()->json([
            'status' => 200,
            'message' => 'Formula saqlandi',
            'data' => $row,
        ], 200);
    }

    private function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:units,id',
            'Name' => 'required|string|max:255',
            'NameRus' => 'required|string|max:255',
            'ShortName' => 'required|string|max:255',
            'ShortNameRus' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $unit = SvodkaFormula::find($request->id);
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
            $unit = SvodkaFormula::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
