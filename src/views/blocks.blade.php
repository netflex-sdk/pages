@php($originalHash = blockhash())
@foreach($blocks as $block)
  @php(@list($component, $hash) = $block)
  @php(blockhash($hash))
  <x-dynamic-component :component="$component" :variables="$variables" />
  @php(blockhash(null))
@endforeach

@php(blockhash($originalHash))
