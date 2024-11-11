<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GraphicTimes;
use Carbon\Carbon;
use DB;
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
            ->select('graphics.Name as GName', 'graphics.Comment', 'graphic_times.*')
            ->get('graphics');
        return response()->json($GraphicTimes);
    }
    public function getTimes($id)
    {
        $GraphicTimes = GraphicTimes::join('graphics', 'graphic_times.GraphicsID', '=', 'graphics.id')
            ->where('graphic_times.GraphicsID',$id)
            ->select('graphics.Name as GName', 'graphics.Comment', 'graphic_times.*')
            ->get('graphics');
        return response()->json($GraphicTimes);
    }
    public function time($id)
    {
        $GraphicTimes = GraphicTimes::where('graphic_times.id',$id)
            ->first();
        return $GraphicTimes;
    }
    private function getRowUnit($id)
    {
        return GraphicTimes::join('graphics', 'graphic_times.GraphicsID', '=', 'graphics.id')
            ->join('changes', 'graphic_times.change', '=', 'changes.id')
            ->where('graphic_times.id', $id)
            ->select('graphics.Name as GName', 'graphics.id as Gid', 'changes.change as Change', 'changes.id as Chid', 'graphic_times.*')
            ->first();
    }
    public function getRowTimes($id, $GparamID, $GPid, $GrapicsID)
    {
        // dd($GparamID);
        return GraphicTimes::join('graphics', 'graphic_times.GraphicsID', '=', 'graphics.id')
            ->join('changes', 'graphic_times.change', '=', 'changes.id')
            ->where('graphics.id', $id)
            ->select('graphics.Name as GName', 'graphics.id as Gid', 'changes.change as Change', 'changes.id as Chid', 'graphic_times.*', DB::raw("'$GrapicsID' as GrapicsID"), DB::raw("'$GparamID' as GparamID"), DB::raw("'$GPid' as GPid"))
            ->get();
    }
    public function getRowFormuleTimes($GparamID)
    {
        // dd($GparamID);
        return GraphicTimes::join('graphics', 'graphic_times.GraphicsID', '=', 'graphics.id')
            ->join('changes', 'graphic_times.change', '=', 'changes.id')
            ->where('graphic_times.GraphicsID', $GparamID)
            ->select('graphics.Name as GName', 'graphics.id as Gid', 'changes.change as Change', 'changes.id as Chid', 'graphic_times.*')
            ->get();
    }
    private function create(Request $request)
    {

        $unit = GraphicTimes::create([
            'GraphicsID' => $request->GraphicId,
            'Change' => $request->ChangeId,
            'Name' => Carbon::parse($request->Name)->addHours(5)->format('H:i:s'),
            'StartTime' => Carbon::parse($request->Name)->addHours(5)->format('H:i:s'),
            'EndTime' => Carbon::parse($request->EndTime)->addHours(5)->format('H:i:s'),
            'Current' => $request->Current,

        ]);
        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'unit' => $unit
        ]);
    }

    private function update(Request $request)
    {


        $unit = GraphicTimes::find($request->id);
        $unit->update([
            'GraphicsID' => $request->GraphicId,
            'Change' => $request->ChangeId,
            'Name' => Carbon::parse($request->Name)->addHours(5)->format('H:i:s'),
            'StartTime' => Carbon::parse($request->Name)->addHours(5)->format('H:i:s'),
            'EndTime' => Carbon::parse($request->EndTime)->addHours(5)->format('H:i:s'),
        ]);

        return response()->json([
            'status' => 200,
            'message' => "muvafaqiyatli yangilandi",
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
