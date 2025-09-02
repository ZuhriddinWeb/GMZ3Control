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
public function selectResultBlogs($docId, $date)
{
    $forDate = \Carbon\Carbon::createFromFormat('d.m.Y', $date)->format('Y-m-d');

    $sql = <<<SQL
DECLARE @DocId   INT  = :doc_id;
DECLARE @ForDate DATE = :for_date;

;WITH D AS (
  SELECT TOP (1)
    dnp.IdNumberPage,
    dnp.FactoryStructureID,
    dnp.NumberPageBlogs,
    dnp.GroupBlogs,
    dnp.ParameterBlogs
  FROM dbo.document_number_pages dnp
  WHERE dnp.IdNumberPage = @DocId
),
-- JSON ni tozalash (agar tashqi qo‘shtirnoq va \" bo‘lsa)
J AS (
  SELECT
    FSRC = CASE WHEN LEFT(FactoryStructureID,1)='"'
                THEN REPLACE(SUBSTRING(FactoryStructureID,2,LEN(FactoryStructureID)-2), '\"','"')
                ELSE FactoryStructureID END,
    NPB  = CASE WHEN LEFT(NumberPageBlogs,1)='"'
                THEN REPLACE(SUBSTRING(NumberPageBlogs,2,LEN(NumberPageBlogs)-2), '\"','"')
                ELSE NumberPageBlogs END,
    GB   = CASE WHEN LEFT(GroupBlogs,1)='"'
                THEN REPLACE(SUBSTRING(GroupBlogs,2,LEN(GroupBlogs)-2), '\"','"')
                ELSE GroupBlogs END,
    PB   = CASE WHEN LEFT(ParameterBlogs,1)='"'
                THEN REPLACE(SUBSTRING(ParameterBlogs,2,LEN(ParameterBlogs)-2), '\"','"')
                ELSE ParameterBlogs END
  FROM D
),
-- 1) sex -> (sexIdx, sexId)
FS AS (
  SELECT sexIdx = TRY_CONVERT(int, jfs.[key]),
         sexId  = TRY_CONVERT(int, jfs.[value])
  FROM J CROSS APPLY OPENJSON(J.FSRC) jfs
),
-- 2) pages -> (sexIdx,pageIdx,pageId)
PAGES AS (
  SELECT sexIdx  = TRY_CONVERT(int, jpb.[key]),
         pageIdx = TRY_CONVERT(int, jp.[key]),
         pageId  = TRY_CONVERT(int, jp.[value])
  FROM J
  CROSS APPLY OPENJSON(J.NPB) jpb
  CROSS APPLY OPENJSON(jpb.[value]) jp
),
-- 3) groups -> (sexIdx,pageIdx,groupIdx,groupId)
GRPS AS (
  SELECT sexIdx   = TRY_CONVERT(int, jg1.[key]),
         pageIdx  = TRY_CONVERT(int, jg.[key]),
         groupIdx = TRY_CONVERT(int, jg2.[key]),
         groupId  = TRY_CONVERT(int, jg2.[value])
  FROM J
  CROSS APPLY OPENJSON(J.GB) jg1
  CROSS APPLY OPENJSON(jg1.[value]) jg
  CROSS APPLY OPENJSON(jg.[value])  jg2
),
-- 4) params -> (sexIdx,pageIdx,groupIdx,paramId)
PB AS (
  SELECT sexIdx   = TRY_CONVERT(int, j1.[key]),
         pageIdx  = TRY_CONVERT(int, j2.[key]),
         groupIdx = TRY_CONVERT(int, j3.[key]),
         paramId  = TRY_CONVERT(uniqueidentifier, jpid.[value])
  FROM J
  CROSS APPLY OPENJSON(J.PB) j1
  CROSS APPLY OPENJSON(j1.[value]) j2
  CROSS APPLY OPENJSON(j2.[value]) j3
  CROSS APPLY OPENJSON(j3.[value]) jpid
),
-- 5) indekslar bo‘yicha bog‘lash va GrapicsID’ni olish
XP AS (
  SELECT
    FS.sexId, PAGES.pageId, GRPS.groupId, PB.paramId,
    gp.GrapicsID
  FROM FS
  JOIN PAGES ON PAGES.sexIdx = FS.sexIdx
  JOIN GRPS  ON GRPS.sexIdx  = FS.sexIdx  AND GRPS.pageIdx = PAGES.pageIdx
  JOIN PB    ON PB.sexIdx    = FS.sexIdx  AND PB.pageIdx   = PAGES.pageIdx AND PB.groupIdx = GRPS.groupIdx
  JOIN dbo.graphics_paramenters gp
       ON gp.ParametersID = PB.paramId
      AND gp.GroupID      = GRPS.groupId
      AND gp.PageId       = PAGES.pageId
),
-- 6) barcha vaqt slotlari (graphic_times)
T AS (
  SELECT
    XP.sexId, XP.pageId, XP.groupId, XP.paramId, XP.GrapicsID,
    gt.id        AS gt_id,
    gt.Change    AS change_id,
    gt.Name      AS gt_name,
    gt.StartTime AS gt_start,
    gt.EndTime   AS gt_end
  FROM XP
  JOIN dbo.graphic_times gt
    ON gt.GraphicsID = XP.GrapicsID
),
-- 7) real qiymatlar (LEFT JOIN — bo‘sh bo‘lishi mumkin)
REALV AS (
  SELECT
    T.groupId, T.pageId, T.sexId, T.paramId, T.change_id, T.gt_id, T.gt_start, T.gt_end,
    vp.TimeID    AS time_id,
    vp.TimeStr   AS time_str,
    TRY_CONVERT(float, vp.Value) AS real_val
  FROM T
  LEFT JOIN dbo.values_parameters vp
    ON vp.ParametersID    = T.paramId
   AND vp.GraphicsTimesID = T.gt_id
   AND CAST(vp.Created AS date) = @ForDate
),
/* 8) FORMULA — dbo.param_formulas (jadval nomini o‘zingiznikiga almashtiring)
   tokens: ["Pid=<GUID>|agg=DAY|func=VALUE|scope=CURRENT", "*", "2"]
   qo'llangan qoida: kun bo‘yicha oxirgi qiymatni olib, operator va sondan foyd. */
PF0 AS (  -- shu doc sahifa uchun faqat mos formulalar
  SELECT pf.*
  FROM dbo.svodka_formulas pf
  WHERE pf.page_id_blog = @DocId
),
PF_TOK AS (  -- tokenlarni pozitsiya bilan ochish
  SELECT
    pf.param_id,
    pf.sex_id,
    pf.page_id,
    pf.group_id,
    t.[key]  AS tok_pos,
    t.[value] AS tok_val
  FROM PF0 pf
  CROSS APPLY OPENJSON(pf.tokens) t
),
PF_PARSED AS ( -- 0-pozitsiya: Pid=..., 1-pozitsiya: operator, 2-pozitsiya: son
  SELECT
    p0.param_id, p0.sex_id, p0.page_id, p0.group_id,
    TRY_CONVERT(uniqueidentifier,
      SUBSTRING(p0.tok_val, CHARINDEX('Pid=', p0.tok_val) + 4, 36)
    ) AS ref_pid,
    p1.tok_val AS op,
    TRY_CONVERT(float, p2.tok_val) AS num
  FROM PF_TOK p0
  LEFT JOIN PF_TOK p1 ON p1.param_id = p0.param_id AND p1.sex_id=p0.sex_id AND p1.page_id=p0.page_id AND p1.group_id=p0.group_id AND p1.tok_pos = 1
  LEFT JOIN PF_TOK p2 ON p2.param_id = p0.param_id AND p2.sex_id=p0.sex_id AND p2.page_id=p0.page_id AND p2.group_id=p0.group_id AND p2.tok_pos = 2
  WHERE p0.tok_pos = 0
),
PF_REF AS ( -- kun bo‘yicha reference parametrning oxirgi qiymati
  SELECT
    pp.param_id, pp.sex_id, pp.page_id, pp.group_id,
    pp.ref_pid, pp.op, pp.num,
    (SELECT TOP (1) TRY_CONVERT(float, vp.Value)
     FROM dbo.values_parameters vp
     WHERE vp.ParametersID = pp.ref_pid
       AND CAST(vp.Created AS date) = @ForDate
     ORDER BY vp.Created DESC, vp.id DESC) AS ref_val
  FROM PF_PARSED pp
),
PF_VAL AS ( -- formulani hisoblash
  SELECT
    pr.param_id, pr.sex_id, pr.page_id, pr.group_id,
    CASE pr.op
      WHEN '+' THEN pr.ref_val + pr.num
      WHEN '-' THEN pr.ref_val - pr.num
      WHEN '*' THEN pr.ref_val * pr.num
      WHEN '/' THEN CASE WHEN pr.num = 0 THEN NULL ELSE pr.ref_val / pr.num END
      ELSE pr.ref_val
    END AS f_val
  FROM PF_REF pr
),
-- 9) REAL + FORMULA ni birlashtirish: real yo‘q bo‘lsa formula
FINAL AS (
  SELECT
    rv.groupId,
    rv.pageId,
    rv.sexId,
    rv.paramId           AS parameter_id,
    rv.change_id,
    rv.gt_id,
    rv.gt_start,
    rv.gt_end,
    rv.time_id,
    rv.time_str,
    COALESCE(rv.real_val, pf.f_val) AS out_val,
    CASE WHEN rv.real_val IS NULL AND pf.f_val IS NOT NULL THEN 1 ELSE 0 END AS WithFormula
  FROM REALV rv
  LEFT JOIN PF_VAL pf
    ON pf.param_id = rv.paramId
   AND pf.sex_id   = rv.sexId
   AND pf.page_id  = rv.pageId
   AND pf.group_id = rv.groupId
)

SELECT 
  f.groupId,
  g.Name AS group_name,
  f.change_id AS change_no,
  f.time_id,
  COALESCE(f.time_str, CONVERT(varchar(8), f.gt_start, 108)) AS time_name,
  f.parameter_id,
  p.Name AS parameter_name,
  p.Min  AS [Min],
  p.Max  AS [Max],
  f.out_val AS [Value],
  f.WithFormula
FROM FINAL f
LEFT JOIN dbo.[groups]     g ON g.id = f.groupId
LEFT JOIN dbo.[parameters] p ON p.id = f.parameter_id
ORDER BY f.groupId, f.change_id, f.gt_start, f.time_id;
SQL;

    $rows = DB::select($sql, [
        'doc_id'   => (int) $docId,
        'for_date' => $forDate,
    ]);

    // groupId -> changeId -> timeId -> [items]
    $out = [];
    foreach ($rows as $r) {
        $gId  = (string) $r->groupId;
        $chId = (string) ($r->change_no ?? 0);
        $tKey = (string) ($r->time_id ?? 0);

        $out[$gId][$chId][$tKey][] = [
            'parameter_id'   => $r->parameter_id,
            'parameter_name' => $r->parameter_name,
            'Value'          => $r->Value,
            'Min'            => $r->Min,
            'Max'            => $r->Max,
            'time_name'      => $r->time_name,
            'group_name'     => $r->group_name,
            'WithFormula'    => (string) ($r->WithFormula ?? 0),
        ];
    }

    return response()->json($out);
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
