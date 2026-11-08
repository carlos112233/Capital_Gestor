<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b0c10">
    <title>@yield('code') — @yield('message')</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: "Share Tech Mono", "Montserrat", "Courier New", monospace;
            background: #0b0c10;
            color: #e8f1f2;
        }

        * { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            overflow: hidden;
            background:
                radial-gradient(ellipse at 50% 45%, rgba(0, 255, 204, .08), transparent 55%),
                #0b0c10;
        }

        body::before {
            position: fixed;
            inset: 0;
            z-index: 1;
            content: "";
            pointer-events: none;
            background: repeating-linear-gradient(
                to bottom,
                transparent 0,
                rgba(0, 0, 0, .22) 1px,
                transparent 3px,
                transparent 5px
            );
        }

        main {
            position: relative;
            z-index: 2;
            width: min(92vw, 760px);
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .header {
            margin: 0 0 1.25rem;
            color: #00ffcc;
            font-size: clamp(.8rem, 2vw, 1rem);
            letter-spacing: .35em;
            text-shadow: 0 0 12px rgba(0, 255, 204, .65);
        }

        .code {
            position: relative;
            display: inline-block;
            margin: 0;
            color: #f2f7f7;
            font-family: "Share Tech Mono", "Courier New", monospace;
            font-size: clamp(6rem, 25vw, 13rem);
            font-weight: 900;
            line-height: .95;
            letter-spacing: .06em;
            text-shadow: 0 0 26px rgba(0, 255, 204, .2);
        }

        .code::before,
        .code::after {
            position: absolute;
            inset: 0;
            overflow: hidden;
            content: attr(data-code);
            pointer-events: none;
        }

        .code::before {
            color: #00ffcc;
            transform: translate(-3px, 0);
            clip-path: inset(12% 0 68% 0);
            animation: glitch-cyan 2.8s infinite steps(1, end);
        }

        .code::after {
            color: #ff0055;
            transform: translate(3px, 0);
            clip-path: inset(62% 0 14% 0);
            animation: glitch-magenta 2.1s infinite steps(1, end);
        }

        .message {
            margin: 1.5rem 0 2rem;
            color: #aab8bc;
            font-size: clamp(.9rem, 2.7vw, 1.2rem);
            letter-spacing: .12em;
            line-height: 1.7;
        }

        .action {
            display: inline-block;
            padding: .85rem 1.4rem;
            border: 1px solid #00ffcc;
            color: #00ffcc;
            font: inherit;
            letter-spacing: .12em;
            text-decoration: none;
            background: rgba(0, 255, 204, .04);
            box-shadow: 0 0 18px rgba(0, 255, 204, .12), inset 0 0 14px rgba(0, 255, 204, .04);
            transition: background-color .2s ease, box-shadow .2s ease, color .2s ease;
        }

        .action:hover, .action:focus-visible {
            color: #0b0c10;
            background: #00ffcc;
            box-shadow: 0 0 26px rgba(0, 255, 204, .5);
            outline: none;
        }

        @keyframes glitch-cyan {
            0%, 88%, 100% { clip-path: inset(12% 0 68% 0); transform: translate(-3px, 0); }
            90% { clip-path: inset(42% 0 38% 0); transform: translate(-7px, 1px); }
            92% { clip-path: inset(4% 0 84% 0); transform: translate(4px, -1px); }
            94% { clip-path: inset(72% 0 8% 0); transform: translate(-2px, 0); }
        }

        @keyframes glitch-magenta {
            0%, 84%, 100% { clip-path: inset(62% 0 14% 0); transform: translate(3px, 0); }
            86% { clip-path: inset(18% 0 62% 0); transform: translate(7px, -1px); }
            89% { clip-path: inset(82% 0 3% 0); transform: translate(-4px, 1px); }
            92% { clip-path: inset(48% 0 32% 0); transform: translate(2px, 0); }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <main>
        <p class="header">@yield('header', 'SYSTEM ALERT')</p>
        <h1 class="code" data-code="@yield('code')">@yield('code')</h1>
        <p class="message">@yield('message')</p>
        @if (trim($__env->yieldContent('code')) === '419')
            <a class="action" href="{{ request()->fullUrl() }}">RECARGAR PÁGINA</a>
        @else
            <a class="action" href="{{ url('/') }}">VOLVER AL INICIO</a>
        @endif
    </main>
</body>
</html>
