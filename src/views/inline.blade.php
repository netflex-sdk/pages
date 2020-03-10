@section('attributes')
    @if(current_mode() === 'edit')
        id="e-{{ $id ?? null }}-inline-{{ uniqid() }}"
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
@stop

@isset($tag)
    <{{ $tag }} @yield('attributes')>
@endif
    {!! $value ?? null !!}
@isset($tag)
    </{{ $tag }}>
@endif
