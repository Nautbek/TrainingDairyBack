<?php

namespace App\Models;

use App\Enums\Donation\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $user_uuid
 * @property string|null $app
 * @property string|null $yookassa_payment_id
 * @property int $amount
 * @property int $months
 * @property PaymentStatus $status
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DonationPayment extends Model
{
    protected $fillable = [
        'uuid',
        'user_uuid',
        'app',
        'yookassa_payment_id',
        'amount',
        'months',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'months' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    /**
     * @param  array<string, mixed>  $paymentObject
     */
    public static function resolveFromYooKassaObject(array $paymentObject): ?self
    {
        $yookassaPaymentId = $paymentObject['id'] ?? null;
        $metadata = $paymentObject['metadata'] ?? [];
        $paymentUuid = is_array($metadata) ? ($metadata['donation_payment_uuid'] ?? null) : null;

        if (is_string($yookassaPaymentId)) {
            $payment = static::query()->where('yookassa_payment_id', $yookassaPaymentId)->first();
            if ($payment !== null) {
                return $payment;
            }
        }

        if (is_string($paymentUuid)) {
            return static::query()->where('uuid', $paymentUuid)->first();
        }

        return null;
    }
}
