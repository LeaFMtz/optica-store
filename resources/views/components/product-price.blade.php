<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-2']) }}>
    <span class="text-lg font-black text-[#71C229]">
        {{ $price?->price->formatted() }}
    </span>
    
    @if($basePrice && $basePrice->price->value > $price->price->value)
        <span class="text-xs text-gray-400 line-through">
            {{ $basePrice->price->formatted() }}
        </span>
    @endif
</div>
