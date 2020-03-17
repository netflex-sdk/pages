@section('picture_attributes')
    @parent

    @if($inline && current_mode() === 'edit')
        id="e-{{ $id ?? null }}-picture-{{ uniqid() }}"
        data-content-type="image"
        data-content-field="image"
        data-content-dimensions="{{ $size ?? null }}"
        data-content-compressiontype="{{ $mode ?? null }}"
        data-content-id="{{ $id ?? null }}"
        class="{{ $class ?? null}} find-image"
    @endif
@overwrite

@section('picture')
    @parent

    @foreach($srcSets ?? [] as $set)
        <source srcset="{!! $set->url !!}" media="(max-width: {{ $set->width }}px)">
    @endforeach
@overwrite

@section('picture_attributes')
    @parent

    @isset($alt)
        alt="{{ $alt ?? null }}"
    @endisset

    @isset($title)
        title="{{ $title ?? null }}"
    @endisset

    @isset($imageClass)
        class="{{ $imageClass ?? null }}"
    @endisset

    @isset($style)
        style="{{ $style ?? null }}"
    @endisset
@overwrite

@section('image_attributes')
    @parent

    @isset($imageClass)
        class="{{ $imageClass ?? null }}"
    @endisset

    @isset($src)
        src="{!! $src??null !!}"
    @endisset

    @isset($imageStyle)
        style="{{ $imageStyle ?? null }}"
    @endisset

    @isset($alt)
        alt="{{ $alt ?? null }}"
    @endisset

    @isset($title)
        title="{{ $title ?? null }}"
    @endisset
@overwrite

@isset($src, $srcSets)
    <picture @yield('picture_attributes')>
        @yield('picture')
        <img @yield('image_attributes') />
    </picture>
@endisset
