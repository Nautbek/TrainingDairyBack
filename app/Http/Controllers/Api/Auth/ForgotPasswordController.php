<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordCodeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * POST /auth/forgot-password — sends a 6-digit code by email (see
 * ResetPasswordCodeNotification), not a link: this app has no web page for a link to land
 * on. Always answers the same generic 200 whether or not the email matched an account —
 * a different response would let someone enumerate which emails are registered here.
 *
 * Reuses the stock `password_reset_tokens` table (email primary key, hashed token,
 * created_at) that's been sitting unused in the schema since the first migration — same
 * shape Laravel's own Password broker expects, just written to directly instead of going
 * through the broker (that class assumes a web link flow, not a mobile numeric code).
 */
class ForgotPasswordController extends Controller
{
    private const CODE_TTL_MINUTES = 30;

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $user = User::query()->where('email', $email)->first();

        if ($user !== null && $user->hasEmailLogin()) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($code), 'created_at' => now()],
            );

            $user->notify(new ResetPasswordCodeNotification($code));
        }

        return response()->json([
            'message' => 'Если такой email зарегистрирован, код отправлен на почту.',
        ]);
    }
}
