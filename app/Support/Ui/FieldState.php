<?php

namespace App\Support\Ui;

use Illuminate\Support\ViewErrorBag;

/**
 * What a form control needs to know about itself: whether it is invalid, its
 * error text, and the ids that tie the label, help text and error together.
 */
final readonly class FieldState
{
    public function __construct(
        public string $id,
        public ?string $error,
        public bool $hasHelp,
    ) {}

    /**
     * @param  string  $name  Input name as written in the form, e.g. "translations[en][name]".
     */
    public static function for(string $name, ?string $id, ViewErrorBag $errors, bool $hasHelp = false): self
    {
        $dotted = trim(str_replace(['[', ']'], ['.', ''], $name), '.');

        return new self(
            id: $id ?? 'field-'.str_replace('.', '-', $dotted),
            error: $errors->first($dotted) ?: null,
            hasHelp: $hasHelp,
        );
    }

    public function invalid(): bool
    {
        return $this->error !== null;
    }

    public function helpId(): string
    {
        return $this->id.'-help';
    }

    public function errorId(): string
    {
        return $this->id.'-error';
    }

    /**
     * The aria-describedby value, or null when nothing describes the control.
     */
    public function describedBy(): ?string
    {
        $ids = array_filter([
            $this->hasHelp ? $this->helpId() : null,
            $this->invalid() ? $this->errorId() : null,
        ]);

        return $ids === [] ? null : implode(' ', $ids);
    }
}
