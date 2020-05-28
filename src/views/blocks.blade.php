@foreach($blocks as $block)
  @php
    @list($component, $hash) = $block;
    blockhash($hash)
  @endphp
  
  <x-component :is="$component" :variables="$variables" />
  @php
    blockhash(null)
  @endphp
@endforeach
