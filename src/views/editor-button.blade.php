<a href="#"
    class="{{ $class ?? null }}"
    style="{{ $style ?? null }}"
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
    @isset($slot)
        {{ $slot }}
    @else
        {{ $icon ?? null }} {{ $label ?? null }}
    @endisset
</a>
