@if($inline && current_mode() === 'edit')
<picture {{ $attributes->merge(['class' => $pictureClass]) }}>
  @foreach ($srcSets as $srcSet)
    <source srcset="{{ $srcSet['paths'] }}" media="(max-width: {{ $srcSet['mqMaxWidth'] }}px)">
  @endforeach
  <img {{ $attributes->only('class')->merge(['class' => $imageClass . ' find-image']) }} src="{{ $defaultSrc }}" {{ $attributes->except('id')->merge($editorSettings()) }}>
</picture>
@else
<picture {{ $attributes->merge(['class' => $pictureClass]) }}>
  @foreach ($srcSets as $srcSet)
    <source srcset="{{ $srcSet['paths'] }}" media="(max-width: {{ $srcSet['mqMaxWidth'] }}px)">
  @endforeach
  @if($useExplicitWidthAndHeight)
    <img width="{{ $preset()->width }}" height="{{ $preset()->height }}" class="{{ collect([$attributes->get('class'), $imageClass])->filter()->join(' ') }}" src="{{ $defaultSrc }}" srcset="{{ $defaultSrcSet }}" title="{{ $title }}" alt="{{ $alt }}" loading="{{ $loading }}">
  @else
    <img class="{{ collect([$attributes->get('class'), $imageClass])->filter()->join(' ') }}" src="{{ $defaultSrc }}" srcset="{{ $defaultSrcSet }}" title="{{ $title }}" alt="{{ $alt }}" loading="{{ $loading }}">
</picture>
@endif
