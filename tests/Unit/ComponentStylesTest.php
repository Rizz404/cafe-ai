<?php

namespace Tests\Unit;

use App\Support\Ui\ComponentStyles;
use PHPUnit\Framework\TestCase;

class ComponentStylesTest extends TestCase
{
    public function test_a_button_gets_its_variant_and_size_classes(): void
    {
        $classes = ComponentStyles::button('danger', 'sm');

        $this->assertStringContainsString('bg-danger', $classes);
        $this->assertStringContainsString('px-3 py-1.5', $classes);
    }

    public function test_an_unknown_variant_falls_back_to_the_primary_button(): void
    {
        $this->assertStringContainsString('bg-primary', ComponentStyles::button('nonsense', 'nonsense'));
    }

    public function test_alerts_and_badges_fall_back_to_a_known_tone(): void
    {
        $this->assertStringContainsString('bg-success-soft', ComponentStyles::alert('nonsense'));
        $this->assertStringContainsString('bg-surface-muted', ComponentStyles::badge('nonsense'));
    }

    public function test_an_invalid_control_has_a_danger_border(): void
    {
        $this->assertStringContainsString('border-danger', ComponentStyles::control(true));
        $this->assertStringContainsString('border-border-strong', ComponentStyles::control(false));
    }

    public function test_no_class_is_a_raw_palette_color(): void
    {
        $source = file_get_contents((new \ReflectionClass(ComponentStyles::class))->getFileName());

        $this->assertDoesNotMatchRegularExpression('/\b(?:bg|text|border)-(?:amber|stone|red|emerald)-\d{2,3}\b/', $source);
    }
}
