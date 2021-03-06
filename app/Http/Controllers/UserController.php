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
        $user->avatar = $user->image->src;
        unset($user->password, $user->image);
        return response()->json($user, 200);
    }

    public function showUserInfoById($id)
    {
        $user = User::with('image')->find($id);
        $user->avatar = $user->image->src;
        unset($user->password, $user->image);
        return response()->json($user, 200);
    }

    public function showUserNotifications()
    {
        return response()->json(User::with('notifications')->find(JWTAuth::user()->id)->notifications, 200);
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
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $login_request = new Request();
        $login_request->setMethod('POST');
        $login_request->request->add([
            'email' => $user->email,
            'password' => $user->password
        ]);

        return $this->login($login_request);
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
            'university' => $request->university,
            'major' => $request->major,
            'gender' => $request->gender,
            'work_hour_start' => $request->work_hour_start,
            'work_hour_end' => $request->work_hour_end,
            'address' => $request->address,
            'linkedin' => $request->linkedin,
            'instagram' => $request->instagram,
            'github' => $request->github
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
        auth()->logout();

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
