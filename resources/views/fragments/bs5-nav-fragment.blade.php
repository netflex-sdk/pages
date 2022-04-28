@php($componentId = uniqid())

<ul class="{{ $listClass ?? null }}" {{ $labelledBy ? 'aria-labelledby="' . $labelledBy . '"' : null }}>
    @isset($start)
        <li class="{{ $itemClass ?? null }}">
            {{ $start }}
        </li>
    @endisset
    
    @foreach($children as $child)
        <li class="{{ $itemClass ?? null }} {{ $child->children->count() ? 'dropdown' : null }}">
            <a
                class="{{ $linkClass ?? null }} {{ $child->children->count() ? 'dropdown-toggle' : null }}"
                target="{{ $child->target }}"
                href="{{ $child->url }}"

                @if($child->children->count())
                    id="nav-link-{{ md5($componentId . $child->id) }}"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                @endif
            >
                {{ $child->title }}
            </a>
            @if($child->children->count())
                @include('netflex-pages::fragments.bs5-nav-fragment', [
                    'labelledBy' => 'nav-link-' . md5($componentId . $child->id),
                    'start' => null,
                    'end' => null,
                    'children' => $child->children,
                    'listClass' => 'dropdown-menu',
                    'linkClass' => 'dropdown-item',
                    'itemClass' => null
                ])
            @endif
        </li>
    @endforeach

    @isset($end)
        <li class="{{ $itemClass ?? null }}">
            {{ $end }}
        </li>
    @endisset
</ul>