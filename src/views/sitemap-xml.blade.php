<{!! '?xml version="1.0" encoding="utf-8"?' !!}>
<{!! '?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?' !!}>
<urlset xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

@foreach ($entries as $entry)
    <url>
        <loc>{{ $entry->url }}</loc>
        <lastmod>{{ $entry->updated }}</lastmod>
    </url>
@endforeach
</urlset>
