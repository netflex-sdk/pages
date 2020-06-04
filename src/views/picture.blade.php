@if($inline && current_mode() === 'edit')
<img {{ $attributes->only('class')->merge(['class' => $imageClass . ' find-image']) }} src="{{ $defaultSrc }}" {{ $attributes->except('id')->merge($editorSettings()) }}>
@else
<picture {{ $attributes->merge(['class' => $pictureClass]) }}>
  @foreach ($srcSets as $srcSet)
    <source srcset="{{ $srcSet['paths'] }}" media="(max-width: {{ $srcSet['maxWidth'] }}px)">
  @endforeach
  <img class="{{  collect([$attributes->get('class'), $imageClass])->filter()->join(' ') }}" src="{{ $defaultSrc }}" srcset="{{ $defaultSrcSet }}" title="{{ $title }}" alt="{{ $alt }}">
</picture>
@endmode
