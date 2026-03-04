<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestOpenai extends Command
{
    protected $signature   = 'test:openai';
    protected $description = 'Test OpenAI API connection';

    public function handle()
    {
        $apiKey = config('openai.api_key');
        $this->info('Key length: ' . strlen($apiKey));
        $this->info('Key start: ' . substr($apiKey, 0, 20) . '...' . substr($apiKey, -10));

        // Modeller listesi - ücretsiz, basit istek
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . trim($apiKey),
        ])->timeout(15)->get('https://api.openai.com/v1/models');

        $this->info('HTTP Status: ' . $response->status());

        if ($response->failed()) {
            $this->error('Error: ' . $response->body());
            return 1;
        }

        $models = $response->json('data', []);
        $gpt4mini = collect($models)->firstWhere('id', 'gpt-4o-mini');
        $this->info('Models count: ' . count($models));
        $this->info('gpt-4o-mini available: ' . ($gpt4mini ? 'YES' : 'NO'));

        // Asıl çağrıyı test et (chat completion)
        $this->info("\nTesting chat completion...");
        $resp2 = Http::withHeaders([
            'Authorization' => 'Bearer ' . trim($apiKey),
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => 'gpt-4o-mini',
            'messages'    => [['role' => 'user', 'content' => 'Merhaba, test. Sadece "OK" yaz.']],
            'max_tokens'  => 5,
            'temperature' => 0,
        ]);

        $this->info('Chat HTTP: ' . $resp2->status());
        if ($resp2->failed()) {
            $this->error('Chat Error: ' . $resp2->body());
            return 1;
        }

        $content = $resp2->json('choices.0.message.content', '');
        $this->info('Chat Response: ' . $content);
        $this->info("\n✅ OpenAI API çalışıyor!");
        return 0;
    }
}
