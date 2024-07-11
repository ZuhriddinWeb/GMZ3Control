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
                return $this->update($request,$id);
            case 'DELETE':
                return $this->delete($request,$id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }

    private function index()
    {
        $params = Parameters::join('paramenters_types', 'parameters.ParametrTypeID', '=', 'paramenters_types.id')
        ->join('units', 'parameters.UnitsID', '=', 'units.id')
        ->select('parameters.id as Uuid','parameters.Name','parameters.ShortName','parameters.Comment','paramenters_types.Name as PName','paramenters_types.id as Pid','units.Name as UName','units.id as Uid')
        ->get();
        return response()->json($params);
    }
    private function getRowParam($id)
    {
        $params = Parameters::join('paramenters_types', 'parameters.ParametrTypeID', '=', 'paramenters_types.id')
        ->join('units', 'parameters.UnitsID', '=', 'units.id')
        ->where('parameters.id',$id)
        ->select('parameters.id as Uuid','parameters.Name','parameters.ShortName','parameters.Comment','paramenters_types.Name as PName','paramenters_types.id as Pid','units.Name as UName','units.id as Uid')
        ->get();
        return response()->json($params);
    }
    private function create(Request $request)
    {
        $uuid = Str::uuid();
        $uuidString = $uuid->toString();

        $params = Parameters::create([
            'id'=>$uuidString,
            'Name' => $request->Name,
            'ShortName' => $request->ShortName,
            'ParametrTypeID'=>$request->ParamsTypeID['value'],
            'UnitsID'=>$request->UnitsID['value'],
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
        // $request->validate([
        //     'id' => 'required|integer|exists:units,id',
        //     'Name' => 'required|string|max:255',
        //     'ShortName' => 'required|string|max:255',
        //     'Comment' => 'nullable|string|max:255',
        // ]);

        $params = Parameters::find($request->id);
        $params->update([
            'Name' => $request->Name,
            'ShortName' => $request->ShortName,
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
