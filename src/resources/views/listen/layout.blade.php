<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Episodes') - playpipe</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('icon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/listen.css', 'resources/js/listen.js'])
</head>
<body>
    <header class="mobile-topbar">
        <a class="brand-wordmark" href="{{ route('listen.episodes.index') }}">NOZOMI_RADIO_01</a>
        <div class="mobile-actions" aria-hidden="true">
            <span>⌁</span>
            <span>⚙</span>
        </div>
    </header>

    <div class="playback-shell">
        <aside class="side-console" aria-label="Playback console">
            <div class="operator-status">
                <strong>Operator_01</strong><br>
                Status:<br>
                Broadcasting
            </div>

            <section class="operator-card" aria-label="Operator profile">
                <img src="{{ asset('images/listen/operator-portrait.png') }}" alt="playpipe operator portrait">
                <div class="operator-card-body">
                    <span class="operator-card-title">playpipe</span>
                    Private episode playback.<br>
                    Frequency: 104.9 MHz.
                </div>
            </section>

            <nav class="side-nav" aria-label="Primary">
                <a href="{{ route('listen.episodes.index') }}" @if(request()->routeIs('listen.*')) aria-current="page" @endif>⌂ Protocol_Home</a>
                <a href="{{ route('listen.episodes.index') }}">☊ Char_Data</a>
                <a href="{{ route('listen.episodes.index') }}">↯ Trending_Sig</a>
                <a href="{{ url('/admin') }}">ⓘ Admin_Info</a>
            </nav>

            <div class="side-action">
                <a class="button" href="{{ route('listen.episodes.index') }}">Initiate_Freq</a>
            </div>
        </aside>

        <main class="content-frame">
            @yield('content')
        </main>
    </div>

    <nav class="mobile-nav" aria-label="Mobile">
        <a href="{{ route('listen.episodes.index') }}" @if(request()->routeIs('listen.*')) aria-current="page" @endif>
            <span>⌂</span>
            <span>Home</span>
        </a>
        <a href="{{ route('listen.episodes.index') }}">
            <span>♙</span>
            <span>Char</span>
        </a>
        <a href="{{ route('listen.episodes.index') }}">
            <span>↯</span>
            <span>Trends</span>
        </a>
        <a href="{{ url('/admin') }}">
            <span>▣</span>
            <span>Info</span>
        </a>
    </nav>

    <aside class="signal-widget" aria-label="Signal status">
        <strong>Signal: Strong</strong>
        <span>104.9 MHz Broadcast</span>
    </aside>
</body>
</html>
