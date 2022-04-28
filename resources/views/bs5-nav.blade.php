@php($componentId = uniqid())

<nav class="navbar navbar-expand-lg {{ $attributes->get('class') }}">
  <div class="container-fluid">
    @isset($brand)
        {{ $brand }}
    @endisset
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-{{ md5($componentId) }}" aria-controls="navbarSupportedContent-{{ md5($componentId) }}" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent-{{ md5($componentId) }}">
        @include('netflex-pages::fragments.bs5-nav-fragment', [
            'level' => 0,
            'labelledBy' => null,
            'start' => isset($start) ? $start : null,
            'end' => isset($end) ? $end : null,
            'children' => $children,
            'listClass' => 'navbar-nav ' . $attributes->get('list-class'),
            'itemClass' => 'nav-item' . $attributes->get('item-class'),
            'linkClass' => 'nav-link ' . $attributes->get('link-class')
        ])
    </div>
  </div>
</nav>