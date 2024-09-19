<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ValuesParameters;
use DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Events\TimeUpdated;
use Carbon\Carbon;
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
        // dd($id,$change,$date);
        // $date = Carbon::parse($date_at)->format('Y-m-d');
        $idArray = explode(',', $id);
        return ValuesParameters::whereIn('BlogID', $idArray)
        // ->whereDate('created_at', $date)
        ->get();
    }
    // private function create(Request $request)
    // {
    //     $uuidString = (string) Str::uuid();
    //     try {
    //         ValuesParameters::updateOrInsert(
    //             [
    //                 'ParametersID' => $request->ParametersID,
    //                 'SourcesID' => $request->SourceID,
    //                 'TimeID' => $request->GTid,
    //             ],
    //             [
    //                 'id' => $uuidString,
    //                 'Value' => $request->Value,
    //                 'GraphicsTimesID' => $request->GrapicsID,
    //                 'BlogID' => $request->BlogsID,
    //                 'Comment' => $request->Comment,
    //                 'updated_at' => now()
    //             ]
    //         );
            
    //         $unit = ValuesParameters::where('id', $uuidString)->first();


    //         return response()->json([
    //             'status' => 200,
    //             'message' => "Ma`lumot muvafaqiyatli qo'shildi",
    //             'unit' => $unit
    //         ]);

    //     } catch (\Exception $e) {
    //         \Log::error('Error creating/updating unit:', ['error' => $e->getMessage()]);

    //         return response()->json([
    //             'status' => 500,
    //             'message' => 'There was an error processing the request.',
    //             'error' => $e->getMessage()
    //         ]);
    //     }
    // }
    private function create(Request $request)
    {
        // $parameters = [
        //     'ParametersID' => $request->ParametersID,
        //     'SourcesID' => $request->SourceID,
        //     'TimeID' => $request->GTid,
        //     'GraphicsTimesID' => $request->GrapicsID,
        // ];
        // // dd($parameters);
        // $existingRecord = ValuesParameters::where($parameters)->first();
    
        try {
            // if ($existingRecord) {
            //     $existingRecord->update([
            //         'Value' => $request->Value,
            //         'BlogID' => intval($request->BlogsID), 
            //         'Comment' => $request->Comment,
            //         'updated_at' => now(),
            //         'Changed' => now(),
            //         'Changer' => $request->userId,
            //     ]);
    
            //     $unit = $existingRecord;
            //     $message = "Data successfully updated";
            $uuidString = (string) Str::uuid();
            $unit = ValuesParameters::create([
                'id' => $uuidString,
                'ParametersID' => $request->ParametersID,
                'SourcesID' => $request->SourceID,
                'TimeID' => $request->GTid, // Ensure this field is set
                'GraphicsTimesID' => $request->GrapicsID,
                'Value' => $request->Value,
                'BlogID' => intval($request->BlogsID), // Ensure this field is set
                'Comment' => $request->Comment,
                'Created' => now(),
                'Creator' => $request->userId,
                'updated_at' => now(), // For consistency
            ]);
            $message = "Data successfully created";
    
            
    
            return response()->json([
                'status' => 200,
                'message' => $message,
                'unit' => $unit
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'There was an error processing the request.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function update(Request $request)
    {
        // dd($request);
        // $request->validate([
        //     'id' => 'required|integer|exists:units,id',
        //     'Name' => 'required|string|max:255',
        //     'ShortName' => 'required|string|max:255',
        //     'Comment' => 'nullable|string|max:255',
        // ]);

        $unit = ValuesParameters::find($request->id);
        $unit->update([
            'Value' => $request->Value,
            'Comment' => $request->Comment,
            'Changer' => $request->userId,
            'Changed' =>now(),

        ]);

        return response()->json([
            'status' => 200,
            'message' => "muvafaqiyatli yangilandi",
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
  
        public function sendTimeUpdate()
        {
            $currentTime = now()->format('H:i'); 
    
            broadcast(new TimeUpdated($currentTime));
            // event();
            return response()->json(['status' => 'Yangilandi!']);
        }
}
