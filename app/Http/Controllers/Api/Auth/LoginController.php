<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * POST /auth/login — how a device recovers its identity after being lost/reinstalled: log
 * in with the email+password attached earlier (RegisterWithEmailController), get back the
 * same uuid plus a fresh DeviceToken. Route carries throttle:5,1 (see routes/api.php) —
 * password auth is the first thing in this app actually worth brute-forcing.
 *
 * Same 401 for "no such email" and "wrong password" — telling them apart lets an attacker
 * enumerate which emails have accounts here.
 */
class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! $user->hasEmailLogin() || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['error' => 'invalid_credentials'], 401);
        }

        $deviceToken = DeviceToken::issueFor($user->uuid);

        return response()->json([
            'uuid' => $user->uuid,
            'device_token' => $deviceToken->token,
        ]);
    }
}
