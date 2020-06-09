
@php
$hasSlot = !empty($slot->toHtml()) || $attributes->get('selector');
$bgCss = $hasSlot ? 'bg_' . uniqid() : $attributes->get('class');
$class = $attributes->get('class') . ' ' . ($hasSlot ? ($bgCss) : null);
$selector = $attributes->get('selector') ? $attributes->get('selector') : 'div';
@endphp

@if($bgCss)
<style>
  .{{ $bgCss }} {
    background-image: url({!! $defaultSrc !!});
  }

  @foreach($defaultPaths as $resolution => $src)
    @if($resolution !== '1x') 
      @media 
      only screen and (-webkit-min-device-pixel-ratio: {{ intval($resolution) }}),
      only screen and (min--moz-device-pixel-ratio: {{ intval($resolution) }}),
      only screen and (-o-min-device-pixel-ratio: {{ intval($resolution) }}/1),
      only screen and (min-device-pixel-ratio: {{ intval($resolution) }}),
      only screen and (min-resolution: {{ intval($resolution) }}dppx) {
        .{{ $bgCss }} {
          background-image: url({!! $src !!});
        }
      }
    @endif
  @endforeach

  @foreach ($srcSets as $srcSet)
    @isset($srcSet['sources']['1x'])
      @media (max-width: {{ $srcSet['maxWidth'] }}px) {
        .{{ $bgCss }} {
          background-image: url({!! $srcSet['sources']['1x'] !!});
        }
      }
    @endisset
  @endforeach

  @foreach ($srcSets as $srcSet)
    @foreach($srcSet['sources'] as $resolution => $src)
      @if($resolution !== '1x')
        @media 
        only screen and (-webkit-min-device-pixel-ratio: {{ intval($resolution) }}),
        only screen and (min--moz-device-pixel-ratio: {{ intval($resolution) }}),
        only screen and (-o-min-device-pixel-ratio: {{ intval($resolution) }}/1),
        only screen and (min-device-pixel-ratio: {{ intval($resolution) }}),
        only screen and (min-resolution: {{ intval($resolution) }}dppx)
        and (max-width: {{ $srcSet['maxWidth'] }}px) {
          .{{ $bgCss }} {
            background-image: url({!! $src !!});
          }
        }
      @endif
    @endforeach
  @endforeach
</style>

@if($hasSlot)
  <{{ $attributes->get('selector') }} class="{!! $class !!}">
    {{ $slot }}
  </{{ $attributes->get('selector') }}>
@endif

@endif
