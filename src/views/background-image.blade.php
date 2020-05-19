{{-- @if($attributes->get('class')) --}}
<style>
  .{{ $attributes->get('class') }} {
    background-image: url({!! $defaultSrc !!});
  }

  @foreach ($srcSets as $srcSet)
    @isset($srcSet['sources']['1x'])
      @media (max-width: {{ $srcSet['maxWidth'] }}px) {
        .{{ $attributes->get('class') }} {
          background-image: url({!! $srcSet['sources']['1x'] !!});
        }
      }
    @endisset
  @endforeach

  @foreach ($srcSets as $srcSet)
    @foreach($srcSet['sources'] as $resolution => $src)
      @if($resolution !== '1x')
        @media (min-resolution: {{ intval($resolution) }}dppx), and (max-width: {{ $srcSet['maxWidth'] }}px) {
          .{{ $attributes->get('class') }} {
            background-image: url({!! $src !!});
          }
        }
      @endif
    @endforeach
  @endforeach

  }
  </style>

  <section class="{{ $attributes->get('class') }}">

  </section>
  {{-- @endif --}}
