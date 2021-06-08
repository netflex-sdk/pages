User-Agent: *
@if ($production)
Allow: /

@foreach(config('pages.bad-bots', []) as $bot)
User-agent: {{ $bot }}
Disallow: /

@endforeach
@if(Route::has('sitemap.xml'))
Sitemap: {{ route('sitemap.xml') }}
@endif
@else
Disallow: /
@endif
