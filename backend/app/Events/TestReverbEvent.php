<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestReverbEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $message = 'Reverb works!'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('test'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'test-event';
    }
}
