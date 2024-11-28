<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Documents;
use DB;
class DocumentsController extends Controller
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
        $units = Documents::all();
        return response()->json($units);
    }
    public function generate($user_id, $start)
    {
        // Foydalanuvchiga tegishli `FactoryStructureID` va `ParametersID`larni olish
        $userData = Documents::where('user_id', $user_id)->first();
    
        if (!$userData) {
            return response()->json([
                'status' => 404,
                'message' => "Foydalanuvchi ma'lumotlari topilmadi"
            ]);
        }
    
        $factoryStructureIDs = $userData->FactoryStructureID;
        $parametersIDs = $userData->ParametersID;
    
        // `FactoryStructureID`, `ParametersID` va `created_at` sanasini filtrlash
        $query = DB::table('values_parameters')
            ->join('parameters', 'values_parameters.ParametersID', '=', 'parameters.id') // `parameters` jadvali bilan join
            ->whereIn('values_parameters.FactoryStructureID', $factoryStructureIDs)
            ->where(function ($query) use ($parametersIDs) {
                foreach ($parametersIDs as $params) {
                    $query->orWhereIn('values_parameters.ParametersID', $params);
                }
            })
            ->whereDate('values_parameters.created_at', '=', $start) // Tanlangan sanani filtrlash
            ->select(
                'values_parameters.id',
                'values_parameters.ParametersID',
                'parameters.Name as PName', // `parameters` jadvalidan `Name` ustuni
                'values_parameters.SourcesID',
                'values_parameters.TimeID',
                'values_parameters.FactoryStructureID',
                'values_parameters.BlogID',
                'values_parameters.Value',
                'values_parameters.Comment',
                'values_parameters.GraphicsTimesID',
                'values_parameters.Created',
                'values_parameters.Creator',
                'values_parameters.Changed',
                'values_parameters.Changer',
                'values_parameters.created_at',
                'values_parameters.updated_at'
            )
            ->get();
    
        // Natijani JSON formatda qaytarish
        return response()->json([
            'status' => 200,
            'data' => $query
        ]);
    }
    
    
    private function getRowUnit($user_id)
    {
        dd($user_id);
        // Foydalanuvchiga tegishli `FactoryStructureID` va `ParametersID`larni olish
        $userData = Documents::where('user_id', $user_id)->first();
    
        if (!$userData) {
            return response()->json([
                'status' => 404,
                'message' => "Foydalanuvchi ma'lumotlari topilmadi"
            ]);
        }
    
        $factoryStructureIDs = $userData->FactoryStructureID;
        $parametersIDs = $userData->ParametersID;
    
        // `FactoryStructureID` va `ParametersID`larni moslashtirish va `join` qilish
        $query = DB::table('values_parameters')
            ->join('parameters', 'values_parameters.ParametersID', '=', 'parameters.id') // Join parameters jadvali
            
            ->whereIn('values_parameters.FactoryStructureID', $factoryStructureIDs)
            ->where(function ($query) use ($parametersIDs) {
                foreach ($parametersIDs as $params) {
                    $query->orWhereIn('values_parameters.ParametersID', $params);
                }
            })
            ->select(
                'values_parameters.id',
                'values_parameters.ParametersID',
                'parameters.Name as PName', // `parameters` jadvalidan `Name` ustuni
                'values_parameters.SourcesID',
                'values_parameters.TimeID',
                'values_parameters.FactoryStructureID',
                'values_parameters.BlogID',
                'values_parameters.Value',
                'values_parameters.Comment',
                'values_parameters.GraphicsTimesID',
                'values_parameters.Created',
                'values_parameters.Creator',
                'values_parameters.Changed',
                'values_parameters.Changer',
                'values_parameters.created_at',
                'values_parameters.updated_at'
            )
            ->get();
    
        // Natijani JSON formatda qaytarish
        return response()->json([
            'status' => 200,
            'data' => $query
        ]);
    }
    
    
    public function getUserData($id)
    {
        // dd($id);
        return Documents::where('user_id',$id)->get();
    }
    private function create(Request $request)
    {
        // `user_id` bo‘yicha mavjud yozuvni topishga harakat qilamiz
        $existingRecord = Documents::where('user_id', $request->user_id)->first();
    
        if ($existingRecord) {
            // Agar yozuv mavjud bo'lsa, uni yangilaymiz
            $existingRecord->update([
                'FactoryStructureID' => $request->id,
                'ParametersID' => $request->parameters,
            ]);
    
            return response()->json([
                'status' => 200,
                'message' => "Yozuv muvaffaqiyatli yangilandi",
                'unit' => $existingRecord
            ]);
        } else {
            // Agar yozuv mavjud bo'lmasa, yangi yozuv yaratamiz
            $unit = Documents::create([
                'user_id' => $request->user_id,
                'FactoryStructureID' => $request->id,
                'ParametersID' => $request->parameters,
            ]);
    
            return response()->json([
                'status' => 200,
                'message' => "Yozuv muvaffaqiyatli qo'shildi",
                'unit' => $unit
            ]);
        }
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

        $unit = Documents::find($request->id);
        $unit->update([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'ShortName' => $request->ShortName,
            'ShortNameRus' => $request->ShortNameRus,
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
            $unit = Documents::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
