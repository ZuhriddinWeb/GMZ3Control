<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeFactory;

class FactoryController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return $this->getRowFactory($id);
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
        $factory = TypeFactory::all();
        return response()->json($factory);
    }
    private function getRowFactory($id)
    {
        $factory = TypeFactory::find($id);
        return response()->json($factory);
    }
    private function create(Request $request)
    {
        $request->validate([
            'Name' => 'required|string|max:255',
            'NameRus' => 'required|string|max:255',
            'ShortName' => 'required|string|max:255',
            'ShortNameRus' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $factory = TypeFactory::create([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'ShortName' => $request->ShortName,
            'ShortNameRus' => $request->ShortNameRus,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'unit' => $factory
        ]);
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

        $factory = TypeFactory::find($request->id);
        $factory->update([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'ShortName' => $request->ShortName,
            'ShortNameRus' => $request->ShortNameRus,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli yangilandi",
            'unit' => $factory
        ]);
    }

    public function delete(Request $request, $id)
    {
        try {
            $factory = TypeFactory::findOrFail($id);
            $factory->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }

}
