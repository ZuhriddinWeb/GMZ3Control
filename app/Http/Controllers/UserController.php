<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Validator;
use Auth;
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
                return $this->update($request, $id);
            case 'DELETE':
                return $this->delete($request, $id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }

    private function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['login' => 'required', 'password' => 'required']);

        if (!$this->guard()->attempt($credentials)) {
            return response()->json(['message' => 'Parol yoki login xato!'], 299);
        }

        $user = $this->guard()->user();

        $token = $user->createToken('token-name', ['server:update'])->plainTextToken;
        return response()->json(['token' => $token, 'type' => 'Bearer'], 200);
    }

    public function logoutUser(Request $request)
    {
        $user = $request->user();

        return $user->tokens()->where([
            ['tokenable_id', $user->id],
            ['id', $request->id],
        ])->delete();
    }

    public function guard($guard = 'web')
    {
        return Auth::guard($guard);
    }

    public function authenticatedUser(Request $request)
    {
        $user = $request->user()->load('roles');
    
        $seanses = $user->tokens()->where('tokenable_id', $user->id)->get();
    
        foreach ($seanses as $key => $value) {
            $value->updated = Carbon::parse($value->updated_at)->translatedFormat('d.m.Y');
        }
    
        $active = $user->tokens()->where('id', $user->currentAccessToken()->id)->first();
        $active->abilities = [$user->isActive];
        $active->save();
    
        $active->updated = Carbon::parse($active->updated_at)->translatedFormat('d.m.Y');
        $user->active = $active;
        $user->seanses = $seanses;
    
        return response()->json($user);
    }
    

    private function getRowUnit($id)
    {
        $user = User::find($id);
        return response()->json($user);
    }

    private function create(Request $request)
    {
        dd($request);
        $request->validate([
            'Name' => 'required|string|max:255',
            'Login' => 'required|string|max:255',
            'Password' => 'required|min:6|max:255',
            // 'structure_id'=>'required|string'
        ]);

        $unit = User::create([
            'name' => $request->Name,
            'phone' => $request->Phone,
            'login' => $request->Login,
            'password' => Hash::make($request->Password),
            'structure_id' => $request->structure_id,

        ]);

        return response()->json([
            'status' => 200,
            'message' => "Foydalanuvchi muvafaqiyatli qo'shildi",
            'unit' => $unit,
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
            'unit' => $unit,
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
