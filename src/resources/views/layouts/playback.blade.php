<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Episodes') - playpipe</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f7f6f2;
            --surface: #ffffff;
            --surface-alt: #f0f7f4;
            --text: #1d2528;
            --muted: #667075;
            --border: #d9dedb;
            --accent: #b45309;
            --accent-strong: #92400e;
            --link: #0f766e;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.6;
        }

        a {
            color: var(--link);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .topbar {
            border-bottom: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.94);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .topbar-inner {
            max-width: 1040px;
            margin: 0 auto;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            color: var(--text);
            font-weight: 700;
            letter-spacing: 0;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 14px;
            white-space: nowrap;
        }

        .page {
            max-width: 1040px;
            margin: 0 auto;
            padding: 28px 18px 56px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            margin-bottom: 22px;
        }

        h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.2;
            letter-spacing: 0;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 20px;
            line-height: 1.3;
            letter-spacing: 0;
        }

        h3 {
            margin: 0 0 8px;
            font-size: 17px;
            line-height: 1.35;
            letter-spacing: 0;
        }

        .muted {
            color: var(--muted);
        }

        .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 14px;
            color: var(--muted);
            font-size: 14px;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 18px;
        }

        .episode-list {
            display: grid;
            gap: 14px;
        }

        .episode-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 18px;
            display: grid;
            gap: 12px;
        }

        .episode-card-title {
            color: var(--text);
            font-size: 19px;
            font-weight: 700;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid var(--accent);
            background: var(--accent);
            color: #fff;
            font-weight: 700;
        }

        .button:hover {
            background: var(--accent-strong);
            color: #fff;
            text-decoration: none;
        }

        .button.secondary {
            background: var(--surface);
            color: var(--accent-strong);
        }

        .button.secondary:hover {
            background: #fff7ed;
            color: var(--accent-strong);
        }

        .filters {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(160px, 240px) auto;
            gap: 10px;
            margin-bottom: 18px;
        }

        .input,
        .select {
            width: 100%;
            min-height: 40px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            padding: 8px 10px;
            color: var(--text);
            font: inherit;
        }

        .audio-player {
            width: 100%;
            min-height: 42px;
        }

        .stack {
            display: grid;
            gap: 18px;
        }

        .section-block,
        .topic-block {
            border-top: 1px solid var(--border);
            padding-top: 16px;
        }

        .section-block:first-child,
        .topic-block:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .section-text,
        .topic-text {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--surface-alt);
            color: #166534;
            font-size: 13px;
            font-weight: 700;
        }

        .pagination {
            margin-top: 22px;
        }

        @media (max-width: 720px) {
            .topbar-inner,
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            h1 {
                font-size: 26px;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('episodes.index') }}">playpipe</a>
            <nav class="nav" aria-label="Primary">
                <a href="{{ route('episodes.index') }}">Episodes</a>
                <a href="{{ url('/admin') }}">Admin</a>
            </nav>
        </div>
    </header>

    <main class="page">
        @yield('content')
    </main>
</body>
</html>
