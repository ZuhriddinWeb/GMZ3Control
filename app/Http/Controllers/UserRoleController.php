<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserRole;
use Auth;
use Hash;
class UserRoleController extends Controller
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
        $user = UserRole::all();
        return response()->json($user);
    }
    private function getRowUnit($id)
    {
        $user = UserRole::find($id);
        return response()->json($user);
    }
    private function create(Request $request)
    {
        $request->validate([
            'Login' => 'required|string|max:255',
            'password' => 'required|min:6|max:255|confirmed',
        ]);
        if($request->fails()){
            return response()->json($request->errors(),299);
        }
        $unit = UserRole::create([
            'Login' => $request->Login,
            'Password' =>  Hash::make($request->Password),
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

        $unit = UserRole::find($request->id);
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
            $unit = UserRole::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }
}
