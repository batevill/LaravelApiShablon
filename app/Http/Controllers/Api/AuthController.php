<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Tokenning amal qilish vaqti (kunlarda)
    private const EXPIRE_TIME_TOKEN = 6;

    // Refresh tokenning amal qilish vaqti (kunlarda)
    private const EXPIRE_TIME_REFRESH_TOKEN = 10;

    // Token turi
    private const AUTH_TYPE = 'Bearer';


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->get('email'))->first();

        if (!$user || !Hash::check($request->get('password'), $user->password)) {
            return response()->json([
                'message' => 'Неверные учетные данные',
                'data' => [],
            ], 401);
        }


        $expiresAt = Carbon::now()->addDays(self::EXPIRE_TIME_TOKEN);

        $expiresAted = Carbon::now()->addDays(self::EXPIRE_TIME_REFRESH_TOKEN);

        $rand_token_name = Str::random(4);

        $token = $user->createToken(
            $rand_token_name,
            ['*'],
            $expiresAt
        )->plainTextToken;

        // Generate refresh token
        $refresh_token = Str::random(48);

        if ($rand_token_name) {
            DB::table('personal_access_tokens')
                ->where('name', $rand_token_name)
                ->update([
                    'refresh_token' => $refresh_token,
                    'refresh_token_expires_at' => $expiresAted,
                ]);
        }

        return response()->json([
            'message' => 'Успешный вход',
            'data' => [
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'refresh_token' => $refresh_token,
                    'type' => self::AUTH_TYPE,
                    'expireIn' => $expiresAt->diffInSeconds(Carbon::now()),
                    'refresh_token_expires_in' => $expiresAted->diffInSeconds(Carbon::now()),
                ],
            ],
        ]);
    }


    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required'
        ]);

        $refreshToken = $request->input('refresh_token');

        $tokenData = DB::table('personal_access_tokens')
            ->where('refresh_token', $refreshToken)
            ->where('refresh_token_expires_at', '>', Carbon::now())
            ->first();

        if (!$tokenData) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $user = User::find($tokenData->tokenable_id);

        $expiresAt = Carbon::now()->addDays(self::EXPIRE_TIME_TOKEN);
        $token = $user->createToken('access_token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => self::AUTH_TYPE,
            'expires_in' => $expiresAt->diffInSeconds(Carbon::now())
        ]);
    }
}
