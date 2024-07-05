<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GraphicTimes;

class GraphicTimesController extends Controller
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
        $GraphicTimes = GraphicTimes::join('graphics', 'graphic_times.GraphicsID', '=', 'graphics.id')
        ->select('graphics.Name as GName','graphics.Comment','graphic_times.*')
        ->get('graphics');
        return response()->json($GraphicTimes);
    }
    private function getRowUnit($id)
    {
        $unit = GraphicTimes::find($id);
        return response()->json($unit);
    }
    private function create(Request $request)
    {
        $unit = GraphicTimes::create([
            'GraphicsID' => $request->GraphicId['value'],
            'Change' => $request->ChangeId['value'],
            'Name' => $request->Name,            
            'StartTime' => $request->Name,
            'EndTime' => $request->EndTime,

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

        $unit = GraphicTimes::find($request->id);
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

    private function delete(Request $request)
    {
        $GraphicTimes = GraphicTimes::find($request->id);
        $GraphicTimes->delete();

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli o'chirildi",
        ]);
    }
}
