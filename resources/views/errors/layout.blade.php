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
            font-family: "Montserrat", Arial, sans-serif;
            background: #050505;
            color: #fff;
        }

        * { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            position: relative;
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            overflow: hidden;
            background: #050505;
        }

        body::before {
            position: fixed;
            inset: 0;
            z-index: 3;
            content: "";
            pointer-events: none;
            background: repeating-linear-gradient(
                to bottom,
                transparent 0,
                rgba(255, 255, 255, .035) 1px,
                transparent 2px,
                transparent 4px
            );
        }

        main {
            position: relative;
            z-index: 2;
            width: min(94vw, 760px);
            padding: 2.5rem 1rem;
            text-align: center;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .header {
            margin: 0 0 1rem;
            color: #fff;
            font-size: clamp(1.25rem, 4vw, 2rem);
            font-weight: 900;
            letter-spacing: .22em;
        }

        .code {
            position: relative;
            display: inline-block;
            margin: 0;
            color: #fff;
            font-family: Impact, "Arial Black", Arial, sans-serif;
            font-size: clamp(8rem, 36vw, 19rem);
            font-weight: 900;
            line-height: .82;
            letter-spacing: -.075em;
            text-shadow: 2px 0 #00bfff, -2px 0 #ff0055;
        }

        .code::before,
        .code::after {
            position: absolute;
            inset: 0;
            overflow: hidden;
            content: attr(data-code);
            pointer-events: none;
            -webkit-text-fill-color: currentColor;
        }

        .code::before {
            color: #00bfff;
            transform: translate(-4px, 0);
            clip-path: inset(27% 0 69% 0);
            animation: glitch-cyan 3s infinite steps(1, end);
        }

        .code::after {
            color: #ff0055;
            transform: translate(4px, 0);
            clip-path: inset(60% 0 35% 0);
            animation: glitch-magenta 2.4s infinite steps(1, end);
        }

        .message {
            margin: 1.25rem 0 2rem;
            color: #fff;
            font-size: clamp(1rem, 3.2vw, 1.45rem);
            font-weight: 800;
            letter-spacing: .12em;
            line-height: 1.5;
        }

        .action {
            display: inline-block;
            padding: .75rem 1.25rem;
            border: 1px solid rgba(255, 255, 255, .45);
            color: #fff;
            font: inherit;
            font-size: .82rem;
            letter-spacing: .1em;
            text-decoration: none;
            background: rgba(255, 255, 255, .04);
            transition: background-color .2s ease, border-color .2s ease;
        }

        .action:hover, .action:focus-visible {
            border-color: #00bfff;
            background: rgba(0, 191, 255, .12);
            outline: 2px solid transparent;
        }

        .pixels {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .pixel {
            position: absolute;
            width: 14px;
            height: 14px;
            background: #ff0055;
            box-shadow: -3px -3px #00bfff;
            animation: pixel-flicker 4s infinite steps(1, end);
        }

        .pixel:nth-child(1) { top: 5%; left: 47%; }
        .pixel:nth-child(2) { top: 16%; left: 23%; animation-delay: .4s; }
        .pixel:nth-child(3) { top: 20%; right: 18%; animation-delay: 1.2s; }
        .pixel:nth-child(4) { top: 43%; left: 8%; animation-delay: .8s; }
        .pixel:nth-child(5) { top: 62%; right: 9%; animation-delay: 1.7s; }
        .pixel:nth-child(6) { bottom: 17%; left: 15%; animation-delay: 2.1s; }
        .pixel:nth-child(7) { bottom: 8%; right: 24%; animation-delay: .9s; }
        .pixel:nth-child(8) { bottom: 3%; left: 52%; animation-delay: 1.5s; }

        @keyframes pixel-flicker {
            0%, 88%, 100% { opacity: 1; }
            90%, 94% { opacity: .2; }
            92%, 96% { opacity: .85; }
        }

        @keyframes glitch-cyan {
            0%, 86%, 100% { clip-path: inset(27% 0 69% 0); transform: translate(-4px, 0); }
            88% { clip-path: inset(41% 0 55% 0); transform: translate(-9px, 1px); }
            90% { clip-path: inset(68% 0 28% 0); transform: translate(5px, -1px); }
            92% { clip-path: inset(15% 0 81% 0); transform: translate(-2px, 0); }
        }

        @keyframes glitch-magenta {
            0%, 82%, 100% { clip-path: inset(60% 0 35% 0); transform: translate(4px, 0); }
            84% { clip-path: inset(24% 0 72% 0); transform: translate(9px, -1px); }
            87% { clip-path: inset(81% 0 15% 0); transform: translate(-6px, 1px); }
            90% { clip-path: inset(47% 0 48% 0); transform: translate(3px, 0); }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; }
        }

        @media (max-width: 420px) {
            .pixel { width: 10px; height: 10px; }
            .message { letter-spacing: .06em; }
        }
    </style>
</head>
<body>
    <main>
        <div class="pixels" aria-hidden="true">
            <span class="pixel"></span><span class="pixel"></span>
            <span class="pixel"></span><span class="pixel"></span>
            <span class="pixel"></span><span class="pixel"></span>
            <span class="pixel"></span><span class="pixel"></span>
        </div>
        <div class="content">
            <p class="header">@yield('header', 'ERROR')</p>
            <h1 class="code" data-code="@yield('code')">@yield('code')</h1>
            <p class="message">@yield('message')</p>
            @if (trim($__env->yieldContent('code')) === '419')
                <a class="action" href="{{ request()->fullUrl() }}">RECARGAR PÁGINA</a>
            @else
                <a class="action" href="{{ url('/') }}">VOLVER AL INICIO</a>
            @endif
        </div>
    </main>
</body>
</html>
