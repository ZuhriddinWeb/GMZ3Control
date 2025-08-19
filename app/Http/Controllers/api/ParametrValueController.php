<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ValuesParameters;
use DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\VPChangeSync;
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
    public function getByBlog($factoryId, $current, $ChangeID)
    {
        $current = Carbon::parse($current)->toDateString();
        $currentMonth = Carbon::parse($current)->month;
        $currentYear = Carbon::parse($current)->year;

        $idArray = explode(',', $factoryId);

        $result = VPChangeSync::whereIn('FactoryStructureID', $idArray)
            ->where('ChangeID', $ChangeID)
            ->where(function ($query) use ($current, $currentMonth, $currentYear) {
                $query->where(function ($q1) use ($currentMonth, $currentYear) {
                    $q1->where('TermID', 1)
                        ->whereMonth('Created', $currentMonth)
                        ->whereYear('Created', $currentYear);
                })
                    ->orWhereRaw("((TermID != 1 OR TermID IS NULL) AND (CAST(Created AS DATE) = ? OR CAST(Changed AS DATE) = ?))", [$current, $current]);
            })
            ->get();


        return $result;
    }





    public function create(Request $request)
    {
        // dd($request);
        $uuidString = (string) Str::uuid();
        try {
            // Yangi yoki mavjud yozuvni topish
            $existingRecord = VPChangeSync::where([
                'ParametersID' => $request->ParametersID,
                'SourcesID' => $request->SourceID,
                'TimeID' => $request->GTid,
                'TimeStr' => $request->GTName,
                'Created' => $request->daySelect,
                'TermID' => $request->TMid,
                'GraphicsTimesID' => $request->GrapicsID,
            ])->first();

            // Agar yozuv mavjud bo'lmasa, yangi yozuv qo'shiladi
            if (!$existingRecord) {
                VPChangeSync::create([
                    'id' => $uuidString,
                    'ParametersID' => $request->ParametersID,
                    'SourcesID' => $request->SourceID,
                    'TimeID' => $request->GTid,
                    'TimeStr' => $request->GTName,
                    'ChangeID' => $request->change,
                    'Value' => $request->Value,
                    'GraphicsTimesID' => $request->GrapicsID,
                    'TermID' => $request->TMid,
                    'BlogID' => $request->BlogsID,
                    'FactoryStructureID' => $request->FactoryStructureID,
                    'Comment' => $request->Comment,
                    'created_at' => now(),
                    'Created' => $request->daySelect,
                    'Creator' => $request->userId,  // Yaratgan foydalanuvchini saqlash
                ]);
            } else {
                // Mavjud yozuv yangilansa, faqat 'updated_at' yangilanadi va 'Updater' yangilanadi
                $existingRecord->update([
                    'Value' => $request->Value,
                    'GraphicsTimesID' => $request->GrapicsID,
                    'TermID' => $request->TMid,
                    'BlogID' => $request->BlogsID,
                    'FactoryStructureID' => $request->FactoryStructureID,
                    'Comment' => $request->Comment,
                    'updated_at' => now(),
                    'Changed' => $request->daySelect,
                    'Changer' => $request->userId  // Faqat 'Updater' yangilanadi
                ]);
                $uuidString = $existingRecord->id; // Mavjud yozuvning id-si saqlanadi
            }

            $unit = VPChangeSync::where('id', $uuidString)->first();

            return response()->json([
                'status' => 200,
                'message' => "Ma`lumot muvaffaqiyatli qo'shildi yoki yangilandi",
                'unit' => $unit
            ]);

        } catch (\Exception $e) {
            // \Log::error('Error creating/updating unit:', ['error' => $e->getMessage()]);

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
        // Agar $id string ko'rinishda bo'lsa, uni massivga aylantiring
        if (is_string($id)) {
            $id = explode(',', $id);
            // ixtiyoriy: har bir elementni tozalash va int ga o'tkazish
            $id = array_map('trim', $id);
            $id = array_map('intval', $id);
        }

        return ValuesParameters::join('parameters', 'values_parameters.ParametersID', '=', 'parameters.id')
            ->whereIn('BlogID', $id)
            ->whereNull('TimeID')
            ->whereDate('values_parameters.created_at', Carbon::today())
            ->select('parameters.id as Pid', 'parameters.Min', 'parameters.Max', 'parameters.Name', 'parameters.NameRus', 'values_parameters.*')
            ->get();
    }
    public function selectResultBlogs($selectedDate)
    {
        // Formatlash kerak bo'lsa (masalan, 14.04.2025 → 2025-04-14)
        $date = \Carbon\Carbon::createFromFormat('d.m.Y', $selectedDate)->format('Y-m-d');

        $results = DB::table('values_parameters as vp')
            ->join('graphics_paramenters as gp', function ($join) {
                $join->on('gp.ParametersID', '=', 'vp.ParametersID')
                    ->on('gp.FactoryStructureID', '=', 'vp.FactoryStructureID');
            })
            ->join('groups as g', 'g.id', '=', 'gp.GroupID')
            ->join('changes as ch', 'ch.id', '=', 'vp.ChangeID')
            ->join('graphic_times as gt', 'gt.id', '=', 'vp.TimeID')
            ->join('parameters as p', 'p.id', '=', 'vp.ParametersID')
            ->where('vp.FactoryStructureID', 5)
            ->whereDate('vp.Created', $date)
            ->select([
                'g.id as group_id',
                'g.Name as group_name',
                'ch.id as change_id',
                'ch.Change as smena',
                'gt.id as time_id',
                'gt.Name as time_name',
                'p.id as parameter_id',
                'p.ShortName as parameter_name',
                'p.ShortName as parameter_name_rus',
                'p.Min as Min',
                'p.Max as Max',
                'vp.Value',
                'gp.OrderNumber',
            ])
            ->orderBy('g.id')
            ->orderBy('ch.Change')
            ->orderBy('gt.Name')
            ->orderBy('gp.OrderNumber')
            ->get();

        // Natijani group, smena, time_name bo'yicha group qilib json qaytarish
        $grouped = $results->groupBy(['group_id', 'change_id', 'time_id']);

        return response()->json($grouped->toArray());
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
