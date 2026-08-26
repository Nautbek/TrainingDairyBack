<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Issued to a device on successful register-with-email or login — proof that this
 * specific device authenticated, as opposed to just knowing a uuid (which isn't a secret,
 * it's shown in-app). Not yet consumed by anything (the one endpoint that would use it,
 * the cross-device history pull, is currently unrouted — see
 * Modules/TrainingDiary/routes/api.php); this is the auth primitive step 1 of the "Аккаунт
 * по email" plan needs so that step can wire it in without another migration.
 *
 * @property int $id
 * @property string $user_uuid
 * @property string $token
 */
class DeviceToken extends Model
{
    protected $fillable = [
        'user_uuid',
        'token',
    ];

    public static function issueFor(string $userUuid): self
    {
        do {
            $token = Str::random(64);
        } while (self::query()->where('token', $token)->exists());

        return self::query()->create([
            'user_uuid' => $userUuid,
            'token' => $token,
        ]);
    }
}
