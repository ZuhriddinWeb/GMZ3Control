<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DocumentNumberPage;
use Illuminate\Http\JsonResponse;

use App\Models\FactoryStructure;
use App\Models\NumberPage;
use App\Models\Groups;
use App\Models\GraphicsParamenters;
use Illuminate\Support\Arr;
use Closure;
class DocumentNumberPageController extends Controller
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
        $units = DocumentNumberPage::all();
        return response()->json($units);
    }
    /**
     *  GET /document-number-pages/{id}
     *  – JSON ustunlarini array qilib,  FactoryStructure  va  NumberPage  bilan bog‘lab chiqaradi
     */
    private function chunkedWhereInQuery(string $model, string $column, array $values, ?Closure $queryCallback = null)
    {
        if (empty($values)) {
            return collect(); // bo‘sh array kelsa, query qilmaydi
        }

        return collect($values)->chunk(2000)->flatMap(function ($chunk) use ($model, $column, $queryCallback) {
            $query = $model::whereIn($column, $chunk);
            if ($queryCallback) {
                $query = $queryCallback($query);
            }
            return $query->get();
        });
    }
    private function chunkedWhereDoubleInQuery(string $model, string $column1, array $values1, string $column2, array $values2, ?Closure $queryCallback = null)
    {
        if (empty($values1) || empty($values2)) {
            return collect();
        }

        $result = collect();

        foreach (array_chunk($values1, 1000) as $chunk1) {
            foreach (array_chunk($values2, 1000) as $chunk2) {
                foreach ($chunk1 as $value1) {
                    foreach ($chunk2 as $value2) {
                        $query = $model::where($column1, $value1)->where($column2, $value2);
                        if ($queryCallback) {
                            $query = $queryCallback($query);
                        }
                        $result = $result->merge($query->get());
                    }
                }
            }
        }

        return $result;
    }





    public function getRowUnit(int $docId): JsonResponse
    {
        ini_set('max_execution_time',3600);
        /** @var DocumentNumberPage|null $doc */
        // $doc = DocumentNumberPage::find($docId);
        $doc = DocumentNumberPage::where('IdNumberPage', $docId)->first();

        $selected = [];
        if ($doc && $doc->ParameterBlogs) {
            if (is_string($doc->ParameterBlogs)) {
                $decoded = json_decode($doc->ParameterBlogs, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $selected = $decoded;
                }
            } elseif (is_array($doc->ParameterBlogs)) {
                $selected = $doc->ParameterBlogs;
            }
        }

        // dd($selected);



        $structureIds = FactoryStructure::orderBy('id')->pluck('id')->all();
        if (empty($structureIds)) {
            return response()->json(['tree' => [], 'selected' => $selected]);
        }

        $flatPages = NumberPage::whereIn('StructureID', $structureIds)->pluck('NumberPage')->all();
        if (empty($flatPages)) {
            return response()->json(['tree' => [], 'selected' => $selected]);
        }

        // 1. Structures
        $structures = FactoryStructure::whereIn('id', $structureIds)->get()->keyBy('id');

        // 2. Pages
        $pages = $this->chunkedWhereInQuery(NumberPage::class, 'NumberPage', $flatPages);
        $pagesBySid = $pages->groupBy('StructureID');

        // 3. Groups
        $groups = $this->chunkedWhereDoubleInQuery(
            Groups::class,
            'StructureID',
            $structureIds,
            'PageID',
            $flatPages
        )->groupBy(fn($g) => $g->StructureID . '-' . $g->PageID);

        // 4. Params
        $params = $this->chunkedWhereDoubleInQuery(
            GraphicsParamenters::class,
            'PageId',
            $flatPages,
            'FactoryStructureID',
            $structureIds,
            function ($query) {
                return $query->with('parameter:id,Name');
            }
        );
        $paramsByGroup = $params->groupBy(fn($p) => $p->FactoryStructureID . '-' . $p->PageId . '-' . $p->GroupID);

        // 5. Daraxt
        $tree = collect($structureIds)->map(function ($sid) use ($structures, $pagesBySid, $groups, $paramsByGroup) {
            $sexNode = [
                'key' => "sex-$sid",
                'title' => $structures[$sid]->Name ?? "Structure #$sid",
                'type' => 'sex',
                'children' => [],
            ];

            foreach (($pagesBySid[$sid] ?? collect()) as $page) {
                $pageNum = $page->NumberPage;

                $pageNode = [
                    'key' => "page-$sid-$pageNum",
                    'title' => $page->Name,
                    'type' => 'page',
                    'children' => [],
                ];

                foreach (($groups["$sid-$pageNum"] ?? collect()) as $g) {
                    $gKey = "$sid-$pageNum-{$g->id}";
                    $paramColl = $paramsByGroup[$gKey] ?? collect();

                    $groupNode = [
                        'key' => "grp-$gKey",
                        'title' => $g->Name,
                        'type' => 'group',
                        'children' => $paramColl->map(fn($p) => [
                            'key' => "prm-{$p->id}",
                            'title' => $p->parameter->Name ?? "Param #{$p->id}",
                            'ParameterID' => (string) $p->ParametersID,
                            'type' => 'param',
                        ])->values(),
                    ];

                    $pageNode['children'][] = $groupNode;
                }

                $sexNode['children'][] = $pageNode;
            }

            return $sexNode;
        })->values();

        return response()->json([
            'tree' => $tree,
            'selected' => $selected,
        ]);
    }
    public function getStructures(): JsonResponse
    {
        $structures = FactoryStructure::select('id', 'Name')->orderBy('id')->get();

        return response()->json($structures);
    }
    public function getPages(int $sexId): JsonResponse
    {
        $pages = NumberPage::where('StructureID', $sexId)
            ->select('NumberPage as id', 'Name')
            ->orderBy('NumberPage')
            ->get();

        return response()->json($pages);
    }
    public function getGroups(int $sexId, int $pageId): JsonResponse
    {
        $groups = Groups::where('StructureID', $sexId)
            ->where('PageID', $pageId)
            ->select('id', 'Name')
            ->orderBy('id')
            ->get();

        return response()->json($groups);
    }
    public function getParameters(int $sexId, int $pageId, int $groupId): JsonResponse
    {
        $params = GraphicsParamenters::with('parameter:id,Name')
            ->where('FactoryStructureID', $sexId)
            ->where('PageId', $pageId)
            ->where('GroupID', $groupId)
            ->select('ParametersID', 'id')
            ->get()
            ->map(fn($p) => [
                'ParameterID' => (string) $p->ParametersID,
                'title' => $p->parameter->Name ?? "Param #{$p->id}"
            ]);

        return response()->json($params);
    }










    // private function getRowUnit($id)
    // {
    //     $unit = DocumentNumberPage::where('IdNumberPage',$id)->get();
    //     return response()->json($unit);
    // }
private function create(Request $request)
{
    $docId = $request->input('doc_id');

    $unit = DocumentNumberPage::updateOrCreate(
        ['IdNumberPage' => $docId], // mavjud bo‘lsa yangilaydi
        [
            'IdBlog'             => $request->IdBlog,
            'Name'               => $request->Name,
            'NameRus'            => $request->NameRus,
            'Comment'            => $request->Comment,
            'FactoryStructureID' => json_encode($request->FactoryStructureID ?? []),
            'NumberPageBlogs'    => json_encode($request->NumberPageBlogs ?? []),
            'GroupBlogs'         => json_encode($request->GroupBlogs ?? []),
            'ParameterBlogs'     => json_encode($request->ParameterBlogs ?? []),
        ]
    );

    return response()->json([
        'status' => 200,
        'message' => 'Maʼlumot yangilandi yoki yaratildi',
        'unit' => $unit
    ]);
}


    private function update(Request $request)
    {
        // $request->validate([
        //     'id' => 'required|integer|exists:units,id',
        //     'Name' => 'required|string|max:255',
        //     'NameRus' => 'required|string|max:255',
        //     'ShortName' => 'required|string|max:255',
        //     'ShortNameRus' => 'required|string|max:255',
        //     'Comment' => 'nullable|string|max:255',
        // ]);

        // $unit = DocumentNumberPage::find($request->id);
        // $unit->update([
        //     'Name' => $request->Name,
        //     'NameRus' => $request->NameRus,
        //     'ShortName' => $request->ShortName,
        //     'ShortNameRus' => $request->ShortNameRus,
        //     'Comment' => $request->Comment,
        // ]);

        // return response()->json([
        //     'status' => 200,
        //     'message' => "Javob muvafaqiyatli yangilandi",
        //     'unit' => $unit
        // ]);
    }

    public function delete(Request $request, $id)
    {
        try {
            $unit = DocumentNumberPage::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
