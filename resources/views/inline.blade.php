@section('attributes')
    @if(current_mode() === 'edit')
        id="e-{{ $id ?? null }}-{{ $tag ?? 'inline' }}-{{ uniqid() }}"
        data-content-area="{{ $area ?? null }}"
        data-content-type="html"
        data-content-id="{{ $id ?? null }}"
        contenteditable="true"
        @if($value())
          data-hascontent="true"
        @else
          data-hascontent="false"
        @endif
    @endif

    @isset($class)
        class="{{ $class }}"
    @endisset

    @isset($style)
        style="{{ $style }}"
    @endisset
@overwrite

@if($tag() !== null)
    <{{ $tag() }} @yield('attributes') {{ $attributes }}>
@endif
    {!! $value() ?? $slot !!}
@if($tag() !== null)
    </{{ $tag() }}>
@endif
