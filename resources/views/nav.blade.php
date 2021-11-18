@if ($levels || $levels === null)
  <ul role="menu" {{ $attributes }}>
    @foreach ($children as $child)
      <li class="{{ $liClass }}">
        <a
          class="{{ $aClassList($child) }}"
          target="{{ $child->target }}"
          href="{{ $child->url }}"
          role="menuitem"
          title="{{ $showTitle ? $child->title : '' }}"
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
            :showTitle="$showTitle"
          />
        @endif
      </li>
    @endforeach
  </ul>
@endif
