<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parameters;
use Illuminate\Support\Str;

class ParamsController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return $this->getRowParam($id);
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
        $params = Parameters::join('paramenters_types', 'parameters.ParametrTypeID', '=', 'paramenters_types.id')
            ->join('units', 'parameters.UnitsID', '=', 'units.id')
            ->leftJoin('servers', 'parameters.ServerId', '=', 'servers.id') 
            ->select('parameters.id as Uuid','parameters.ShortName as ShName', 'parameters.Name','parameters.WinCC','parameters.ServerId', 'parameters.ShortName', 'parameters.Comment', 'parameters.Min', 'parameters.Max', 'paramenters_types.Name as PName', 'paramenters_types.id as Pid', 'units.Name as UName', 'units.id as Uid','servers.name as IpName', 'servers.id as IpId')
            ->get();
        return response()->json($params);
    }
    private function getRowParam($id)
    {
        // dd($id);
        return Parameters::join('paramenters_types', 'parameters.ParametrTypeID', '=', 'paramenters_types.id')
        ->join('units', 'parameters.UnitsID', '=', 'units.id')
        ->leftJoin('servers', 'parameters.ServerId', '=', 'servers.id') 
        ->where('parameters.id', $id)
        ->select(
            'parameters.id as Uuid',
            'parameters.WinCC',
            'parameters.Min',
            'parameters.Max',
            'parameters.NameRus',
            'parameters.ShortNameRus',
            'parameters.Name',
            'parameters.ShortName',
            'parameters.Comment',
            'paramenters_types.Name as PName',
            'paramenters_types.id as Pid',
            'units.Name as UName',
            'units.id as Uid',
            'servers.name as IpName',
            'servers.id as Ipid'
        )
        ->first();
    
    }

    private function create(Request $request)
    {
        $uuid = Str::uuid();
        $uuidString = $uuid->toString();

        $params = Parameters::create([
            'id' => $uuidString,
            'Name' => $request->Name,
            'NameRus' => $request->Name,
            'ShortName' => $request->ShortName,
            'ShortNameRus' => $request->ShortNameRus,
            'ParametrTypeID' => $request->ParamsTypeID,
            'WinCC' => $request->WinCC,
            'ServerId' => $request->ServerId,
            'Min' => $request->Min,
            'Max' => $request->Max,
            'UnitsID' => $request->UnitsID,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'unit' => $params
        ]);
    }

    private function update(Request $request)
    {
        
        $params = Parameters::find($request->id);
        $params->update([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'ShortName' => $request->ShortName,
            'ShortNameRus' => $request->ShortNameRus,
            'WinCC' => $request->WinCC,
            'ServerId' => $request->ServerId,
            'ParametrTypeID' => $request->ParamsTypeID,
            'Min' => $request->Min,
            'Max' => $request->Max,
            'UnitsID' => $request->UnitsID,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli yangilandi",
            'unit' => $params
        ]);
    }

    public function delete(Request $request, $id)
    {
        try {
            $params = Parameters::findOrFail($id);
            $params->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
