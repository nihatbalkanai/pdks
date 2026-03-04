<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BildirimGonderJob implements ShouldQueue
{
    use Queueable;

    public $firmaId;
    public $kanal;
    public $alici;
    public $mesaj;

    /**
     * Create a new job instance.
     */
    public function __construct($firmaId, $kanal, $alici, $mesaj)
    {
        $this->firmaId = $firmaId;
        $this->kanal = $kanal;
        $this->alici = $alici;
        $this->mesaj = $mesaj;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bildirimServisi = new \App\Services\BildirimServisi();
        $bildirimServisi->gonder($this->firmaId, $this->kanal, $this->alici, $this->mesaj);
    }
}
