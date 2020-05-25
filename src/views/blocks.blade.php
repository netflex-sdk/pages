@foreach($blocks as $block)
  <x-component :is="$block" :variables="$variables" />
@endforeach
