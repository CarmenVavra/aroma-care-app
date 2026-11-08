@props(['product', 'height' => 'h-1'])

<div {{ $attributes->class(['w-full overflow-hidden rounded-full bg-mist-300 dark:bg-white/10', $height]) }} role="progressbar"
    aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $product->fill_percent }}"
    aria-label="Füllstand {{ $product->fill_percent }} Prozent">
    <div class="{{ $product->stock_status->barClasses() }} h-full rounded-full" style="width: {{ $product->fill_percent }}%"></div>
</div>
