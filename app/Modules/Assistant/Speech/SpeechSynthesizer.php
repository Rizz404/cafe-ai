<?php

namespace App\Modules\Assistant\Speech;

use App\Modules\Assistant\Gateway\GatewayException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Gives the barista a voice through OpenRouter's OpenAI-compatible
 * /audio/speech endpoint. The answer is raw MP3 bytes, kept on disk by text so
 * a line that is spoken twice is only paid for once.
 */
class SpeechSynthesizer
{
    private const TIMEOUT_SECONDS = 30;

    public function available(): bool
    {
        return filled(config('services.openrouter.api_key')) && filled(config('services.openrouter.tts_model'));
    }

    /**
     * @throws GatewayException
     */
    public function synthesize(string $text): string
    {
        $path = $this->cachePath($text);

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->get($path);
        }

        $response = Http::withToken(config('services.openrouter.api_key'))
            ->timeout(self::TIMEOUT_SECONDS)
            ->post(rtrim(config('services.openrouter.base_url'), '/').'/audio/speech', array_filter([
                'model' => config('services.openrouter.tts_model'),
                'voice' => config('services.openrouter.tts_voice'),
                'input' => $text,
                'response_format' => 'mp3',
            ]));

        if ($response->failed() || $response->body() === '') {
            throw new GatewayException("Speech request failed: HTTP {$response->status()}");
        }

        Storage::disk('local')->put($path, $response->body());

        return $response->body();
    }

    private function cachePath(string $text): string
    {
        $fingerprint = sha1(config('services.openrouter.tts_model').'|'.config('services.openrouter.tts_voice').'|'.$text);

        return "speech/{$fingerprint}.mp3";
    }
}
