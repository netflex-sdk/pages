<!-- newsletter-tag -->

@if(is_array($tag))
  @foreach($tag as $key => $nextTag)
    @include('pages::newsletter-tag', ['key' => $key, 'tag' => $nextTag, 'prefix' => array_filter([...($prefix ?? []), $key])])
  @endforeach
@else

  @if(($key ?? '') == '_group_header')
    <div class="nf-automation-mail-tag-header">
      <h3>{{ $tag }}</h3>
    </div>
  @else
    <button class="nf-automation-mail-tag-option" data-option="{{ implode(".", $prefix ?? []) }}">
      <strong>
        <small style="">{{ implode(".", $prefix ?? []) }}</small>
      </strong>
      <div style="color: #aaaaaa; font-size: 0.75rem;">
        {{ $tag }}
      </div>
    </button>
  @endif
@endif
<!-- /newsletter-tag -->
