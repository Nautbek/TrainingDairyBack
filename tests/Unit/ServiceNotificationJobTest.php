<?php

namespace Tests\Unit;

use App\Jobs\ServiceNotificationJob;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ServiceNotificationJobTest extends TestCase
{
    public function test_send_message_queues_a_job_instead_of_calling_telegram_directly(): void
    {
        Queue::fake();

        config(['services.telegram.api_url' => 'https://api.telegram.org/botTEST/sendMessage']);
        config(['services.telegram.chat_id' => 123]);

        $service = $this->app->make(TelegramNotificationService::class);

        $result = $service->sendMessage('hello');

        $this->assertTrue($result);
        Queue::assertPushed(ServiceNotificationJob::class, function (ServiceNotificationJob $job): bool {
            return $job->message === 'hello' && $job->queue === 'service-notifications';
        });
    }

    public function test_send_message_does_not_queue_when_telegram_is_not_configured(): void
    {
        Queue::fake();

        config(['services.telegram.api_url' => '']);
        config(['services.telegram.chat_id' => 0]);

        $service = $this->app->make(TelegramNotificationService::class);

        $this->assertFalse($service->sendMessage('hello'));
        Queue::assertNotPushed(ServiceNotificationJob::class);
    }

    public function test_job_has_five_tries_with_fifteen_minute_backoff(): void
    {
        $job = new ServiceNotificationJob('hello');

        $this->assertSame(5, $job->tries);
        $this->assertSame(900, $job->backoff);
    }

    public function test_job_throws_to_trigger_a_retry_when_delivery_fails(): void
    {
        $telegram = Mockery::mock(TelegramNotificationService::class);
        $telegram->shouldReceive('deliver')->once()->with('hello')->andReturn(false);

        $job = new ServiceNotificationJob('hello');

        $this->expectException(\RuntimeException::class);

        $job->handle($telegram);
    }

    public function test_job_does_not_throw_when_delivery_succeeds(): void
    {
        $telegram = Mockery::mock(TelegramNotificationService::class);
        $telegram->shouldReceive('deliver')->once()->with('hello')->andReturn(true);

        $job = new ServiceNotificationJob('hello');

        $job->handle($telegram);

        $this->assertTrue(true);
    }
}
