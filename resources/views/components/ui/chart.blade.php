@props([
    'type',
    'data',
    'options' => [],
    'height' => 280,
])

<div
    x-data="chart(@js(['type' => $type, 'data' => $data, 'options' => $options]))"
    {{ $attributes->merge(['class' => 'relative w-full']) }}
    style="height: {{ (int) $height }}px"
>
    <canvas x-ref="canvas"></canvas>
</div>
