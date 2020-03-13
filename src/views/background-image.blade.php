<style scoped>
	{!! $selector !!} {
    background-image: url({!! $src !!});
  }

  @foreach ($srcSets as $set)
    @media (max-width: {{ $set->width }}px) {
      {{ $selector }} {
        background-image: url({!! $set->url !!}?src={{ $set->width }}w);
      }
    }

    @media (-webkit-min-device-pixel-ratio: 1.5),
	         (min--moz-device-pixel-ratio: 1.5),
	         (-o-min-device-pixel-ratio: 3/2),
	         (min-device-pixel-ratio: 1.5),
	         (min-resolution: 1.5dppx) {
      @media (max-width: {{ $set->width }}px) {
        {{ $selector }} {
          background-image: url({!! $set->url !!}?src={{ $set->width }}w);
        }
      }
    }
  @endforeach
</style>
