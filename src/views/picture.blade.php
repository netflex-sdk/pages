@mode('edit')
<img {{ $attributes->only('class')->merge(['class' => $imageClass . ' find-image']) }} src="{{ $default }}" {{ $attributes->except('id')->merge($editorSettings()) }}" width="100" height="100">
@else
<picture {{ $attributes->merge(['class' => $pictureClass]) }}>
  @foreach ($srcSets as $srcSet)
    <source srcset="{{ $srcSet['paths'] }}">
  @endforeach
  <img class="{{  collect([$attributes->get('class'), $imageClass])->filter()->join(' ') }}" src="{{ $default }}" title="{{ $title }}" alt="{{ $alt }}">
</picture>
@endmode
