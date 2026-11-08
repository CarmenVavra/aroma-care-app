@props(['product', 'size' => 'md'])

<span aria-hidden="true" @class([
    'grid shrink-0 place-items-center font-display font-semibold',
    $product->category->tileClasses(),
    'size-12 rounded-[14px] text-xl' => $size === 'md',
    'size-14 rounded-2xl text-2xl' => $size === 'lg',
])>{{ $product->initials }}</span>
