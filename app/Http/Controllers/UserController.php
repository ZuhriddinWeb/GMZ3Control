<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Hash;

class UserController extends Controller
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
        $user = User::all();
        return response()->json($user);
    }
    private function getRowUnit($id)
    {
        $user = User::find($id);
        return response()->json($user);
    }
    private function create(Request $request)
    {
        // dd($request);
        $request->validate([
            'Name'=> 'required|string|max:255',
            'Login' => 'required|string|max:255',
            'Password' => 'required|min:6|max:255',
        ]);
        
        $unit = User::create([
            'name'=>$request->Name,
            'phone'=>$request->Phone,
            'login' => $request->Login,
            'password' =>  Hash::make($request->Password),
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Foydalanuvchi muvafaqiyatli qo'shildi",
            'unit' => $unit
        ]);
    }

    private function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:units,id',
            'Name' => 'required|string|max:255',
            'ShortName' => 'required|string|max:255',
            'Comment' => 'nullable|string|max:255',
        ]);

        $unit = User::find($request->id);
        $unit->update([
            'Name' => $request->Name,
            'ShortName' => $request->ShortName,
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
            $unit = User::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
