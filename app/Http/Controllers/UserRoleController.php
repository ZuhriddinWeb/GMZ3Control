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
            return $this->getRowUserRole($id);
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
    private function getRowUserRole($id)
    {
        $user = UserRole::with('role')->where('user_id', $id)->get();
        return response()->json($user);
    }
    private function create(Request $request)
    {
        
        foreach ($request['roles'] as $role) {
            $roles = UserRole::updateOrCreate(
                [
                    'user_id' => $request['id'],
                    'role_id' => $role['id'],
                ],
                [
                    'view' => $role['view'],
                    'create' => $role['create'],
                    'update' => $role['edit'],
                    'delete' => $role['delete'],
                ]
            );
        }
    

        return response()->json([
            'status' => 200,
            'message' => "Foydalanuvchi muvafaqiyatli qo'shildi",
            'unit' => $roles
        ]);
    }

    private function update(Request $request)
    {
        foreach ($request['roles'] as $role) {
            $roles = UserRole::create([
                'user_id' => $request['id'],
                'role_id' => $role['id'],
                'view' => $role['view'],
                'create' => $role['create'],
                'update' => $role['edit'],
                'delete' => $role['delete'],
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => "Foydalanuvchi muvafaqiyatli qo'shildi",
            'unit' => $roles
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
