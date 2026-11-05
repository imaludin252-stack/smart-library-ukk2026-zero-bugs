<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') — @yield('title') | Smart Library</title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --bg: #f1f5f9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: radial-gradient(circle at top, #e6fffb 0%, var(--bg) 55%);
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, "Segoe UI", Roboto, Arial, sans-serif;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 2rem 1.75rem;
            text-align: center;
            box-shadow: 0 24px 60px -24px rgba(15, 23, 42, .25);
        }
        .mark {
            width: 64px;
            height: 64px;
            margin: 0 auto .9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #ccfbf1;
            color: var(--primary-dark);
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: .02em;
        }
        .code {
            display: inline-block;
            margin-bottom: .6rem;
            padding: .25rem .7rem;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid var(--line);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
        }
        h1 { margin: 0 0 .5rem; font-size: 1.35rem; line-height: 1.3; }
        p { margin: 0 auto 1.25rem; max-width: 360px; font-size: .85rem; line-height: 1.6; color: var(--muted); }
        .incident {
            margin: 1.1rem 0 1.4rem;
            padding: .75rem .9rem;
            border: 1px dashed var(--line);
            border-radius: 12px;
            background: #f8fafc;
            text-align: left;
            font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
            font-size: .68rem;
            line-height: 1.6;
            color: #475569;
            word-break: break-word;
        }
        a.btn {
            display: block;
            width: 100%;
            padding: .75rem 1rem;
            border-radius: 12px;
            background: var(--primary);
            color: #fff;
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
            transition: background .15s ease;
        }
        a.btn:hover { background: var(--primary-dark); }
        .foot { margin-top: 1rem; font-size: .65rem; color: #94a3b8; }
    </style>
</head>
<body>
    <main class="card">
        <div class="mark">@yield('icon', '!')</div>
        <span class="code">@yield('code')</span>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>

        <div class="incident">
            Kode Insiden: SL-ERR-{{ now()->format('YmdHis') }}<br>
            Status: Terisolasi Aman — data Anda tidak hilang.
        </div>

        <a class="btn" href="{{ url('/') }}">Kembali ke Beranda</a>
        <div class="foot">Smart Library — SMK Budi Bakti Ciwidey</div>
    </main>
</body>
</html>
