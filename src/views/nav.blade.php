@if ($levels || $levels === null)
  <ul role="menu" {{ $attributes }}>
    @foreach ($children as $child)
      <li class="{{ $liClass }}">
        <a
          class="{{ implode(' ', array_filter([$aClass, $isActive($child) ? $activeClass : null])) }}"
          target="{{ $child->target }}"
          href="{{ $child->url }}"
          role="menuitem"
        >
          {{ $child->title }}
        </a>
        @if($child->children->count())
          <x-nav
            :class="implode(' ', array_filter([$attributes->get('class'), $dropdownClass]))"
            :parent="$child->id"
            :levels="$levels !== null ? ($levels - 1) : $levels"
            :type="$type"
            :root="$root"
          />
        @endif
      </li>
    @endforeach
  </ul>
@endif
