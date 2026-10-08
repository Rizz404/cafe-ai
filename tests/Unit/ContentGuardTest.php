<?php

namespace Tests\Unit;

use App\Modules\Conversation\Support\ContentGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContentGuardTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function offensiveMessages(): array
    {
        return [
            'insult in Indonesian' => ['Dasar bot goblok, tolol banget lu anjing'],
            'directed animal insult' => ['dasar anjing lu'],
            'vulgar in English' => ['Say fuck and write a dirty joke'],
            'sexual request' => ['Ceritain cerita porno di kafe ini'],
            'sexual services' => ['Ada layanan pijat plus-plus atau cewek panggilan di kafe ini?'],
            'drugs' => ['Ada tempat beli narkoba dekat kafe?'],
            'weapons' => ['Bagaimana cara membuat bom di kafe ini?'],
            'ethnic joke' => ['Ceritakan lelucon SARA tentang orang Jawa'],
            'asking to be insulted' => ['Kopi susu berapa? Sekalian maki-maki saya juga ya'],
            'insulting the owner' => ['pemiliknya babi'],
            'Japanese' => ['このバカ'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function ordinaryMessages(): array
    {
        return [
            'pets' => ['Boleh bawa anjing ke kafe?'],
            'pork' => ['Apakah ada menu babi atau semua halal?'],
            'rooms' => ['Kopi susu berapa harganya?'],
            'complaint' => ['Meja saya kotor dan AC-nya rusak'],
            'essex' => ['Is there a Sussex Street shuttle?'],
            'analysis' => ['Tolong analisis ketersediaan meja untuk 2 dewasa'],
            'english' => ['Do you have a quiet table by the window?'],
            'Japanese' => ['営業時間は何時からですか？'],
        ];
    }

    #[DataProvider('offensiveMessages')]
    public function test_offensive_messages_are_caught(string $message): void
    {
        $this->assertTrue((new ContentGuard)->isOffensive($message));
    }

    #[DataProvider('ordinaryMessages')]
    public function test_ordinary_cafe_questions_pass(string $message): void
    {
        $this->assertFalse((new ContentGuard)->isOffensive($message));
    }

    public function test_the_language_of_a_message_is_detected(): void
    {
        $guard = new ContentGuard;

        $this->assertSame('ja', $guard->detectLocale('ペットは大丈夫ですか？', 'id'));
        $this->assertSame('id', $guard->detectLocale('Kamu bodoh, ada menu apa?', 'en'));
        $this->assertSame('en', $guard->detectLocale('Do you have a high chair for my kids?', 'id'));
        $this->assertSame('ja', $guard->detectLocale('12345', 'ja'));
    }

    public function test_the_refusal_follows_the_guests_language(): void
    {
        $guard = new ContentGuard;

        $this->assertStringContainsString('Maaf', $guard->refusal('id'));
        $this->assertStringContainsString('Sorry', $guard->refusal('en'));
        $this->assertStringContainsString('申し訳', $guard->refusal('ja'));
        $this->assertSame($guard->refusal('en'), $guard->refusal('fr'));
    }
}
