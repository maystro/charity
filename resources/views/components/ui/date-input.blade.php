@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'error' => null,
    'min' => null,
    'max' => null,
])

<x-ui.input
    :label="$label"
    :name="$name"
    type="date"
    :required="$required"
    :error="$error"
    {{ $attributes }}
/>
