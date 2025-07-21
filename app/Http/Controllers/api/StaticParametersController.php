<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StaticParameters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class StaticParametersController extends Controller
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
        //  $params = Parameters::join('paramenters_types', 'parameters.ParametrTypeID', '=', 'paramenters_types.id')
        //     ->join('units', 'parameters.UnitsID', '=', 'units.id')
        //     ->leftJoin('servers', 'parameters.ServerId', '=', 'servers.id') 
        //     ->select('parameters.id as Uuid','parameters.ShortName as ShName', 'parameters.Name','parameters.WinCC','parameters.ServerId', 'parameters.ShortName', 'parameters.Comment', 'parameters.Min', 'parameters.Max', 'paramenters_types.Name as PName', 'paramenters_types.id as Pid', 'units.Name as UName', 'units.id as Uid','servers.name as IpName', 'servers.id as IpId')
        //     ->get();
        $units = StaticParameters::join('period_types','static_parameters.period_type_id','=','period_types.id')
        ->join('parameters','static_parameters.ParameterID','=','parameters.id')
        ->join('units', 'parameters.UnitsID', '=', 'units.id')
        ->select('units.Name as UName','period_types.name as PTName','parameters.Name as PName','static_parameters.*')
        ->get();
        return response()->json($units);
    }
    public function periodType()
    {
        return  DB::table('period_types')->get();
        
    }
    private function getRowUnit($id)
    {
        $unit = StaticParameters::find($id);
        return response()->json($unit);
    }
    private function create(Request $request)
    {
        // dd($request);
        $uuid = Str::uuid();
        $uuidString = $uuid->toString();


        $unit = StaticParameters::create([
            'id'=>$uuidString,
            'ParameterID' => $request->ParameterID,
            'value' => $request->Value,
            'period_type_id' => $request->PeriodTypeId,
            'period_start_date' => $request->PeriodStartDate,
            'period_end_date' => $request->PeriodEndDate,
            'Comment'=>$request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'unit' => $unit
        ]);
    }

    private function update(Request $request)
    {
        $unit = StaticParameters::find($request->id);
        $unit->update([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'value' => $request->Value,
            'period_type_id' => $request->PeriodTypeId,
            'period_start_date' => $request->PeriodStartDate,
            'period_end_date' => $request->PeriodEndDate,
            'description'=>$request->Description,
            'unit' => $request->UnitId,
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
            $unit = StaticParameters::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
