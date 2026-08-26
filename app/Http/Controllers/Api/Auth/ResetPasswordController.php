<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * POST /auth/reset-password — spends the code from ForgotPasswordController. Single-use:
 * the password_reset_tokens row is deleted on both success and (once found) failure, so a
 * wrong code can't be retried against the same request indefinitely, and a correct one
 * can't be replayed after it's been used.
 *
 * Also issues a fresh DeviceToken on success, same as LoginController — proving you know
 * the code that just arrived in your inbox is exactly as much proof of identity as knowing
 * the password would be, no reason to make the user log in again right after.
 */
class ResetPasswordController extends Controller
{
    private const CODE_TTL_MINUTES = 30;

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $email = $request->string('email')->toString();
        $code = $request->string('code')->toString();
        $password = $request->string('password')->toString();

        /** @var object{email: string, token: string, created_at: string}|null $row */
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($row === null) {
            return response()->json(['error' => 'invalid_or_expired_code'], 400);
        }

        // diffInMinutes is signed (negative for a past date) in this Carbon version —
        // comparing isPast() on an explicit expiry instant avoids relying on its sign.
        $expired = Carbon::parse($row->created_at)->addMinutes(self::CODE_TTL_MINUTES)->isPast();
        $codeMatches = Hash::check($code, $row->token);

        if ($expired || ! $codeMatches) {
            if ($expired) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
            }

            return response()->json(['error' => 'invalid_or_expired_code'], 400);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null || $user->uuid === null) {
            return response()->json(['error' => 'invalid_or_expired_code'], 400);
        }

        $user->password = Hash::make($password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $deviceToken = DeviceToken::issueFor($user->uuid);

        return response()->json([
            'uuid' => $user->uuid,
            'device_token' => $deviceToken->token,
        ]);
    }
}
