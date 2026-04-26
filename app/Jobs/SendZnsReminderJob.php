<?php

namespace App\Jobs;

use App\Models\MemorialEvent;
use App\Models\Recipient;
use App\Services\ZnsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendZnsReminderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly MemorialEvent $event,
        public readonly Recipient $recipient,
    ) {}

    public function handle(ZnsService $znsService): void
    {
        $znsService->send($this->event, $this->recipient);
    }
}
