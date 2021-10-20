<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function showAllUsers()
    {
        return response()->json(User::all());
    }

    public function showUserById($id)
    {
        return response()->json(User::find($id));
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8|same:password',
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request)
    {
        $user = JWTAuth::user();
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'new_password' => 'min:8',
            'new_password_confirmation' => 'min:8|same:password',
            'phone' => 'required|unique:users|numeric|digits_between:10,13'
        ]);

        if (!Hash::check($user->password, $request->password)) {
            return response()->json(['status' => 'error', 'message' => 'Incorrect password']);
        }
        $password = $request->new_password ? $request->new_password : $request->password;
        $request->file('image')->move(storage_path('avatar'), $user->id);

        $user = User::findOrFail($user->id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'phone' => $request->phone,
            'description' => $request->description,
            'skills' => $request->skills,
            'image' => storage_path('avatar') . '/' . $user->id,
            'work_hour_start' => $request->work_hour_start,
            'work_hour_end' => $request->work_hour_end
        ]);

        return response()->json($user, 200);
    }

    public function delete()
    {
        $id = JWTAuth::user()->id;
        $user = User::findOrFail($id)->delete();

        return response()->json($user, 200);
    }

    public function login(Request $request)
    {
        $email = $request->email;
        $password = Hash::make($request->password);

        // Check if field is empty
        if (empty($email) || empty($password)) {
            return response()->json(['status' => 'error', 'message' => 'You must fill all the fields']);
        }

        $credentials = request(['email', 'password']);

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }
    
    public function logout()
    {
        JWTAuth::logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }
}
