<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Groups;
class GroupsController extends Controller
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
                return $this->update($request, $id);
            case 'DELETE':
                return $this->delete($request, $id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }

    private function index()
    {
        $units = Groups::all();
        return response()->json($units);
    }
    public function getRowGroup($idS, $idP)
    {
        $unit = Groups::join('factory_structures', 'groups.StructureID', '=', 'factory_structures.id')
            ->join('number_pages', 'number_pages.id', '=', 'groups.PageID')
            ->where('groups.StructureID', $idS)
            ->where('groups.PageID', $idP)
            ->select('factory_structures.Name as SName', 'number_pages.Name as NName', 'groups.*')->get();
        return response()->json($unit);
    }
    public function getRowGroupEdit($id)
    {
        $unit = Groups::join('factory_structures', 'groups.StructureID', '=', 'factory_structures.id')
            ->join('number_pages', 'number_pages.id', '=', 'groups.PageID')
            ->where('groups.id', $id)
            ->select('factory_structures.Name as SName', 'number_pages.Name as NName', 'groups.*')->get();
        return response()->json($unit);
    }
    private function create(Request $request)
    {
        $unit = Groups::create([
            'StructureID' => $request->StructureID,
            'PageID' => $request->PageID,
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
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
        $unit = Groups::find($request->id);
        $unit->update([
            'StructureID' => $request->StructureID,
            'PageID' => $request->PageID,
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
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
            $unit = Groups::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
