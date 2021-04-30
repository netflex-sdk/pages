User-Agent: *
@if ($production)
Allow: /
Sitemap: {{ route('sitemap.xml') }}
@else
Disallow: /
@endif

@foreach(config('pages.bad-bots', [] as $bot))
User-agent: {{ $bot }}
Disallow: /

@endforeach