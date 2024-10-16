<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sources;

class SourcesController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return $this->getRowSource($id);
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
        $units = Sources::all();
        return response()->json($units);
    }
    private function getRowSource($id)
    {
        $source = Sources::find($id);
        return response()->json($source);
    }
    private function create(Request $request)
    {
        $request->validate([
            'Name' => 'required|string|max:255',
            'ShortName' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $unit = Sources::create([
            'Name' => $request->Name,
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

        $source = Sources::find($request->id);
        $source->update([
            'Name' => $request->Name,
            'ShortName' => $request->ShortName,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli yangilandi",
            'unit' => $source
        ]);
    }

    private function delete(Request $request)
    {
        // $request->validate([
        //     'id' => 'required|integer|exists:units,id',
        // ]);

        $source = Sources::find($request->id);
        $source->delete();

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli o'chirildi",
        ]);
    }
}
