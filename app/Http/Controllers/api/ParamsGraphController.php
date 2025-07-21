<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GraphicsParamenters;
use DB;
use Carbon\Carbon;
use App\Http\Controllers\api\AppHelper;
use App\Events\TimeUpdated;
use App\Models\GraphicTimes;
use App\Models\ValuesParameters;
use App\Models\User;
use App\Models\Parameters;


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
            // ->join('number_pages', 'graphics_paramenters.PageId', '=', 'number_pages.id')

            ->join('graphics', 'graphics_paramenters.GrapicsID', '=', 'graphics.id')
            ->select('graphics.id as Gid', 'graphics.name as GName', 'parameters.id as Puuid', 'parameters.name as PName', 'parameters.name as PNameRus', 'factory_structures.id as Fid', 'factory_structures.name as FName', 'graphics_paramenters.*')
            ->get();
        return response()->json($Gparams);
    }
    public function withCardId($id, $pageId)
    {
        $Gparams = GraphicsParamenters::join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->join('factory_structures', 'graphics_paramenters.FactoryStructureID', '=', 'factory_structures.id')
            ->leftJoin('number_pages', 'graphics_paramenters.PageId', '=', 'number_pages.NumberPage')
            ->leftJoin('formulas', 'graphics_paramenters.WithFormula', '=', 'formulas.id')

            ->join('graphics', 'graphics_paramenters.GrapicsID', '=', 'graphics.id')
            ->where('graphics_paramenters.FactoryStructureID', $id)
            ->where('graphics_paramenters.PageId', $pageId)
            ->select('formulas.id as ForId', 'formulas.Name as ForName', 'number_pages.NumberPage', 'number_pages.Name as NName', 'graphics.id as Gid', 'graphics.name as GName', 'parameters.id as Puuid', 'parameters.name as PName', 'parameters.name as PNameRus', 'factory_structures.id as Fid', 'factory_structures.name as FName', 'graphics_paramenters.*')
            ->get();
        // dd($Gparams);
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
            // ->join('blogs', 'graphics_paramenters.BlogsID', '=', 'blogs.id')
            ->join('sources', 'graphics_paramenters.SourceID', '=', 'sources.id')
            // ->join('number_pages', 'graphics_paramenters.PageId', '=', 'number_pages.id')
            ->join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->where('graphics_paramenters.id', $id)
            // ->select('number_pages.id as Nid','number_pages.Name as NumName','graphics_paramenters.*', 'graphics.id as Gid', 'graphics.Name as GName', 'parameters.id as Pid', 'parameters.name as Pname', 'factory_structures.id as Sid', 'factory_structures.Name as SName', 'blogs.id as Bid', 'blogs.Name as BName', 'sources.id as Cid', 'sources.Name as Cname')
            ->select('graphics_paramenters.*', 'graphics.id as Gid', 'graphics.Name as GName', 'parameters.id as Pid', 'parameters.name as Pname', 'factory_structures.id as Sid', 'factory_structures.Name as SName', 'sources.id as Cid', 'sources.Name as Cname')

            ->get();
    }

    public function getGraficWithParams($id)
    {
        return GraphicsParamenters::join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->where('FactoryStructureID', $id)
            ->get();
    }
    public function getForFormule($id)
    {
        // Fetch the main graphic parameter record by its ID
        $data = GraphicsParamenters::find($id);

        if (!$data) {
            return response()->json(['message' => 'Graphic parameter not found'], 404);
        }
        // Fetch all graphic parameters that share the same FactoryStructureID
        $units = GraphicsParamenters::where('FactoryStructureID', $data->FactoryStructureID)
            ->where('PageId', $data->PageId)->get();
        // dd($units);

        $allParameters = [];

        foreach ($units as $unit) {
            // Retrieve parameters using the relationship
            $parameters = $unit->parameters; // This should fetch the related Parameters record based on ParametersID


            $allParameters[] = [
                'formula' => $unit,
                'parameters' => $parameters ? $parameters : null, // Explicitly check if parameters exist
            ];
        }

        return response()->json($allParameters);
    }

public function getParamsForUser($id, $change, $ChangeDay, $tabId)
{
    $idArray = explode(',', $id);
    $blogsIds = array_map('intval', $idArray);
    $blogsIdsString = implode(',', $blogsIds);

    $query = DB::select("
        SELECT * FROM 
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
                terms.id as TMid,

                -- Smena vaqtiga qarab StartDateTime
                CASE 
                    WHEN DATEPART(HOUR, graphic_times.StartTime) < 8 
                        THEN DATEADD(DAY, -1, ?) 
                        ELSE ? 
                END + CAST(graphic_times.StartTime AS DATETIME) AS StartDateTime,
                ? AS ChangeDay1,

                -- Sun’iy tartib raqam qiymatlari
                CASE 
                    WHEN ? = 2 THEN 
                        CASE 
                            WHEN DATEPART(HOUR, graphic_times.StartTime) >= 20 THEN DATEPART(HOUR, graphic_times.StartTime) - 19
                            ELSE DATEPART(HOUR, graphic_times.StartTime) + 5
                        END
                    ELSE DATEPART(HOUR, graphic_times.StartTime)
                END AS SortOrder

            FROM graphics_paramenters 
            INNER JOIN graphic_times ON graphics_paramenters.GrapicsID = graphic_times.GraphicsID
            INNER JOIN parameters ON graphics_paramenters.ParametersID = parameters.id
            INNER JOIN terms ON terms.GraphicsID = graphic_times.GraphicsID

            WHERE graphics_paramenters.FactoryStructureID IN ($blogsIdsString)
              AND (graphic_times.Change = ? OR ? = 0)
              AND graphics_paramenters.PageId = $tabId
        ) p

        WHERE p.StartDateTime <= GETDATE()

        ORDER BY 
            CASE WHEN p.Change = 2 THEN p.SortOrder ELSE DATEPART(HOUR, p.STime) END DESC,
            p.OrderNumber ASC
    ", [$ChangeDay, $ChangeDay, $ChangeDay, $change, $change, $change]);

    return $query;
}
    public function getParamsForUserHorizontal($id, $change, $ChangeDay, $tabId)
    {
        $idArray = explode(',', $id);
        $blogsIds = array_map('intval', $idArray);
        $blogsIdsString = implode(',', $blogsIds);
        $query = DB::select("
        SELECT * FROM 
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
                groups.Name as GroupName,
                graphics_paramenters.*,
                CASE 
                    WHEN DATEPART(HOUR, graphic_times.StartTime) < 8 
                    THEN DATEADD(DAY, -1, ?)
                    ELSE ?
                END + CAST(graphic_times.StartTime AS DATETIME) AS StartDateTime,
                ? AS ChangeDay1 
            FROM graphics_paramenters 
            INNER JOIN graphic_times ON graphics_paramenters.GrapicsID = graphic_times.GraphicsID
            INNER JOIN parameters ON graphics_paramenters.ParametersID = parameters.id
            INNER JOIN groups ON graphics_paramenters.GroupID = groups.id

            WHERE graphics_paramenters.FactoryStructureID IN ($blogsIdsString)
                AND (graphic_times.Change = ? OR ? = 0) 
                AND graphics_paramenters.PageId = $tabId
        ) p
        WHERE p.StartDateTime <= GETDATE()
        ORDER BY StartDateTime DESC, OrderNumber
    ", [$ChangeDay, $ChangeDay, $ChangeDay, $change, $change]);

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
        $idsArray = explode(',', $id);
        return GraphicsParamenters::join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
            ->whereIn('FactoryStructureID', $idsArray)
            ->select('parameters.id as Pid', 'parameters.Name as PName', 'parameters.ShortName as ShName', 'graphics_paramenters.*')
            ->get();
    }
    public function sendTimeUpdate()
    {
        $currentTime = now()->format('H:i');

        broadcast(new TimeUpdated($currentTime));
        // event();
        return response()->json(['status' => 'Yangilandi!']);
    }
    // public function getParamsForUserCount($id, $change_id)
    // {
    //     $idArray = explode(',', $id);

    //     $query = DB::table('graphics_paramenters')
    //         ->join('graphic_times', 'graphics_paramenters.GrapicsID', '=', 'graphic_times.GraphicsID')
    //         ->join('parameters', 'graphics_paramenters.ParametersID', '=', 'parameters.id')
    //         ->whereIn('BlogsID', $idArray)
    //         ->select('graphic_times.id as GTid', 'graphic_times.Name as GTName', 'graphic_times.Change as Change', 'graphic_times.StartTime as STime', 'graphic_times.EndTime as ETime', 'parameters.Name as PName', 'parameters.Min as Min', 'parameters.Max as Max', 'graphics_paramenters.*');

    //     if ($change_id == 1) {
    //         $query->whereTime('graphic_times.StartTime', '>=', '08:00')
    //             ->whereTime('graphic_times.EndTime', '<=', '20:00');
    //     } elseif ($change_id == 2) {
    //         $query->where(function ($query) {
    //             $query->whereTime('graphic_times.StartTime', '<', '08:00')
    //                 ->orWhereTime('graphic_times.EndTime', '>', '20:00');
    //         });
    //     }
    //     $data = $query->count();
    //     return $data;
    // }

    public function getRowPageResult(Request $request, $id)
    {
        $day = $request->input('day');
        $smena = $request->input('smena');
    
        $graphicsParams = GraphicsParamenters::where('FactoryStructureID', $id)
            ->with(['NumberPage', 'factoryStructure'])
            ->get()
            ->groupBy('PageId');
    
        $result = collect();
    
        foreach ($graphicsParams as $pageId => $items) {
            $parameterIDs = $items->pluck('ParametersID');
            $graphicIDs = $items->pluck('GrapicsID');
    
            $parameterCount = $parameterIDs->count();
            $formulaCount = $items->where('WithFormula', 1)->count();
            $manualCount = $items->where('WithFormula', 2)->count();
    
            $graphicTimes = GraphicTimes::whereIn('GraphicsID', $graphicIDs)
                ->when($smena, fn($q) => $q->where('Change',  $smena))
                ->get();
    
            $graphicTimesCount = $graphicTimes->count();
            $graphicTimeIds = $graphicTimes->pluck('id');
    
            $multipliedFormulaCount = $formulaCount * $graphicTimesCount;
            $multipliedManualCount = $manualCount * $graphicTimesCount;
            $multipliedCount = $parameterCount * $graphicTimesCount;
    
            // kiritilgan ma'lumotlar
            $enteredData = ValuesParameters::whereIn('ParametersID', $parameterIDs)
                ->whereDate('created_at', $day)
                ->where('ChangeID', $smena)
                ->get();
    
            $firstCreatorId = optional($enteredData->firstWhere('Creator', '!=', null))->Creator;
            $creatorName = $firstCreatorId ? optional(User::find($firstCreatorId))->name : null;
    
            $langField = app()->getLocale() === 'ru' ? 'NameRus' : 'Name';
            $allParams = \App\Models\Parameters::whereIn('id', $parameterIDs)->get(['id', $langField]);
    
            $inputedParamsWithTimes = [];
            $notInputedParamsWithTimes = [];
    
            foreach ($allParams as $param) {
                $paramId = $param->id;
                $paramName = $param->{$langField};
    
                foreach ($graphicTimes as $graphicTime) {
                    $exists = $enteredData->contains(function ($data) use ($paramId, $graphicTime) {
                        return $data->ParametersID == $paramId && $data->TimeStr == $graphicTime->Name;
                    });
    
                    $entry = [
                        'param' => $paramName,
                        'time' => $graphicTime->Name,
                    ];
    
                    if ($exists) {
                        $inputedParamsWithTimes[] = $entry;
                    } else {
                        $notInputedParamsWithTimes[] = $entry;
                    }
                }
            }
    
            $percentage = $multipliedCount > 0 ? round((count($inputedParamsWithTimes) / $multipliedCount) * 100, 2) : 0;
    
            $result->push([
                'page_id' => $pageId,
                'page_name' => optional(optional($items->first())->NumberPage)->Name,
                'parameter_count' => $parameterCount,
                'formula_count' => $formulaCount,
                'manual_count' => $manualCount,
                'multiplied_formula_count' => $multipliedFormulaCount,
                'multiplied_manual_count' => $multipliedManualCount,
                'multiplied_parameter_count' => $multipliedCount,
                'factory_structure_name' => optional(optional($items->first())->factoryStructure)->Name,
                'graphics_ids' => $graphicIDs,
                'graphic_times_count' => $graphicTimesCount,
                'kiritilgan' => count($inputedParamsWithTimes),
                'kiritgan_operator' => $creatorName,
                'foiz' => $percentage,
                'inputed_param_with_times' => $inputedParamsWithTimes,
                'not_inputed_param_with_times' => $notInputedParamsWithTimes,
            ]);
        }
    
        return response()->json($result->values());
    }
    












    private function create(Request $request)
    {
        // dd( $request);

        $GParams = GraphicsParamenters::create([
            'OrderNumber' => $request->OrderNumber,
            'ParametersID' => $request->ParametersID,
            'FactoryStructureID' => $request->FactoryStructureID,
            'BlogsID' => $request->BlogID,
            'WithFormula' => $request->WithFormula,
            'GrapicsID' => $request->GrapicsID,
            'SourceID' => $request->SourceID,
            'PageId' => $request->PageId,
            'GroupID' => $request->GroupID,
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
            'WithFormula' => $request->WithFormula,
            'FactoryStructureID' => $request->FactoryStructureID,
            'BlogsID' => $request->BlogID,
            'GrapicsID' => $request->GrapicsID,
            'SourceID' => $request->SourceID,
            'PageId' => $request->PageId,
            'GroupID' => $request->GroupID,
            'CurrentTime' => date('Y-m-d H:i:s', strtotime($request->CurrentTime)),
            'EndingTime' => date('Y-m-d H:i:s', strtotime($request->EndingTime)),
        ]);
        if ($request->ParametersID && $request->ShortName) {
            Parameters::where('id', $request->ParametersID)->update([
                'ShortName' => $request->ShortName
            ]);
        }
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
