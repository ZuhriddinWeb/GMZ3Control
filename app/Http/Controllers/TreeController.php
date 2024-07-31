<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FactoryStructure;
use App\Models\ValuesParameters;
use Carbon\Carbon; // Import the Carbon class

class TreeController extends Controller
{
    public function getTree()
    {
        $structures = FactoryStructure::with('blogs')->get();

        $tree = $structures->map(function($structure) {
            return [
                'id' => $structure->id,
                'label' => $structure->Name,
                'icon' => 'some_icon', 
                'children' => $structure->blogs->map(function($blog) {
                    return [
                        'id' => $blog->id,
                        'label' => $blog->Name
                    ];
                })->toArray()
            ];
        });

        return response()->json($tree);
    }
    public function treeChart(Request $request)
    {
        $startDate = Carbon::parse($request->start)->startOfDay();
        $endDate = Carbon::parse($request->end)->endOfDay();

        $formattedStartDate = $startDate->format('Y-m-d H:i:s');
        $formattedEndDate = $endDate->format('Y-m-d H:i:s');

        $results = ValuesParameters::where('ParametersID','=', $request->id)->get();
        // ->whereBetween('updated_at', [$formattedStartDate, $formattedEndDate])->get();

        return response()->json([
            'status' => 200,
            'data' => $results
        ]);
    }
    public function handleNodeClick(Request $request)
    {
        $nodeId = $request->input('id');
        // Perform your query or logic here using the $nodeId
        $blog = Blog::find($nodeId);

        return response()->json($blog);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
