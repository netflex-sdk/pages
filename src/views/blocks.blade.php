@php($originalHash = blockhash())
@foreach($blocks as $block)
  @php(@list($component, $hash) = $block)
  @php(blockhash($hash))
  <x-component :is="$component" :variables="$variables" />
  @php(blockhash(null))
@endforeach

@php(blockhash($originalHash))
