@if ($levels || $levels === null)
  <ul role="menu" {{ $attributes }}>
    @foreach ($children as $child)
      <li class="{{ $liClass }}">
        <a
          class="{{ $aClassList($child) }}"
          target="{{ $child->target }}"
          href="{{ $child->url }}"
          role="menuitem"
        >
          {{ $child->title }}
        </a>
        @if($child->children->count())
          <x-nav
            :class="$dropdownClassList"
            :parent="$child->id"
            :levels="$dropdownLevels()"
            :type="$type"
            :root="$root"
          />
        @endif
      </li>
    @endforeach
  </ul>
@endif
