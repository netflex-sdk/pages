@section('attributes')
    @if(current_mode() === 'edit')
        id="e-{{ $id ?? null }}-{{ $tag ?? 'inline' }}-{{ uniqid() }}"
        data-content-area="{{ $area ?? null }}"
        data-content-type="html"
        data-content-id="{{ $id ?? null }}"
        contenteditable="true"
    @endif

    @isset($class)
        class="{{ $class }}"
    @endisset

    @isset($style)
        style="{{ $style }}"
    @endisset
@overwrite

@isset($tag)
    <{{ $tag }} @yield('attributes')>
@endif
    {!! $value ?? null !!}
@isset($tag)
    </{{ $tag }}>
@endif
