<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NumberPage;

class NumberPageController extends Controller
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
        $units = NumberPage::join('factory_structures','number_pages.StructureID','=','factory_structures.id')
        ->select('factory_structures.Name as SName','number_pages.*')->get();
        return response()->json($units);
    }
    public function getRowPage($id)
    {
        $unit = NumberPage::join('factory_structures','number_pages.StructureID','=','factory_structures.id')
        ->where('number_pages.StructureID',$id)
        ->select('factory_structures.Name as SName','number_pages.*')->get();
        return response()->json($unit);
    }
    private function getRowUnit($id)
    {
        
        $unit = NumberPage::join('factory_structures','number_pages.StructureID','=','factory_structures.id')
        ->where('number_pages.id',$id)
        ->select('factory_structures.Name as SName','number_pages.*')->get();
        return response()->json($unit);
    }
    public function getRowPages($id)
    {
        $unit = NumberPage::where('StructureID',$id)->get();
        return response()->json($unit);
    }
    private function create(Request $request)
    {
        // $request->validate([
        //     'Name' => 'required|string|max:255',
        //     'NameRus' => 'required|string|max:255',
        //     'ShortName' => 'required|string|max:255',
        //     'ShortNameRus' => 'required|string|max:255',
        //     'Comment' => 'nullable|string|max:255',
        // ]);

        $unit = NumberPage::create([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'StructureID' => $request->StructureID,
            'NumberPage' => $request->NumberPage,
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
        // $request->validate([
        //     'id' => 'required|integer|exists:units,id',
        //     'Name' => 'required|string|max:255',
        //     'NameRus' => 'required|string|max:255',
        //     'ShortName' => 'required|string|max:255',
        //     'ShortNameRus' => 'required|string|max:255',
        //     'Comment' => 'nullable|string|max:255',
        // ]);

        $unit = NumberPage::find($request->id);
        $unit->update([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'StructureID' => $request->StructureID,
            'NumberPage' => $request->NumberPage,
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
            $unit = NumberPage::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
    
}
