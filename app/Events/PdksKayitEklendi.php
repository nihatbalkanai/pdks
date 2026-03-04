<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PdksKayitEklendi implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $firma_id;
    public $kayit;

    /**
     * PdksKayit modeli veya array yollanabilir
     */
    public function __construct($firma_id, $kayit)
    {
        $this->firma_id = $firma_id;
        $this->kayit = $kayit;
    }

    /**
     * WebSocket için dinlenecek kanal ismi
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('firma.' . $this->firma_id),
        ];
    }

    /**
     * Yayınlanacak event'in ismi
     */
    public function broadcastAs(): string
    {
        return 'pdks.kayit.eklendi';
    }
}
