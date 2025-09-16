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
    private function isValidCell(string $cell): bool
    {
        return (bool) preg_match('/^[A-Z]+[1-9]\d*$/', $cell);
    }

    /** GET /sheet/formula?numberPage=303&date=10.09.2025 */
public function getFormulas(Request $req)
{
    $req->validate([
        'numberPage' => 'required|integer|min:1',
        'date'       => 'required|string',
    ]);

    $page    = (int) $req->get('numberPage');
    $forDate = $this->parseDate($req->get('date'));

    // 1) doimiy (for_date = NULL)
    $permanent = \App\Models\SheetFormulas::query()
        ->where('number_page', $page)
        ->whereNull('for_date')
        ->get(['cell','expr']);

    // 2) shu kunga tegishli
    $dated = \App\Models\SheetFormulas::query()
        ->where('number_page', $page)
        ->where('for_date', $forDate)
        ->get(['cell','expr']);

    // 3) merge: dated > permanent
    $map = [];
    foreach ($permanent as $r) $map[$r->cell] = $r->expr;
    foreach ($dated as $r)     $map[$r->cell] = $r->expr;

    $items = [];
    foreach ($map as $cell => $expr) $items[] = ['cell'=>$cell,'expr'=>$expr];

    return response()->json(['items' => $items]);
}



    /** POST /sheet/formula  { numberPage, date, cell, expr }  */
public function saveFormula(Request $req)
{
    $page = (int) $req->get('numberPage');
    $cell = strtoupper(trim($req->get('cell')));
    $expr = trim((string) $req->get('expr'));
    $scope = $req->get('scope', 'dated'); // 'permanent' yoki 'dated'

    if (!$this->isValidCell($cell)) {
        return response()->json(['message' => 'Noto‘g‘ri katak nomi'], 422);
    }
    if (!str_starts_with($expr, '=')) {
        $expr = '=' . $expr;
    }

    // permanent -> for_date = NULL, aks holda kiritilgan sana
    $forDate = null;
    if ($scope !== 'permanent') {
        $dateStr = $req->get('date');                // dd.mm.yyyy yoki yyyy-mm-dd
        $forDate = $this->parseDate((string) $dateStr);
    }

    // (number_page, cell, for_date) bo‘yicha upsert
    $row = \App\Models\SheetFormulas::firstOrNew([
        'number_page' => $page,
        'cell'        => $cell,
        'for_date'    => $forDate, // NULL bo‘lishi ham mumkin
    ]);

    if (!$row->exists) {
        $row->id = (string) \Illuminate\Support\Str::uuid(); // yangi bo‘lsa id beramiz
    }

    $row->expr = $expr;
    $row->save();

    return response()->json([
        'status'  => 200,
        'message' => 'Formula saqlandi (yangilandi)',
        'item'    => $row,
    ]);
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
