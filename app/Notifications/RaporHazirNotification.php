<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class RaporHazirNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $dosyaYolu;

    /**
     * @param string $dosyaYolu
     */
    public function __construct(string $dosyaYolu)
    {
        $this->dosyaYolu = $dosyaYolu;
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
     * Veritabanına (notifications tablosuna) json olarak yazılacak.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'tur' => 'rapor',
            'mesaj' => 'Excel raporunuz başarıyla oluşturuldu.',
            'link' => $this->dosyaYolu,
            'zaman' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'tur' => 'rapor',
            'mesaj' => 'Excel raporunuz başarıyla oluşturuldu.',
            'link' => $this->dosyaYolu,
            'zaman' => now()->toIso8601String(),
        ]);
    }
}
