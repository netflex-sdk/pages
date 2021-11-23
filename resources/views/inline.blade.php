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

@if($tag() !== null)
    <{{ $tag() }} @yield('attributes') {{ $attributes }}>
@endif
    {!! $value ?? null !!}
@if($tag() !== null)
    </{{ $tag() }}>
@endif
