<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FactoryStructure;
use Illuminate\Http\JsonResponse;
use App\Models\NumberPage;            // sahifa
use App\Models\Groups;                // guruh
use App\Models\GraphicsParamenters;   // parametr-bog‘lovchi
use Illuminate\Support\Collection;
use DB;
class FactoryStructureController extends Controller
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
                return $this->update($request,$id);
            case 'DELETE':
                return $this->delete($request,$id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }

    private function index()
    {
        
        $fstructure = FactoryStructure::all();
        return response()->json($fstructure);
    }
    private function getRowUnit($id)
    {
        $unit = FactoryStructure::find($id);
        return response()->json($unit);
    }
    public function tree(): JsonResponse
    {
        /* 1)  Barcha jadval ma’lumotlarini oldindan tortib olamiz */
        $sexes   = FactoryStructure::orderBy('Name')->get();          // id => sex
        $pages   = NumberPage::all()->groupBy('StructureID');         // sid => collection
        $groups  = Groups::all()->groupBy(fn ($g) => $g->StructureID.'-'.$g->PageID);

        $params  = GraphicsParamenters::with('parameters:id,Name')
                   ->get()
                   ->groupBy(fn ($p) =>
                       $p->FactoryStructureID.'-'.$p->PageId.'-'.$p->GroupID);
                   // key →  sid-page-group

        /* 2)  Daraxtni yig‘amiz */
        $tree = $sexes->map(function ($sex) use ($pages, $groups, $params) {

            $sexNode = [
                'key'      => "sex-{$sex->id}",
                'title'    => $sex->Name,
                'type'     => 'sex',
                'children' => [],
            ];

            foreach (($pages[$sex->id] ?? collect()) as $page) {

                $pageNode = [
                    'key'      => "page-{$sex->id}-{$page->id}",
                    'title'    => $page->Name,
                    'type'     => 'page',
                    'children' => [],
                ];

                foreach (($groups["{$sex->id}-{$page->id}"] ?? collect()) as $group) {

                    $grpKey = "{$sex->id}-{$page->id}-{$group->id}";
                    $grpNode = [
                        'key'      => "grp-$grpKey",
                        'title'    => $group->Name,
                        'type'     => 'group',
                        'children' => [],
                    ];

                    foreach (($params[$grpKey] ?? collect()) as $p) {
                        $grpNode['children'][] = [
                            'key'         => "prm-{$p->id}",
                            'title'       => $p->parameters->Name ?? "Param #{$p->id}",
                            'ParameterID' => $p->ParametersID,   // GUID
                            'type'        => 'param',
                        ];
                    }

                    $pageNode['children'][] = $grpNode;
                }

                $sexNode['children'][] = $pageNode;
            }

            return $sexNode;
        })->values();

        return response()->json($tree);
    }
    public function getForUser($id)
    {
        // dd($id);
        $idsArray = explode(',', $id);
        $unit = FactoryStructure::whereIn('id', $idsArray)->get();
        return response()->json($unit);
    }
    private function create(Request $request)
    {
        // $request->validate([
        //     'Name' => 'required|string|max:255',
        //     'ShortName' => 'required|string|max:255',
        //     'Comment' => 'nullable|string|max:255',
        // ]);

        $unit = FactoryStructure::create([
            'Name' => $request->Name,
            'NameRus' => $request->NameRus,
            'ShortName' => $request->ShortName,
            'ShortNameRus' => $request->ShortNameRus,
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
        $request->validate([
            'Name' => 'required|string|max:255',
            'NameRus' => 'required|string|max:255',
            'ShortName' => 'required|string|max:255',
            'ShortNameRus' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $unit = FactoryStructure::find($request->id);
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
        try {
            $unit = FactoryStructure::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
