@section('attributes')
  @if($editable && current_mode() === 'edit')
    id="e-{{ $id ?? null }}-image-{{ uniqid() }}"
    class="{{ $class ?? null }} find-image"
    data-content-area="{{ $area ?? null }}"
    data-content-type="{{ $type ?? 'image' }}"
    data-content-dimensions="{{ $size ?? null }}"
    data-content-compressiontype="{{ $crop ?? null }}"
    data-content-id="{{ $id ?? null }}"
  @else
    @isset($class)
      class="{{ $class ?? null }}"
    @endisset
  @endif

  @isset($src)
    src="{{ $src ?? null }}"
  @endisset

  @isset($alt)
    alt="{{ $alt ?? null }}"
  @endisset

  @isset($title)
    title="{{ $title ?? null }}"
  @endisset

  @isset($style)
    style="{{ $style ?? null }}"
  @endisset
@overwrite

@isset($src)
  <img @yield('attributes')>
@endisset
