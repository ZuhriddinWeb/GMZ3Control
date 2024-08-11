<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ValuesParameters;
use DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
class ParametrValueController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return $this->getByBlog($id);
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
        $units = ValuesParameters::all();
        return response()->json($units);
    }
    public function getParamsForId($id){
        dd($id);
        $result = ValuesParameters::where('ParametersID', $id)->get();
    }
    private function getByBlog($id)
    {
        $idArray = explode(',', $id);
        return ValuesParameters::where('BlogID', $idArray)->get();
    }
    private function create(Request $request)
    {
        $uuidString = (string) Str::uuid();
        try {
            ValuesParameters::updateOrInsert(
                [
                    'ParametersID' => $request->ParametersID,
                    'SourcesID' => $request->SourceID,
                    'TimeID' => $request->GTid,
                    'GraphicsTimesID' => $request->GrapicsID,
                ],
                [
                    'id' => $uuidString,
                    'Value' => $request->Value,
                    'BlogID' => $request->BlogsID,
                    'Comment' => $request->Comment,
                    'updated_at' => now()
                ]
            );
            
            $unit = ValuesParameters::where('id', $uuidString)->first();


            return response()->json([
                'status' => 200,
                'message' => "Ma`lumot muvafaqiyatli qo'shildi",
                'unit' => $unit
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating/updating unit:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 500,
                'message' => 'There was an error processing the request.',
                'error' => $e->getMessage()
            ]);
        }
    }

    private function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:units,id',
            'Name' => 'required|string|max:255',
            'ShortName' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $unit = ValuesParameters::find($request->id);
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

    public function delete(Request $request, $id)
    {
        try {
            $unit = ValuesParameters::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }

}
