<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SheetFormulas;
use App\Models\SheetValue;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
class SheetController extends Controller
{
    /** dd.mm.yyyy | yyyy-mm-dd ni datega aylantirish */
    private function parseDate(string $s): string
    {
        $s = trim($s);
        try {
            if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $s)) {
                return Carbon::createFromFormat('d.m.Y', $s)->format('Y-m-d');
            }
            return Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable $e) {
            return Carbon::today()->format('Y-m-d');
        }
    }

    /** Katak nomi validatsiyasi: A1..Z9999... */
    // private function isValidCell(string $cell): bool
    // {
    //     return (bool) preg_match('/^[A-Z]+[1-9]\d*$/', $cell);
    // }

    /** GET /sheet/formula?numberPage=303&date=10.09.2025 */
public function getFormulas(Request $req)
{
    $req->validate([
        'numberPage' => 'required|integer|min:1',
        'date'       => 'required|string',
    ]);

    $page    = (int) $req->get('numberPage');
    $forDate = $this->parseDate($req->get('date'));

    // Doimiy (for_date = NULL)
    $permanent = \App\Models\SheetFormulas::query()
        ->where('number_page', $page)
        ->whereNull('for_date')
        ->get(['param_id','period_type_id','cell','expr','expr_stable']);

    // Shu kunga tegishli
    $dated = \App\Models\SheetFormulas::query()
        ->where('number_page', $page)
        ->where('for_date', $forDate)
        ->get(['param_id','period_type_id','cell','expr','expr_stable']);

    // Merge: dated > permanent  (kalit: param_id+period_type_id bo‘lsa shunga, bo‘lmasa cell bo‘yicha)
    $map = [];

    $keyOf = function($r) {
        if ($r->param_id && $r->period_type_id) {
            return 'P:' . $r->param_id . ':' . $r->period_type_id;
        }
        return 'C:' . strtoupper((string)$r->cell);
    };

    foreach ($permanent as $r) $map[$keyOf($r)] = $r;
    foreach ($dated as $r)     $map[$keyOf($r)] = $r;

    $items = [];
    foreach ($map as $r) {
        $items[] = [
            'param_id'       => $r->param_id,
            'period_type_id' => $r->period_type_id,
            'cell'           => $r->cell,
            'expr'           => $r->expr_stable ?: $r->expr,
            'expr_stable'    => $r->expr_stable ?: $r->expr,
        ];
    }

    return response()->json(['items' => $items]);
}

public function listFormulas(Request $req) {
  $page = (int)($req->input('numberPage') ?? $req->input('number_page'));
  $forDate = $this->parseDate((string)$req->input('date')); // yoki null

  $items = \App\Models\SheetFormulas::where('number_page',$page)
    ->when($forDate !== null, fn($q) => $q->where(function($qq) use($forDate){
        $qq->whereNull('for_date')->orWhere('for_date',$forDate);
    }), fn($q)=>$q->whereNull('for_date'))
    ->get(['param_id','slot','cell','expr_stable as expr','expr_raw','for_date']);

  return response()->json(['items'=>$items]);
}



    /** POST /sheet/formula  { numberPage, date, cell, expr }  */
public function saveFormula(Request $req)
{
    $page = (int) ($req->input('numberPage') ?? $req->input('number_page'));
    $cell = strtoupper(trim((string) ($req->input('cell') ?? '')));
    $paramIdRaw = $req->input('param_id') ?? $req->input('parameter_id');
    $periodTypeIdRaw = $req->input('period_type_id'); // 🔹 yangi
    $periodLabel = $req->input('period_label');       // ixtiyoriy, nom bilan moslashtirishga yordam beradi
    $date = $req->input('date');

    $exprRaw    = trim((string) ($req->input('expr_raw') ?? $req->input('expr') ?? ''));
    $exprStable = trim((string) ($req->input('expr_stable') ?? ''));
    $scope = $req->input('scope', 'dated'); // 'permanent' | 'dated'

    if (!$page) {
        return response()->json(['message' => 'numberPage/number_page kiritilmadi'], 422);
    }
    if ($exprRaw === '' && $exprStable === '') {
        return response()->json(['message' => 'Formula bo‘sh bo‘lishi mumkin emas'], 422);
    }
    if ($exprStable === '') { $exprStable = $exprRaw; }
    if (!\Illuminate\Support\Str::startsWith($exprRaw, '='))    { $exprRaw    = '=' . $exprRaw; }
    if (!\Illuminate\Support\Str::startsWith($exprStable, '=')) { $exprStable = '=' . $exprStable; }

    // for_date
    $forDate = null;
    if ($scope !== 'permanent') {
        $forDate = $this->parseDate((string) $req->input('date'));
    }

    // param_id
    $paramId = $this->normalizeUuid($paramIdRaw);

    // 🔹 period_type_id ni aniqlash (raqam bo'lsa to'g'ridan-to'g'ri, bo'lmasa nomdan topamiz)
    $periodTypeId = null;
    if ($periodTypeIdRaw !== null && is_numeric($periodTypeIdRaw)) {
        $periodTypeId = (int) $periodTypeIdRaw;
    } elseif ($periodLabel) {
        $name = trim(mb_strtolower($periodLabel));
        $found = \DB::table('period_types')
            ->whereRaw('LOWER(TRIM(name)) = ?', [$name])
            ->value('id');
        if ($found) $periodTypeId = (int) $found;
    }

    $canUseParamKey = $paramId && $periodTypeId;

    if (!$canUseParamKey && !$this->isValidCell($cell)) {
        return response()->json(['message' => 'Parametr+period_type_id yoki to‘g‘ri katak kerak'], 422);
    }

    // 🔹 Upsert kaliti: (number_page, param_id, period_type_id, for_date)
    if ($canUseParamKey) {
        $row = \App\Models\SheetFormulas::firstOrNew([
            'number_page'    => $page,
            'param_id'       => $paramId,
            'period_type_id' => $periodTypeId,
            'for_date'       => $forDate,
        ]);
    } else {
        // Legacy: cell bo‘yicha
        $row = \App\Models\SheetFormulas::firstOrNew([
            'number_page' => $page,
            'cell'        => $cell,
            'for_date'    => $forDate,
        ]);
    }

    if (!$row->exists) {
        $row->id = (string) \Illuminate\Support\Str::uuid();
    }

    if ($canUseParamKey) {
        $row->param_id       = $paramId;
        $row->period_type_id = $periodTypeId;
    }
    if ($this->isValidCell($cell)) {
        $row->cell = $cell; // diagnostika uchun
    }
    $row->date        = $date;
    $row->expr        = $exprStable;
    $row->expr_stable = $exprStable;
    $row->expr_raw    = $exprRaw;

    $row->save();

    return response()->json([
        'status'  => 200,
        'message' => 'Formula saqlandi (parametrga bog‘landi)',
        'item'    => $row,
    ]);
}


/** --------- Helpers --------- */

private function normalizeUuid($v)
{
    $s = strtoupper(trim((string) $v));
    if ($s === '') return null;
    // Laravelda mavjud: Str::isUuid
    return Str::isUuid($s) ? $s : null;
}

private function normalizeSlot($slot)
{
    $s = strtolower(trim((string) $slot));
    $ok = [
        'daily_plan',
        'daily_fact_20_08',
        'daily_fact_08_20',
        'daily_fact_total',
        'daily_percent',
        'monthly_plan',
        'monthly_fact',
        'monthly_percent',
    ];
    return in_array($s, $ok, true) ? $s : null;
}

private function slotFromCell($cell)
{
    if (!$this->isValidCell($cell)) return null;
    if (!preg_match('/^([A-Z]+)/', $cell, $m)) return null;
    $col = $m[1];
    $map = [
        'C' => 'daily_plan',
        'D' => 'daily_fact_20_08',
        'E' => 'daily_fact_08_20',
        'F' => 'daily_fact_total',
        'G' => 'daily_percent',
        'H' => 'monthly_plan',
        'I' => 'monthly_fact',
        'J' => 'monthly_percent',
    ];
    return $map[$col] ?? null;
}

private function isValidCell($cell)
{
    // A1, AB12, ... ko‘rinish
    return (bool) preg_match('/^[A-Z]+[1-9]\d*$/', $cell);
}

public function saveValuesBulk(Request $req)
{
    // 1) Validatsiya
    $data = $req->validate([
        'numberPage'   => 'required|integer|min:1',
        'date'         => 'required|string',
        'items'        => 'required|array|min:1',
        'items.*.cell' => 'required|string|max:16',
        'items.*.value'=> 'nullable',
    ]);

    $page    = (int)$data['numberPage'];
    $forDate = $this->parseDate($data['date']);   // dd.mm.yyyy | yyyy-mm-dd -> 'YYYY-mm-dd'
    $now     = now();

    // 2) Raws tayyorlash (faqat valid kataklar)
    $rows = [];
    foreach ($data['items'] as $it) {
        $cell = strtoupper(trim($it['cell'] ?? ''));
        if (!$this->isValidCell($cell)) {
            // noto‘g‘ri katak nomi bo‘lsa — tashlab ketamiz
            continue;
        }

        // qiymatni normalize qilamiz: bo‘sh -> null, raqam bo‘lsa float, aks holda null
        $val = $it['value'] ?? null;
        if ($val === '' || $val === null) {
            $val = null;
        } else {
            $val = is_numeric($val) ? (float)$val : null;
        }

        $rows[] = [
            'id'           => (string) Str::uuid(), // upsert insert qismida kerak bo‘ladi
            'number_page'  => $page,
            'for_date'     => $forDate,
            'cell'         => $cell,
            'value'        => $val,
            'created_at'   => $now,
            'updated_at'   => $now,
        ];
    }

    if (empty($rows)) {
        return response()->json([
            'status'  => 200,
            'message' => 'Hech qanday to‘g‘ri katak topilmadi (hammasi filtrdan o‘tdi).',
            'saved'   => 0,
        ]);
    }

    // 3) Bulk upsert (SQL Server MERGE orqali)
    // unique: number_page + for_date + cell
DB::transaction(function () use ($rows) {
    foreach ($rows as $r) {
        \App\Models\SheetValue::updateOrCreate(
            ['number_page' => $r['number_page'], 'for_date' => $r['for_date'], 'cell' => $r['cell']],
            ['value' => $r['value'], 'updated_at' => $r['updated_at']]
        );
    }
});


    return response()->json([
        'status'  => 200,
        'message' => 'Qiymatlar keshga saqlandi',
        'saved'   => count($rows),
    ]);
}


    /** GET /sheet/value?numberPage=304&cell=B6&date=10.09.2025 */
    public function getValue(Request $req)
    {
        $req->validate([
            'numberPage' => 'required|integer|min:1',
            'cell' => 'required|string|max:16',
            'date' => 'required|string',
        ]);

        $page = (int) $req->get('numberPage');
        $forDate = $this->parseDate($req->get('date'));
        $cell = strtoupper(trim($req->get('cell')));

        if (!$this->isValidCell($cell)) {
            return response()->json(['message' => 'Noto‘g‘ri katak nomi'], 422);
        }

        // 1) soddalashtirilgan variant: qiymatni sheet_values dan olamiz
        $val = SheetValue::query()
            ->where('number_page', $page)
            ->where('for_date', $forDate)
            ->where('cell', $cell)
            ->value('value');

        // 2) (ixtiyoriy) Agar yo‘q bo‘lsa, shu yerda sizning "selectResultBlogs" yoki
        //    boshqa real manbadan topib kelishingiz mumkin. Hozircha yo‘q bo‘lsa null.
        return response()->json(['value' => $val]);
    }

    /** (ixtiyoriy) POST /sheet/value  { numberPage, date, cell, value } */
    public function saveValue(Request $req)
    {
        $req->validate([
            'numberPage' => 'required|integer|min:1',
            'date' => 'required|string',
            'cell' => 'required|string|max:16',
            'value' => 'nullable',
        ]);

        $page = (int) $req->get('numberPage');
        $forDate = $this->parseDate($req->get('date'));
        $cell = strtoupper(trim($req->get('cell')));

        if (!$this->isValidCell($cell)) {
            return response()->json(['message' => 'Noto‘g‘ri katak nomi'], 422);
        }

        $val = $req->get('value');
        // raqamga normallashtirish: bo'sh bo'lsa null
        if ($val === '' || $val === null) {
            $val = null;
        } else {
            $val = is_numeric($val) ? (float) $val : null;
        }

        $row = SheetValue::updateOrCreate(
            ['number_page' => $page, 'for_date' => $forDate, 'cell' => $cell],
            ['value' => $val]
        );

        return response()->json([
            'status' => 200,
            'message' => 'Qiymat saqlandi',
            'item' => $row,
        ]);
    }
}
