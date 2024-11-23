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
    public function getParamsForId($id)
    {
        dd($id);
        $result = ValuesParameters::where('ParametersID', $id)->get();
    }
    public function getByBlog($factoryId, $current)
    {
        $current = Carbon::parse($current)->toDateString();
    
        // FactoryStructureID'ni arrayga aylantirish
        $idArray = explode(',', $factoryId);
    
        // Query yaratish
        $result = ValuesParameters::whereIn('FactoryStructureID', $idArray)
            ->where(function ($query) use ($current) {
                $query->whereRaw("CAST(created_at AS DATE) = ?", [$current])
                      ->orWhereRaw("CAST(updated_at AS DATE) = ?", [$current]);
            })
            ->orWhereRaw("CAST(updated_at AS DATE) >= ?", [$current]) // Qo'shimcha shart
            ->get();
    
        return $result;
    }
    
    
    public function create(Request $request)
    {
        // dd($request);
        $uuidString = (string) Str::uuid();
        try {
            // Yangi yoki mavjud yozuvni topish
            $existingRecord = ValuesParameters::where([
                'ParametersID' => $request->ParametersID,
                'SourcesID' => $request->SourceID,
                'TimeID' => $request->GTid
            ])->first();
    
            // Agar yozuv mavjud bo'lmasa, yangi yozuv qo'shiladi
            if (!$existingRecord) {
                ValuesParameters::create([
                    'id' => $uuidString,
                    'ParametersID' => $request->ParametersID,
                    'SourcesID' => $request->SourceID,
                    'TimeID' => $request->GTid,
                    'Value' => $request->Value,
                    'GraphicsTimesID' => $request->GrapicsID,
                    'BlogID' => $request->BlogsID,
                    'FactoryStructureID' => $request->FactoryStructureID,
                    'Comment' => $request->Comment,
                    'created_at' => now(),
                    'Created' => now(),
                    'Creator' => $request->userId,  // Yaratgan foydalanuvchini saqlash
                ]);
            } else {
                // Mavjud yozuv yangilansa, faqat 'updated_at' yangilanadi va 'Updater' yangilanadi
                $existingRecord->update([
                    'Value' => $request->Value,
                    'GraphicsTimesID' => $request->GrapicsID,
                    'BlogID' => $request->BlogsID,
                    'FactoryStructureID' => $request->FactoryStructureID,
                    'Comment' => $request->Comment,
                    'updated_at' => now(),
                    'Changed' => now(),
                    'Changer' => $request->userId  // Faqat 'Updater' yangilanadi
                ]);
                $uuidString = $existingRecord->id; // Mavjud yozuvning id-si saqlanadi
            }
    
            $unit = ValuesParameters::where('id', $uuidString)->first();
    
            return response()->json([
                'status' => 200,
                'message' => "Ma`lumot muvaffaqiyatli qo'shildi yoki yangilandi",
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
    
    
    // private function create(Request $request)
    // {
    //     // $parameters = [
    //     // dd($request);
    //     //     'ParametersID' => $request->ParametersID,
    //     //     'SourcesID' => $request->SourceID,
    //     //     'TimeID' => $request->GTid,
    //     //     'GraphicsTimesID' => $request->GrapicsID,
    //     // ];
    //     // // dd($parameters);
    //     // $existingRecord = ValuesParameters::where($parameters)->first();

    //     try {
    //         // if ($existingRecord) {
    //         //     $existingRecord->update([
    //         //         'Value' => $request->Value,
    //         //         'BlogID' => intval($request->BlogsID), 
    //         //         'Comment' => $request->Comment,
    //         //         'updated_at' => now(),
    //         //         'Changed' => now(),
    //         //         'Changer' => $request->userId,
    //         //     ]);

    //         //     $unit = $existingRecord;
    //         //     $message = "Data successfully updated";
    //         $uuidString = (string) Str::uuid();
    //         $unit = ValuesParameters::create([
    //             'id' => $uuidString,
    //             'ParametersID' => $request->ParametersID,
    //             'SourcesID' => $request->SourceID,
    //             'BlogID' => $request->BlogsID, // Ensure this field is set
    //             'TimeID' => $request->GTid, // Ensure this field is set
    //             'GraphicsTimesID' => $request->GrapicsID,
    //             'Value' => $request->Value,
    //             'Comment' => $request->Comment,
    //             'Created' => now(),
    //             'Creator' => $request->userId,
    //             'updated_at' => now(), // For consistency
    //         ]);
    //         $message = "Data successfully created";



    //         return response()->json([
    //             'status' => 200,
    //             'message' => $message,
    //             'unit' => $unit
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 500,
    //             'message' => 'There was an error processing the request.',
    //             'error' => $e->getMessage()
    //         ]);
    //     }
    // }

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
            'Changed' => now(),

        ]);

        return response()->json([
            'status' => 200,
            'message' => "muvafaqiyatli yangilandi",
            'unit' => $unit
        ]);
    }
    public function vparamsGetValue($id)
    {

        return ValuesParameters::join('parameters', 'values_parameters.ParametersID', '=', 'parameters.id')
            ->where('BlogID', $id)
            ->where('TimeID', NULL)
            ->whereDate('values_parameters.created_at', Carbon::today()) // Filter for current day
            ->select('parameters.id as Pid', 'parameters.Min', 'parameters.Max', 'parameters.Name', 'parameters.NameRus', 'values_parameters.*')
            ->get();
        
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
