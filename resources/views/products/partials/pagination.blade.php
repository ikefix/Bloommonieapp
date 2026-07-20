
<div class="pagination-wrapper">


    <div class="d-flex justify-content-center mt-3">
        @if ($products->hasPages())
            <nav>
                <ul class="pagination">
                    {{-- Prev Button --}}
                    <li class="page-item {{ $products->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $products->previousPageUrl() }}" tabindex="-1">
                            ⬅️ Prev
                        </a>
                    </li>

                    {{-- Page Numbers --}}
                    @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                        <li class="page-item {{ $products->currentPage() == $page ? 'active' : '' }}">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endforeach

                    {{-- Next Button --}}
                    <li class="page-item {{ !$products->hasMorePages() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $products->nextPageUrl() }}">
                            Next ➡️
                        </a>
                    </li>
                </ul>
            </nav>
        @endif
    </div>


</div>