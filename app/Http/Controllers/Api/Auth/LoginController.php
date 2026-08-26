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
        $email = $request->string('email')->toString();
        $password = $request->string('password')->toString();

        $user = User::query()->where('email', $email)->first();

        // uuid is nullable at the column level (pre-dates this app's uuid scheme) — every
        // account created through our own controllers always has one, but a user row that
        // somehow doesn't must not be allowed to log in and mint a token for a null identity.
        if (
            $user === null
            || $user->uuid === null
            || ! $user->hasEmailLogin()
            || ! Hash::check($password, $user->password)
        ) {
            return response()->json(['error' => 'invalid_credentials'], 401);
        }

        $deviceToken = DeviceToken::issueFor($user->uuid);

        return response()->json([
            'uuid' => $user->uuid,
            'device_token' => $deviceToken->token,
        ]);
    }
}
