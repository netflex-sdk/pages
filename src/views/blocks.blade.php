@foreach($blocks as $block => $hash)
  @php(blockhash($hash))
  <x-component :is="$block" :variables="$variables" />
  @php(blockhash(null))
@endforeach
