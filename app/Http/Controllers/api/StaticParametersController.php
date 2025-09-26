<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StaticParameters;
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
            ->where('sp.NumberPage', $id)
            ->select([
                'u.Name as UName',
                'pt.name as PTName',
                'p.Name as PName',
                'sp.*',
            ])
            ->get();

        return response()->json($units);
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
        return \DB::table('static_parameters as sp')
            ->leftJoin('period_types as pt', 'sp.period_type_id', '=', 'pt.id')
            ->leftJoin('parameters as p', 'sp.ParameterID', '=', 'p.id')
            ->leftJoin('units as u', 'p.UnitsID', '=', 'u.id')
            ->leftJoin('factory_structures as fs', 'sp.FactoryStructureID', '=', 'fs.id')
            ->leftJoin('groups as g', 'sp.GroupID', '=', 'g.id')
            ->where('sp.NumberPage', $id)
            ->select([
                'sp.*',
                'u.Name as UName',
                'pt.name as PTName',
                'p.Name as PName',
                'fs.Name as FSName',
                'fs.OrderNumberSex as OrderNumberSex',
                'g.Name as GName',
                'g.OrderNumberGroup as OrderNumberGroup',

            ])
            ->orderBy('sp.FactoryStructureID')
            ->orderBy('sp.GroupID')
            ->orderBy('p.Name')
            ->get();
    }

    public function upsert(Request $r)
    {
        $data = $r->validate([
            'number_page' => 'required|integer',
            'factory_structure_id' => 'nullable',
            'parameter_id' => 'required|string',
            'group_id' => 'nullable',
            'period_type_id' => 'required|integer',
            'period_start_date' => 'required|date',
            'period_end_date' => 'required|date', // daily uchun ham start=end yuborasiz
            'value' => 'nullable|numeric',
            'comment' => 'nullable|string',
        ]);

        // Bo'sh string bo'lsa nullga aylantiramiz 
        if (array_key_exists('comment', $data) && $data['comment'] === '') {
            $data['comment'] = null;
        }

        // Unikal kalitlar (id NI BU YERGA QO‘YMAYMIZ!)
        $key = [
            'FactoryStructureID' => $data['factory_structure_id'],
            'ParameterID' => $data['parameter_id'],
            'NumberPage' => $data['number_page'],
            'GroupID' => $data['group_id'],
            'period_type_id' => $data['period_type_id'],
            'period_start_date' => $data['period_start_date'],
            'period_end_date' => $data['period_end_date'],
        ];

        // Avval mavjudini topamiz (Comment TEXT bo‘lgani uchun CAST qilamiz)
        $q = StaticParameters::query()->where($key);

        if (is_null($data['comment'])) {
            $q->whereNull('Comment');
        } else {
            // TEXT -> NVARCHAR(MAX) ga CAST qilib taqqoslash
            $q->whereRaw('CAST([Comment] AS NVARCHAR(MAX)) = ?', [$data['comment']]);
        }

        $row = $q->first();

        if ($row) {
            // UPDATE
            $row->value = $data['value'];
            $row->save();
        } else {
            // INSERT (shu yerda id ni beramiz, where da EMAS)
            $payload = $key + [
                'id' => (string) Str::uuid(),
                'Comment' => $data['comment'],
                'value' => $data['value'],
            ];
            $row = StaticParameters::create($payload);
        }

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'item' => $row
        ]);
    }



}
