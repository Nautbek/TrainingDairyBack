<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterWithEmailRequest;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * POST /auth/register — email+password identity, replacing the bare-uuid one from
 * RegisterController. Two shapes, depending on whether the client already has a local uuid:
 *
 * - No `uuid` (brand-new install, mandatory sign-up screen): creates a fresh uuid + real
 *   email/password in one row, same as RegisterController but with real credentials from
 *   the start instead of {uuid}@temp.local placeholders.
 * - `uuid` given (dismissible "attach email" banner on an existing install): replaces that
 *   account's placeholder email/password with the real ones. Doesn't create a second
 *   identity — the device keeps the uuid it already has locally.
 *
 * Either way, issues a DeviceToken — the auth proof this step introduces for whatever next
 * uses it (the cross-device pull endpoint is currently unrouted; re-enabling it behind this
 * token is a later step in the "Аккаунт по email" plan, not this one).
 */
class RegisterWithEmailController extends Controller
{
    public function __invoke(RegisterWithEmailRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! empty($validated['uuid'])) {
            $user = User::query()->where('uuid', $validated['uuid'])->first();

            if ($user === null) {
                return response()->json(['error' => 'not_found'], 404);
            }

            if ($user->hasEmailLogin()) {
                return response()->json(['error' => 'already_registered'], 409);
            }

            $user->email = $validated['email'];
            $user->password = Hash::make($validated['password']);
            $user->save();

            $uuid = $user->uuid;
        } else {
            do {
                $uuid = (string) Str::uuid();
            } while (User::query()->where('uuid', $uuid)->exists());

            DB::table('users')->insert([
                'uuid' => $uuid,
                'name' => 'user_'.substr($uuid, 0, 8),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
        }

        $deviceToken = DeviceToken::issueFor($uuid);

        return response()->json([
            'uuid' => $uuid,
            'device_token' => $deviceToken->token,
        ], 201);
    }
}
