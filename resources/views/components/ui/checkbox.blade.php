{{-- A checkbox that submits "1" when ticked. Pass :checked="old('x', $model->x)"; a class goes on the label, so it can place the control in a grid. --}}
@props([
    'name',
    'label',
    'checked' => false,
])

<label {{ $attributes->only('class')->class('flex items-center gap-2 text-sm text-text') }}>
    <input
        type="checkbox"
        name="{{ $name }}"
        value="1"
        @checked($checked)
        {{ $attributes->except('class')->class('rounded-sm border-border-strong') }}
    >
    {{ $label }}
</label>
