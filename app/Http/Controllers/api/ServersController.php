<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Servers;
use Illuminate\Http\Request;

class ServersController extends Controller
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
        $servers = Servers::all();
        return response()->json($servers);
    }
    private function getRowUnit($id)
    {
        $servers = Servers::find($id);
        return response()->json($servers);
    }
    private function create(Request $request)
    {
        
    }

    private function update(Request $request)
    {
        
    }

    public function delete(Request $request, $id)
    {
       
    }
}
