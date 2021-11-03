<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function showUserInfo()
    {
        $user = User::with('image')->find(JWTAuth::user()->id);
        $response = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'phone' => $user->phone,
            'description' => $user->description,
            'skills' => $user->skills,
            'work_hour_start' => $user->work_hour_start,
            'work_hour_end' => $user->work_hour_end,
            'avatar' => $user->image->src
        ];
        return response()->json($response, 200);
    }

    public function showUserInfoById($id)
    {
        $user = User::with('image')->find($id);
        $response = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'phone' => $user->phone,
            'description' => $user->description,
            'skills' => $user->skills,
            'work_hour_start' => $user->work_hour_start,
            'work_hour_end' => $user->work_hour_end,
            'avatar' => $user->image->src
        ];
        return response()->json($response, 200);
    }

    public function showUserNotifications()
    {
        return response()->json(User::find(JWTAuth::user()->id)->notifications, 200);
    }

    public function markNotificationAsRead($id)
    {
        $notification = Notification::find($id);

        if ($notification->user_id != JWTAuth::user()->id) {
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch - Unauthorized'], 401);
        }

        $notification->update([
            'is_read' => true
        ]);

        return response()->json($notification, 200);
    }

    public function markNotificationsAsRead()
    {
        $notifications = JWTAuth::user()->notifications;

        foreach ($notifications as $notification) {
            $notification->update([
                'is_read' => true
            ]);
        }

        return response()->json($notifications, 200);
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8|same:password',
        ]);
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(true, 201);
    }

    public function update(Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'name' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'required|min:8',
            'new_password' => 'min:8',
            'new_password_confirmation' => 'min:8|same:password',
            'phone' => [
                'required',
                'numeric',
                'digits_between:10,13',
                Rule::unique('users')->ignore($user->id)
            ],
            'image' => 'image'
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Incorrect password'], 401);
        }
        $password = $request->new_password ? $request->new_password : $request->password;

        $status = $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'phone' => $request->phone,
            'description' => $request->description,
            'skills' => $request->skills,
            'work_hour_start' => $request->work_hour_start,
            'work_hour_end' => $request->work_hour_end
        ]);

        if ($request->file('image')) {
            if ($user->image) {
                $user->image->user()->dissociate();
                $user->image->save();
            }

            $filename = $user->id . '.' . $request->file('image')->extension();
            $request->file('image')->move('avatar', $filename);
            $user->image()->save(
                new Image(['src' => 'avatar/' . $filename])
            );
        }

        return response()->json($status, 200);
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
            return response()->json(['status' => 'error', 'message' => 'You must fill all the fields'], 400);
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

        return response()->json(['message' => 'Successfully logged out'], 200);
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
