{{-- Labelled textarea. Pass value="{{ old('x', $model->x) }}"; the error comes from the validation bag by name. --}}
@props([
    'name',
    'label',
    'id' => null,
    'value' => null,
    'rows' => 2,
    'help' => null,
])

@php($field = \App\Support\Ui\FieldState::for($name, $id, $errors, filled($help)))

<div {{ $attributes->only('class') }}>
    <label for="{{ $field->id }}" class="block text-sm font-medium text-text">{{ $label }}</label>
    <textarea
        id="{{ $field->id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($field->invalid()) aria-invalid="true" @endif
        @if ($field->describedBy()) aria-describedby="{{ $field->describedBy() }}" @endif
        {{ $attributes->except('class')->class(\App\Support\Ui\ComponentStyles::control($field->invalid())) }}
    >{{ $value }}</textarea>
    @if (filled($help))
        <p id="{{ $field->helpId() }}" class="mt-1 text-xs text-muted">{{ $help }}</p>
    @endif
    @if ($field->invalid())
        <p id="{{ $field->errorId() }}" class="mt-1 text-xs text-danger">{{ $field->error }}</p>
    @endif
</div>
