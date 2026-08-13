@if ($paginator->hasPages())
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="page-link" style="cursor:default;">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="page-link active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="page-link">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach
@endif