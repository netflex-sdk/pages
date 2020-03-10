@section('picture_attributes')
  @if($editable && current_mode() === 'edit')
    id="e-{{ $id ?? null }}-picture-{{ uniqid() }}"
    data-content-area="{{ $area ?? null }}"
    data-content-type="{{ $type ?? 'image' }}"
    data-content-dimensions="{{ $size ?? null }}"
    data-content-compressiontype="{{ $crop ?? null }}"
    data-content-id="{{ $id ?? null }}"
    class="{{ $picture_class ?? null}} find-image"
  @else
    @isset($picture_class)
      class="{{ $picture_class }}"
    @endisset
  @endif

  @isset($picture_style)
    style="{{ $picture_style ?? null }}"
  @endisset
@overwrite

@section('picture')
  @foreach($srcsets ?? [] as $set)
    <source srcset="{{ $set->url }}" media="(max-width: {{ $set->width }}px)">
  @endforeach
@overwrite

@section('image_attributes')
  @isset($image_class)
    class="{{ $image_class ?? null }}"
  @endisset

  @isset($src)
    src="{{ $src ?? null }}"
  @endisset

  @isset($alt)
    alt="{{ $alt ?? null }}"
  @endisset

  @isset($title)
    title="{{ $title ?? null }}"
  @endisset

  @isset($image_style)
    style="{{ $image_style ?? null }}"
  @endisset
@overwrite

@isset($src, $srcsets)
  <picture @yield('picture_attributes')>
    @yield('picture')
    <img @yield('image_attributes') />
  </picture>
@endisset
