<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        @if (isset($page) && !is_null($page))
            <li class="breadcrumb-item">
                <a href="{{ route('main') }}">
                    {{ __('main.home') }}
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                {{ $page }}
            </li>
        @else
            <li class="breadcrumb-item active">
                <a href="{{ route('main') }}">
                    {{ __('main.home') }}
                </a>
            </li>
        @endif
    </ol>
</nav>


