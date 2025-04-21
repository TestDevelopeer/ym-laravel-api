<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class YandexMusicTrackUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trackData;

    public function __construct($trackData)
    {
        $this->trackData = $trackData;
    }

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('yandex-music'); // Или new Channel для публичного
    }

    public function broadcastAs(): string
    {
        return 'track-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'track' => $this->trackData['track'],
            'paused' => $this->trackData['paused'],
            'progress_ms' => $this->trackData['progress_ms'],
            'duration_ms' => $this->trackData['duration_ms'],
            'updated_at' => $this->trackData['updated_at']
        ];
    }
}
