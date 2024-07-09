<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParamentersTypes;

class ParamTypesController extends Controller
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
                return $this->update($request);
            case 'DELETE':
                return $this->delete($request);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }

    private function index()
    {
        $params = ParamentersTypes::all();
        return response()->json($params);
    }
    private function getRowUnit($id)
    {
        $unit = ParamentersTypes::find($id);
        return response()->json($unit);
    }
    private function create(Request $request)
    {
        $request->validate([
            'Name' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $params = ParamentersTypes::create([
            'Name' => $request->Name,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Muvafaqiyatli qo'shildi",
            'unit' => $params
        ]);
    }

    private function update(Request $request)
    {

        $params = ParamentersTypes::find($request->id);
        $params->update([
            'Name' => $request->Name,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli yangilandi",
            'unit' => $params
        ]);
    }

    private function delete(Request $request)
    {
        $unit = ParamentersTypes::find($request->id);
        $unit->delete();

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli o'chirildi",
        ]);
    }
}
