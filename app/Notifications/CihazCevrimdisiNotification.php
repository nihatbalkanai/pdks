<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CihazCevrimdisiNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $cihazSeriNo;

    /**
     * @param string $cihazSeriNo
     */
    public function __construct(string $cihazSeriNo)
    {
        $this->cihazSeriNo = $cihazSeriNo;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tur' => 'uyari',
            'mesaj' => "Dikkat! '{$this->cihazSeriNo}' seri numaralı cihazınız son 10 dakikadır yanıt vermiyor (Çevrimdışı).",
            'link' => null,
            'zaman' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'tur' => 'uyari',
            'mesaj' => "Dikkat! '{$this->cihazSeriNo}' seri numaralı cihazınız son 10 dakikadır yanıt vermiyor (Çevrimdışı).",
            'link' => null,
            'zaman' => now()->toIso8601String(),
        ]);
    }
}
