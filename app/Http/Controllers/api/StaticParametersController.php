<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StaticParameters;
use App\Models\StaticHistory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class StaticParametersController extends Controller
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
                return $this->update($request, $id);
            case 'DELETE':
                return $this->delete($request, $id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }

    private function index()
    {
        //  $params = Parameters::join('paramenters_types', 'parameters.ParametrTypeID', '=', 'paramenters_types.id')
        //     ->join('units', 'parameters.UnitsID', '=', 'units.id')
        //     ->leftJoin('servers', 'parameters.ServerId', '=', 'servers.id') 
        //     ->select('parameters.id as Uuid','parameters.ShortName as ShName', 'parameters.Name','parameters.WinCC','parameters.ServerId', 'parameters.ShortName', 'parameters.Comment', 'parameters.Min', 'parameters.Max', 'paramenters_types.Name as PName', 'paramenters_types.id as Pid', 'units.Name as UName', 'units.id as Uid','servers.name as IpName', 'servers.id as IpId')
        //     ->get();
        // $units = StaticParameters::join('period_types', 'static_parameters.period_type_id', '=', 'period_types.id')
        //     ->join('parameters', 'static_parameters.ParameterID', '=', 'parameters.id')
        //     ->join('units', 'parameters.UnitsID', '=', 'units.id')
        //     ->select('units.Name as UName', 'period_types.name as PTName', 'parameters.Name as PName', 'static_parameters.*')
        //     ->get();
        // return response()->json($units);

        $base = DB::table('static_parameters as sp')
            ->select(
                'sp.*',
                DB::raw("
                ROW_NUMBER() OVER (
                  PARTITION BY
                    sp.ParameterID,
                    sp.FactoryStructureID,
                    sp.NumberPage,
                    sp.GroupID
                  ORDER BY
                    -- eng yangi deb hisoblash mezoni:
                    sp.period_end_date DESC,
                    sp.period_start_date DESC,
                    sp.updated_at DESC,
                    sp.created_at DESC
                ) as rn
            ")
            );

        // 2) faqat rn = 1 (ya'ni eng so‘nggi) bo‘lganlarini olib, bog‘liq nomlar bilan birga qaytaramiz
        $units = DB::query()
            ->fromSub($base, 'sp')
            ->join('parameters as p', 'sp.ParameterID', '=', 'p.id')
            ->join('units as u', 'p.UnitsID', '=', 'u.id')
            ->leftJoin('period_types as pt', 'sp.period_type_id', '=', 'pt.id')
            ->where('sp.rn', 1)
            ->select([
                'u.Name as UName',
                'pt.name as PTName',
                'p.Name as PName',
                'sp.*',
            ])
            ->get();

        return response()->json($units);
    }
public function staticCard($id)
{
    return StaticParameters::join('parameters','static_parameters.ParameterID','=','parameters.id')
    ->join('units as u', 'parameters.UnitsID', '=', 'u.id')
    ->join('period_types', 'static_parameters.period_type_id', '=', 'period_types.id')
    ->where('NumberPage',$id)
    ->select([
            // 'sp.id as static_id',
            'static_parameters.NumberPage',
            'static_parameters.FactoryStructureID',
            'static_parameters.GroupID',
            'static_parameters.ParameterID',
            'static_parameters.OrderNumber',
            'static_parameters.period_start_date',
            'static_parameters.period_end_date',
            'period_types.Name as PTName',
            // — parametr / birlik nomlari
            'parameters.Name as PName',
            'u.Name as UName',
        ])
    ->get();
    // $pageId = (int) $id;
    // $date   = request('date'); // ixtiyoriy: 'YYYY-MM-DD' bo'lsa snapshot

    // // 1) Histories subquery: har (static_id, period_type_id, comment) bo'yicha eng so'nggi yozuv
    // $histBase = DB::table('static_histories as h')
    //     ->when($date, function ($q) use ($date) {
    //         // snapshot: shu sanaga qadar amal qiladigan qiymatlar
    //         $q->whereDate('h.period_start_date', '<=', $date)
    //           ->whereDate('h.period_end_date',   '>=', $date);
    //     })
    //     ->select([
    //         'h.id',
    //         'h.static_id',
    //         'h.value',
    //         'h.period_type_id',
    //         'h.period_start_date',
    //         'h.period_end_date',
    //         DB::raw('CAST(h.comment AS NVARCHAR(4000)) as comment'),
    //         DB::raw("
    //             ROW_NUMBER() OVER (
    //               PARTITION BY
    //                 h.static_id,
    //                 h.period_type_id,
    //                 CAST(h.comment AS NVARCHAR(4000))
    //               ORDER BY
    //                 h.period_end_date DESC,
    //                 h.period_start_date DESC,
    //                 h.updated_at DESC,
    //                 h.created_at DESC
    //             ) as rn
    //         "),
    //     ]);

    // // 2) static_parameters (hammasi) + oxirgi history (bo'lsa)
    // $rows = DB::table('static_parameters as sp')
    //     ->leftJoinSub($histBase, 'lh', function ($join) {
    //         $join->on('lh.static_id', '=', 'sp.id')
    //              ->where('lh.rn', '=', 1);
    //     })
    //     ->leftJoin('parameters as p', 'sp.ParameterID', '=', 'p.id')
    //     ->leftJoin('units as u', 'p.UnitsID', '=', 'u.id')
    //     ->leftJoin('period_types as pt', 'lh.period_type_id', '=', 'pt.id')
    //     ->where('sp.NumberPage', $pageId)
    //     ->orderBy('sp.OrderNumberSex')
    //     ->orderBy('sp.OrderNumberGroup')
    //     ->orderBy('sp.OrderNumber')
    //     ->select([
    //         // — meta (strukturaviy) ustunlar: doim bor
    //         'sp.id as static_id',
    //         'sp.NumberPage',
    //         'sp.FactoryStructureID',
    //         'sp.GroupID',
    //         'sp.ParameterID',
    //         'sp.OrderNumberSex',
    //         'sp.OrderNumberGroup',
    //         'sp.OrderNumber',

    //         // — parametr / birlik nomlari
    //         'p.Name as PName',
    //         'u.Name as UName',

    //         // — period va qiymatlar (bo'lsa); bo'lmasa NULL qaytadi
    //         'lh.value',
    //         'lh.period_type_id',
    //         'lh.period_start_date',
    //         'lh.period_end_date',
    //         DB::raw('lh.comment as Comment'),
    //         'pt.name as PTName',
    //     ])
    //     ->get();

    // return response()->json($rows);
}
    public function periodType()
    {
        return DB::table('period_types')->get();

    }
public function getRowUnit($id)
{
    $row = \DB::table('static_parameters as sp')
        ->leftJoin('period_types as pt', 'sp.period_type_id', '=', 'pt.id')
        ->leftJoin('parameters as p', 'sp.ParameterID', '=', 'p.id')
        ->leftJoin('units as u', 'p.UnitsID', '=', 'u.id')
        ->leftJoin('factory_structures as fs', 'sp.FactoryStructureID', '=', 'fs.id')
        ->leftJoin('groups as g', 'sp.GroupID', '=', 'g.id')
        // ⚠️ Agar sahifalar jadvali nomi boshqacha bo‘lsa, moslashtiring (masalan: pages_svodka)
        ->leftJoin('number_pages as pg', 'sp.NumberPage', '=', 'pg.NumberPage')
        ->where('sp.id', $id)
        ->select([
            'sp.*',                                   // ID’lar – v-model uchun
            'pt.name        as PTName',

            'p.Name         as PName',
            'p.NameRus      as PNameRus',

            'u.Name         as UName',

            'fs.Name        as FSName',
            'fs.NameRus     as FSNameRus',
            'fs.OrderNumberSex     as OrderNumberSex',

            'g.Name         as GName',
            'g.NameRus      as GNameRus',
            'g.OrderNumberGroup      as OrderNumberGroup',

            'pg.Name        as PageName',
            'pg.NameRus     as PageNameRus',
        ])
        ->first();

    if (!$row) {
        return response()->json(['message' => 'Not found'], 404);
    }

    return response()->json($row);
}

    private function create(Request $request)
    {
        // dd($request);
        $uuid = Str::uuid();
        $uuidString = $uuid->toString();


        $unit = StaticParameters::create([
            'id' => $uuidString,
            'FactoryStructureID' => $request->FactoryStructureID,
            'ParameterID' => $request->ParameterID,
            'NumberPage' => $request->NumberPage,
            'GroupID' => $request->GroupID,
            'OrderNumber' => $request->OrderNumber,
            'period_type_id' => $request->PeriodTypeId,
            'period_start_date' => $request->PeriodStartDate,
            'period_end_date' => $request->PeriodEndDate,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'unit' => $unit
        ]);
    }

    private function update(Request $request)
    {
        $unit = StaticParameters::find($request->id);
        $unit->update([
            'GroupID' => $request->GroupID,
            'OrderNumber' => $request->OrderNumber,
            'period_type_id' => $request->PeriodTypeId,
            'period_start_date' => $request->PeriodStartDate,
            'period_end_date' => $request->PeriodEndDate,
            'Comment' => $request->Comment,
            'ParameterID' => $request->ParameterID,
            'NumberPage' => $request->NumberPage,
            
            
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
            $unit = StaticParameters::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }

    // public function staticWithNumberPage($id){
    //    return StaticParameters::join('period_types','static_parameters.period_type_id','=','period_types.id')
    //     ->join('parameters','static_parameters.ParameterID','=','parameters.id')
    //     ->join('units', 'parameters.UnitsID', '=', 'units.id')
    //     ->where('NumberPage',$id)
    //     ->select('units.Name as UName','period_types.name as PTName','parameters.Name as PName','static_parameters.*')
    //     ->get();
    // }
public function staticWithNumberPage($id)
{
    $pageId = (int) $id;
    $date   = request('date') ?: now()->toDateString(); // YYYY-MM-DD

    // Shu sanaga TEGISHLI histories (kunlik = aynan o'sha kun, oylik = oy oralig'i)
    $hist = DB::table('static_histories as h')
        ->select(
            'h.id',
            'h.static_id',
            'h.value',
            'h.period_type_id',
            'h.period_start_date',
            'h.period_end_date',
            DB::raw('CAST(h.comment AS NVARCHAR(4000)) as Comment')
        )
        ->whereDate('h.period_start_date', '<=', $date)
        ->whereDate('h.period_end_date',   '>=', $date);

    $rows = DB::table('static_parameters as sp')
        ->leftJoinSub($hist, 'h', function ($j) {
            $j->on('h.static_id', '=', 'sp.id');
        })
        ->leftJoin('parameters as p', 'sp.ParameterID', '=', 'p.id')
        ->leftJoin('units as u', 'p.UnitsID', '=', 'u.id')
        ->leftJoin('factory_structures as fs', 'sp.FactoryStructureID', '=', 'fs.id')
        ->leftJoin('groups as g', 'sp.GroupID', '=', 'g.id')
        ->leftJoin('period_types as pt', 'h.period_type_id', '=', 'pt.id')
        ->where('sp.NumberPage', $pageId)
        ->orderBy('fs.OrderNumberSex')
        ->orderBy('g.OrderNumberGroup')
        ->orderBy('sp.OrderNumber')
        ->select([
            // static meta (qatorlarni chizish uchun)
            'sp.id                as StaticID',
            'sp.NumberPage',
            'sp.FactoryStructureID',
            'sp.GroupID',
            'sp.ParameterID',
            'fs.OrderNumberSex',
            'g.OrderNumberGroup',
            'sp.OrderNumber',

            // nomlar
            'fs.Name              as FSName',
            'g.Name               as GName',
            'p.Name               as PName',
            'u.Name               as UName',

            // history qiymatlari (bo‘lsa)
            'h.value',
            'h.period_type_id',
            'h.period_start_date',
            'h.period_end_date',
            DB::raw('h.Comment'),            // TEXT -> NVARCHAR bo'lib qaytadi
            'pt.name              as PTName',
        ])
        ->get();

    return response()->json($rows);
}


public function upsert(Request $r)
{
    $data = $r->validate([
        'number_page'        => 'required|integer',
        'factory_structure_id' => 'nullable',
        'parameter_id'       => 'required|string',
        'group_id'           => 'nullable',
        'period_type_id'     => 'required|integer',
        'period_start_date'  => 'required|date',
        'period_end_date'    => 'required|date',
        'value'              => 'nullable|numeric',
        'comment'            => 'nullable|string',
        'for_date'             => 'sometimes|date',   
    ]);
    $forDate = data_get($data, 'for_date'); // yo'q bo'lsa null bo'ladi
    // bo'sh string -> null
    if (array_key_exists('comment', $data) && $data['comment'] === '') {
        $data['comment'] = null;
    }

    // 1) Static param (ro‘yxat shu jadvaldan olinadi)
    $static = StaticParameters::query()->where([
        'FactoryStructureID' => $data['factory_structure_id'],
        'ParameterID'        => $data['parameter_id'],
        'NumberPage'         => $data['number_page'],
        'GroupID'            => $data['group_id'],
    ])->first();

    if (!$static) {
        return response()->json([
            'status'  => 404,
            'message' => 'Static parameter topilmadi (FS/Param/Group/NumberPage kombinatsiyasi mos emas).'
        ], 404);
    }

    // 2) Histories upsert — STATIC_PARAMETERS GA QIYMAT YOZILMAYDI!
    $key = [
        'static_id'         => $static->id,
        'period_start_date' => $data['period_start_date'],
        'period_end_date'   => $data['period_end_date'],
        'period_type_id'    => $data['period_type_id'],
    ];

    // TEXT tipidagi comment bilan tenglik solishtirishda CAST ishlatamiz
    $q = StaticHistory::query()->where($key);
    if (is_null($data['comment'])) {
        $q->whereNull('comment');
    } else {
        $q->whereRaw('CAST([comment] AS NVARCHAR(MAX)) = CAST(? AS NVARCHAR(MAX))', [$data['comment']]);
    }

    $row = $q->first();

    if ($row) {
        // UPDATE faqat history qiymati
        $row->value = $data['value'];
        // Agar commentni ham yangilash kerak bo‘lsa (odatda shart emas):
        // $row->comment = $data['comment'];
        $row->save();
    } else {
        // INSERT
        $payload = $key + [
            'id'      => (string) Str::uuid(),
            'value'   => $data['value'],
            'comment' => $data['comment'],
            'date'   => $forDate,    
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $row = StaticHistory::create($payload);
    }

    return response()->json([
        'status'  => 200,
        'message' => "Qiymat tarixga saqlandi",
        'item'    => $row,
    ]);
}




}
