User-Agent: *
@if ($production)
Allow: /

@foreach(config('pages.bad-bots', []) as $bot)
User-agent: {{ $bot }}
Disallow: /

@endforeach

Sitemap: {{ route('sitemap.xml') }}
@else
Disallow: /
@endif