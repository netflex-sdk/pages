@section('attributes')
  @if($editable && current_mode() === 'edit')
    @php($tag = $tag ?? 'div')
    id="e-{{ $id ?? null }}-inline-{{ uniqid() }}"
    data-content-area="{{ $area ?? null }}"
    data-content-type="{{ $type ?? 'html' }}"
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
  {!! $html ?? null !!}
@isset($tag)
  </{{ $tag }}>
@endif
