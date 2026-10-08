<?php

namespace Tests\Unit;

use App\Support\Ui\FieldState;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\TestCase;

class FieldStateTest extends TestCase
{
    private function errors(array $messages = []): ViewErrorBag
    {
        return (new ViewErrorBag)->put('default', new MessageBag($messages));
    }

    public function test_a_nested_input_name_becomes_a_stable_id_and_finds_its_error(): void
    {
        $field = FieldState::for('translations[en][name]', null, $this->errors(['translations.en.name' => ['Too long.']]));

        $this->assertSame('field-translations-en-name', $field->id);
        $this->assertTrue($field->invalid());
        $this->assertSame('Too long.', $field->error);
    }

    public function test_an_explicit_id_wins(): void
    {
        $this->assertSame('email', FieldState::for('email', 'email', $this->errors())->id);
    }

    public function test_describedby_lists_the_help_and_the_error_in_that_order(): void
    {
        $field = FieldState::for('price', null, $this->errors(['price' => ['Required.']]), hasHelp: true);

        $this->assertSame('field-price-help field-price-error', $field->describedBy());
    }

    public function test_a_valid_field_without_help_describes_nothing(): void
    {
        $this->assertNull(FieldState::for('price', null, $this->errors())->describedBy());
    }
}
