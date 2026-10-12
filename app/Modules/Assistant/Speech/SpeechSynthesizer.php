<?php

namespace App\Modules\Assistant\Speech;

use App\Modules\Assistant\Gateway\GatewayException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Gives the barista a voice through OpenRouter's OpenAI-compatible
 * /audio/speech endpoint. Gemini voices only answer with raw 16-bit PCM, which
 * a browser cannot play, so it is wrapped into a WAV file here. The WAV is kept
 * on disk by text so a line that is spoken twice is only paid for once.
 */
class SpeechSynthesizer
{
    public const MIME_TYPE = 'audio/wav';

    private const TIMEOUT_SECONDS = 30;

    private const DEFAULT_SAMPLE_RATE = 24000;

    private const DEFAULT_CHANNELS = 1;

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
                'response_format' => 'pcm',
            ]));

        if ($response->failed() || $response->body() === '') {
            throw new GatewayException("Speech request failed: HTTP {$response->status()} — {$response->body()}");
        }

        $contentType = (string) $response->header('Content-Type');
        $sampleRate = preg_match('/rate=(\d+)/', $contentType, $rate) ? (int) $rate[1] : self::DEFAULT_SAMPLE_RATE;
        $channels = preg_match('/channels=(\d+)/', $contentType, $count) ? (int) $count[1] : self::DEFAULT_CHANNELS;

        $audio = $this->wavFromPcm($response->body(), $sampleRate, $channels);

        Storage::disk('local')->put($path, $audio);

        return $audio;
    }

    /**
     * Prepends the 44-byte RIFF header that describes 16-bit little-endian PCM.
     */
    private function wavFromPcm(string $pcm, int $sampleRate, int $channels): string
    {
        $bytesPerFrame = $channels * 2;

        return 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVEfmt '
            .pack('VvvVVvv', 16, 1, $channels, $sampleRate, $sampleRate * $bytesPerFrame, $bytesPerFrame, 16)
            .'data'.pack('V', strlen($pcm)).$pcm;
    }

    private function cachePath(string $text): string
    {
        $fingerprint = sha1(config('services.openrouter.tts_model').'|'.config('services.openrouter.tts_voice').'|'.$text);

        return "speech/{$fingerprint}.wav";
    }
}
