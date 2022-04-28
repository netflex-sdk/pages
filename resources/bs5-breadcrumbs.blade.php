<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        @foreach($trail() as $crumb)
            <li class="breadcrumb-item {{ $loop->last ? 'active' : null }}" {{ $loop->last ? 'aria-current="page"' : null }}>
                @if(!$loop->last)
                    <a href="{{ $crumb->url }}">
                        {{ $crumb->name }}
                    </a>
                @else
                    {{ $crumb->name }}
                @endif
            </li>
        @endforeach
    </ol>
</nav>