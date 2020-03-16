<ul role="menu" {{ $attributes }}>
  @foreach ($children as $child)
    <li>
      <a
        target="{{ $child->target }}"
        href="{{ $child->url }}"
        role="menuitem"
      >
        {{ $child->title }}
      </a>
      @if($child->children->count())
        <x-nav
          class="dropdown-container"
          :parent="$child->id"
          :levels="$levels - 1"
          :type="$type"
          :root="$root"
        />
      @endif
    </li>
  @endforeach
</ul>
