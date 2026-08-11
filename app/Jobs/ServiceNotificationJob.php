<?php

namespace App\Jobs;

use App\Services\TelegramNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/**
 * Delivers a single service (Telegram) notification in the background.
 *
 * Dispatched by TelegramNotificationService::sendMessage() — the caller
 * (feedback, donation/mycar/tripsplit payment flows) never waits on the
 * actual network call to Telegram, so an outage there can't slow down or
 * fail a user-facing API request.
 *
 * Retries up to 5 times, 15 minutes apart, before landing in
 * `failed_jobs` — matched to how long Telegram connectivity issues from
 * this host have historically lasted (see storage/logs/laravel.log).
 */
class ServiceNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** Seconds between retry attempts. */
    public int $backoff = 900;

    public function __construct(public readonly string $message)
    {
        // Dedicated queue so a burst of notifications never competes with
        // other (currently none, but future) queued work.
        $this->onQueue('service-notifications');
    }

    public function handle(TelegramNotificationService $telegramNotificationService): void
    {
        if (! $telegramNotificationService->deliver($this->message)) {
            // Throwing (rather than just returning) is what makes the queue
            // worker actually retry the job per $tries/$backoff above.
            throw new RuntimeException('Telegram delivery failed, will retry');
        }
    }
}
