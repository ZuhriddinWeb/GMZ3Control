<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GraphicsParamenters;
use DB;
use Carbon\Carbon;
use App\Http\Controllers\api\AppHelper;
use App\Events\TimeUpdated;

class ParamsGraphController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return $this->getRowUnit($id);
        }
        // asd
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
        $Gparams = GraphicsParamenters::join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->join('factory_structures', 'graphics_paramenters.FactoryStructureID', '=', 'factory_structures.id')
            ->join('graphics', 'graphics_paramenters.GrapicsID', '=', 'graphics.id')
            ->select('graphics.id as Gid', 'graphics.name as GName', 'parameters.id as Puuid', 'parameters.name as PName', 'parameters.name as PNameRus', 'factory_structures.id as Fid', 'factory_structures.name as FName', 'graphics_paramenters.*')
            ->get();
        return response()->json($Gparams);
    }
    private function getRowUnit($id)
    {
        $unit = GraphicsParamenters::join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')->where('BlogsID', $id)->get();
        return response()->json($unit);
    }
    public function getRowParamEdit($id)
    {
        return GraphicsParamenters::join('graphics', 'graphics_paramenters.GrapicsID', '=', 'graphics.id')
            ->join('factory_structures', 'graphics_paramenters.FactoryStructureID', '=', 'factory_structures.id')
            ->join('blogs', 'graphics_paramenters.BlogsID', '=', 'blogs.id')
            ->join('sources', 'graphics_paramenters.SourceID', '=', 'sources.id')
            ->join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->where('graphics_paramenters.id', $id)
            ->select('graphics_paramenters.*', 'graphics.id as Gid', 'graphics.Name as GName', 'parameters.id as Pid', 'parameters.name as Pname', 'factory_structures.id as Sid', 'factory_structures.Name as SName', 'blogs.id as Bid', 'blogs.Name as BName', 'sources.id as Cid', 'sources.Name as Cname')
            ->first();
    }
    public function getParamsForUser($id, $change, $ChangeDay,$tabId)
    {
        // dd($id);
        // Split the ID string into an array of integers
        $idArray = explode(',', $id);
        $blogsIds = array_map('intval', $idArray); // Ensure IDs are integers
        $blogsIdsString = implode(',', $blogsIds); // Create comma-separated string

        // Prepare the SQL query with parameter binding
        $query = DB::select("SELECT * FROM 
            (
                SELECT 
                    graphic_times.id AS GTid,
                    graphic_times.Name AS GTName,
                    graphic_times.Change AS Change,
                    graphic_times.StartTime AS STime,
                    graphic_times.EndTime AS ETime,
                    parameters.Name AS PName,
                    parameters.NameRus AS PNameRus,
                    parameters.Min AS Min,
                    parameters.Max AS Max,
                    graphics_paramenters.*,
                    (SELECT TOP 1 DATEADD(DAY, CASE WHEN f.StartingDay = 1 THEN 1 ELSE 0 END, ?)
                     FROM [dbo].[Change2](1, ? + CAST(graphic_times.StartTime AS DATETIME)) f) 
                     + CAST(graphic_times.StartTime AS DATETIME) AS StartDateTime,
                    ? AS ChangeDay1 
                FROM graphics_paramenters 
                INNER JOIN graphic_times ON graphics_paramenters.GrapicsID = graphic_times.GraphicsID
                INNER JOIN parameters ON graphics_paramenters.ParametersID = parameters.id
                WHERE FactoryStructureID IN ($blogsIdsString)
                    AND (Change = ? OR ? = 0) AND PageId=$tabId
            ) p
            WHERE p.StartDateTime <= GETDATE()
            ORDER BY StartDateTime DESC, OrderNumber
        ", [$ChangeDay, $ChangeDay, $ChangeDay, $change, $change]);

        // Optionally, you can dump the query result for debugging
        // dd($query[0]->ETime);
        // dd($query);

        return $query;
    }
    //dd($ChangeDay);
    //  $query = DB::select("select * from 
    //  (
    //  select 
    //      graphic_times.id as GTid,graphic_times.Name as GTName, graphic_times.Change as Change, graphic_times.StartTime as STime, graphic_times.EndTime as ETime, parameters.Name as PName,parameters.NameRus as PNameRus, parameters.Min as Min, parameters.Max as Max, graphics_paramenters.*
    //      ,(SELECT top 1 dateadd(day, case when f.StartingDay=1 then 1 else 0 end, '$ChangeDay') FROM [dbo].[Change2](1, '$ChangeDay'+ cast(graphic_times.StartTime as datetime)) f) + cast(graphic_times.StartTime as datetime)  StartDateTime
    //      ,'$ChangeDay' ChangeDay1 
    //      from graphics_paramenters 
    //      inner join graphic_times on graphics_paramenters.GrapicsID = graphic_times.GraphicsID
    //      inner join parameters on graphics_paramenters.ParametersID = parameters.id
    //  where BlogsID in ($idArray)
    //      and (Change=$change or $change=0) 
    //  ) p
    //  where p.StartDateTime <= getdate()
    //  order by StartDateTime desc, OrderNumber");

    //  // dd($query[0]->ETime);
    //  // dd($query);
    //  return $query;
    // $ChangeDay = '2024-08-22';
    // $change=(int)1;
    // $query =  DB::table('graphics_paramenters')
    //     ->join('graphic_times', 'graphics_paramenters.GrapicsID', '=', 'graphic_times.GraphicsID')
    //     ->join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
    //     ->whereIn('BlogsID', $idArray) 

    //     ->select('graphic_times.id as GTid','graphic_times.Name as GTName', 'graphic_times.Change as Change', 'graphic_times.StartTime as STime', 'graphic_times.EndTime as ETime', 'parameters.Name as PName', 'parameters.Min as Min', 'parameters.Max as Max', 'graphics_paramenters.*');
    //     if ($change_id == 1) {
    //         $query->whereTime('graphic_times.StartTime', '>=', '08:00')
    //               ->whereTime('graphic_times.StartTime', '<=', '20:00');
    //     } elseif ($change_id == 2) {
    //         $query->where(function ($query) {
    //             $query->whereTime('graphic_times.StartTime', '<', '08:00')
    //                   ->orWhereTime('graphic_times.StartTime', '>', '20:00');
    //         });
    //     }
    //     return $query->get();
    // dd();
    // if()
    public function getRowParamID($id)
    {
        return GraphicsParamenters::join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->where('FactoryStructureID', $id)
            ->select('parameters.id as Pid','parameters.Name as PName','graphics_paramenters.*')
            ->get();
    }
    public function sendTimeUpdate()
    {
        $currentTime = now()->format('H:i');

        broadcast(new TimeUpdated($currentTime));
        // event();
        return response()->json(['status' => 'Yangilandi!']);
    }
    public function getParamsForUserCount($id, $change_id)
    {
        $idArray = explode(',', $id);

        $query = DB::table('graphics_paramenters')
            ->join('graphic_times', 'graphics_paramenters.GrapicsID', '=', 'graphic_times.GraphicsID')
            ->join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->whereIn('BlogsID', $idArray)
            ->select('graphic_times.id as GTid', 'graphic_times.Name as GTName', 'graphic_times.Change as Change', 'graphic_times.StartTime as STime', 'graphic_times.EndTime as ETime', 'parameters.Name as PName', 'parameters.Min as Min', 'parameters.Max as Max', 'graphics_paramenters.*');

        if ($change_id == 1) {
            $query->whereTime('graphic_times.StartTime', '>=', '08:00')
                ->whereTime('graphic_times.EndTime', '<=', '20:00');
        } elseif ($change_id == 2) {
            $query->where(function ($query) {
                $query->whereTime('graphic_times.StartTime', '<', '08:00')
                    ->orWhereTime('graphic_times.EndTime', '>', '20:00');
            });
        }
        $data = $query->count();
        return $data;
    }
    private function create(Request $request)
    {
        $GParams = GraphicsParamenters::create([
            'OrderNumber' => $request->OrderNumber,
            'ParametersID' => $request->ParametersID,
            'FactoryStructureID' => $request->FactoryStructureID,
            'BlogsID' => $request->BlogID,
            'GrapicsID' => $request->GrapicsID,
            'SourceID' => $request->SourceID,
            'PageId' => $request->PageId,

            'CurrentTime' => date('Y-m-d H:i:s', strtotime($request->CurrentTime)),
            'EndingTime' => date('Y-m-d H:i:s', strtotime($request->EndingTime)),
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'unit' => $GParams
        ]);
    }

    private function update(Request $request)
    {
        $unit = GraphicsParamenters::find($request->id);
        $unit->update([
            'OrderNumber' => $request->OrderNumber,
            'ParametersID' => $request->ParametersID,
            'FactoryStructureID' => $request->FactoryStructureID,
            'BlogsID' => $request->BlogID,
            'GrapicsID' => $request->GrapicsID,
            'SourceID' => $request->SourceID,
            'CurrentTime' => date('Y-m-d H:i:s', strtotime($request->CurrentTime)),
            'EndingTime' => date('Y-m-d H:i:s', strtotime($request->EndingTime)),
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
            $unit = GraphicsParamenters::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
