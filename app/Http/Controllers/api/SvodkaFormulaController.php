<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Parameters;
use Illuminate\Http\Request;
use App\Models\SvodkaFormula;
use Illuminate\Support\Str;
class SvodkaFormulaController extends Controller
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
        $units = SvodkaFormula::all();
        return response()->json($units);
    }
    private function getRowUnit($id)
    {
        $row = SvodkaFormula::where('param_id', $id)->first();
        return response()->json([
            'status' => 200,
            'data' => $row,
        ]);
    }

public function getByParam($paramId)
{
    // 1. Validatsiya (ixtiyoriy, agar UUID bo‘lishi shart bo‘lsa)
    if (!Str::isUuid($paramId)) {
        return response()->json(['error' => 'param_id must be a valid UUID'], 400);
    }

    // 2. Formula olish
    $formula = SvodkaFormula::where('param_id', $paramId)->first();

    if (!$formula) {
        return response()->json(null, 200); // yoki 404 bilan xatolik
    }

    // 3. Tokenlarni dekodlash
    $tokens = $formula->tokens ?? [];

    // 4. Pid= larni ajratish
    $pids = collect($tokens)
    ->filter(fn($t) => is_string($t) && Str::startsWith($t, 'Pid='))
    ->map(function ($t) {
        preg_match('/Pid=([^|]+)/', $t, $match);
        return $match[1] ?? null;
    })
    ->filter()
    ->unique();

    // 5. Parametr nomlarini olish
    $paramNames = Parameters::whereIn('id', $pids)->pluck('Name', 'id');

    // 6. Tushunarli ko‘rinishga keltirish
    $tokensReadable = collect($tokens)->map(function ($token) use ($paramNames) {
        if (preg_match('/Pid=([^|]+)\|agg=(\w+)\|func=(\w+)\|scope=(\w+)/', $token, $match)) {
            $pid = $match[1];
            $agg = $match[2];
            $func = $match[3];
            $scope = $match[4];
            $name = $paramNames[$pid] ?? 'Nomaʼlum parametr';

            return "{$name} ({$agg} {$func}, {$scope})";
        }

        return $token;
    });

    // 7. Javob qaytarish
    return response()->json([
        'id' => $formula->id,
        'tokens' => $tokens,
        'tokens_readable' => $tokensReadable,
        'comment' => $formula->comment,
    ]);
}

    private function create(Request $request)
    {
        // dd($request);

        $row = SvodkaFormula::updateOrCreate(
            ['param_id' => $request['param_id']],
            [
                'page_id_blog' => $request['page_id_blog'],
                'sex_id' => $request['sex_id'],
                'page_id' => $request['page_id'],
                'group_id' => $request['group_id'],
                'tokens' => $request['tokens'],   // casts -> json
                'comment' => $request['comment'] ?? null,
            ]
        );

        return response()->json([
            'status' => 200,
            'message' => 'Formula saqlandi',
            'data' => $row,
        ], 200);
    }

    private function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:units,id',
            'Name' => 'required|string|max:255',
            'NameRus' => 'required|string|max:255',
            'ShortName' => 'required|string|max:255',
            'ShortNameRus' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $unit = SvodkaFormula::find($request->id);
        $unit->update([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'ShortName' => $request->ShortName,
            'ShortNameRus' => $request->ShortNameRus,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli yangilandi",
            'unit' => $unit
        ]);
    }

    public function delete(Request $request, $id)
    {
            $unit = SvodkaFormula::where('param_id', $id)->delete();
           return response()->json(['status' => 200, 'deleted' => $unit]);
        
    }
}
