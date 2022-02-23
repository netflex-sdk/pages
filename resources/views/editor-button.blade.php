<a
    {{ $attributes->merge(['class' => $class, 'style' => $style ]) }}
    href="#"
    data-area-name="{{ $name ?? null }}"
    data-area-field="{{ $field ?? null }}"
    data-area-description="{{ $description ?? null }}"
    data-page-id="{{ $page->id ?? null }}"
    data-area-config="{{ $config ?? null }}"
    data-area-type="{{ $type ?? null }}"
    data-area-alias="{{ $area ?? null }}"
    data-max-items="{{ $items ?? null }}"
    data-directory-id="{{ $relationId ?? null }}"
>
    @if(isset($slot) && $slot && $slot->isNotEmpty())
        {{ $slot }}
    @else
        {!! $icon ?? null !!} {{ $label ?? null }}
    @endif
</a>
