<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blogs;
class BlogsController extends Controller
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
        $units = Blogs::join('factory_structures','blogs.StructureID','=','factory_structures.id')
        ->select('factory_structures.Name as SName','blogs.*')
        ->get();
        return response()->json($units);
    }
    private function getRowUnit($id)
    {
        return  Blogs::find($id);
    }
    private function create(Request $request)
    {
        $validatedData = $request->validate([
            'StructureID' => 'required|integer', 
            'Name' => 'required|string|max:255',
            'NameRus' => 'nullable|string|max:255',
            'ShortName' => 'nullable|string|max:255',
            'ShortNameRus' => 'nullable|string|max:255',
            'Comment' => 'nullable|string',
        ]);
        $blog = Blogs::create([
            'StructureID' => $validatedData['StructureID'],
            'Name' => $validatedData['Name'],
            'NameRus' => $validatedData['NameRus'],
            'ShortName' => $validatedData['ShortName'],
            'ShortNameRus' => $validatedData['ShortNameRus'],
            'Comment' => $validatedData['Comment'],
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'unit' => $blog
        ]);
    }

    private function update(Request $request)
    {
        $validatedData = $request->validate([
            'StructureID' => 'required|integer', 
            'Name' => 'required|string|max:255',
            'NameRus' => 'nullable|string|max:255',
            'ShortName' => 'nullable|string|max:255',
            'ShortNameRus' => 'nullable|string|max:255',
            'Comment' => 'nullable|string',
        ]);

        $unit = Blogs::find($request->id);
        $unit->update([
            'StructureID' => $validatedData['StructureID'],
            'Name' => $validatedData['Name'],
            'NameRus' => $validatedData['NameRus'],
            'ShortName' => $validatedData['ShortName'],
            'ShortNameRus' => $validatedData['ShortNameRus'],
            'Comment' => $validatedData['Comment'],
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
            $unit = Blogs::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
