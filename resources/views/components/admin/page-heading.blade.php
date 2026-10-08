@props(['title'])
<header class="lft-page-heading">
    <div>
        <h1>{{ $title }}</h1>
        <nav aria-label="مسار التنقل">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('main') }}">{{ __('main.home') }}</a></li>
                @if($title !== __('main.home'))
                    <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                @endif
            </ol>
        </nav>
    </div>
    <span class="lft-page-tag" dir="ltr">LEADER / WORKSPACE</span>
</header>
