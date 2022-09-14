<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\API\Auth\LoginRequest;
use App\Http\Requests\API\Auth\LogoutRequest;
use App\Http\Requests\API\Auth\RegisterRequest;
use App\Models\Loan;

class AuthController extends Controller
{
    private $token = '';

    public function __construct()
    {
        $this->token = config('auth.sanctum_token');
    }

    public function register(RegisterRequest $request, User $user)
    {
        try {
            // get validated request data
            $requestData = $request->validated();

            $user = $user->create([
                'uuid' => Str::orderedUuid(),
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'user' => $user,
                'token' => $user->createToken($this->token)->plainTextToken
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'user' => $user,
                'token' => $user->createToken($this->token)->plainTextToken
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        if (auth()->user()->tokens()->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'User Logged Out Successfully'
            ], 200);
        }
    }
}
