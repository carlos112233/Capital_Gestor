<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#000000">
    <title>@yield('code') — @yield('message')</title>

    <!-- Tipografía idéntica a la estética del GIF (Montserrat Black / ExtraBold) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: dark;
            --bg-color: #000000;
            --text-color: #ffffff;
            --cyan: #00abff;
            --pink: #ff2b81;
            --font-family: 'Montserrat', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            min-height: 100%;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: var(--font-family);
        }

        body {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            background: #000000;
            padding: 2.5rem 1.25rem;
        }

        /* Contenedor principal que recrea la composición exacta del GIF de Tenor */
        .glitch-canvas {
            position: relative;
            width: 100%;
            max-width: 540px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            user-select: none;
            padding: 1.5rem 0.5rem;
        }

        /* ========================================================
           1. TEXTO SUPERIOR: ERROR (con corte y glitch rítmico)
           ======================================================== */
        .error-tag {
            position: relative;
            font-size: clamp(2rem, 5.5vw, 2.75rem);
            font-weight: 900;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #ffffff;
            line-height: 1;
            margin-bottom: 0.5rem;
            display: inline-block;
            animation: err-shake 0.72s infinite steps(1);
        }

        .error-tag::before,
        .error-tag::after {
            content: attr(data-text);
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .error-tag::before {
            color: var(--cyan);
            transform: translate(-3px, 0);
            clip-path: inset(45% 0 20% 0);
            animation: err-cyan 1.44s infinite steps(1);
        }

        .error-tag::after {
            color: var(--pink);
            transform: translate(3px, 0);
            clip-path: inset(15% 0 55% 0);
            animation: err-pink 1.08s infinite steps(1);
        }

        /* ========================================================
           2. NÚMERO CENTRAL (404) CON FRAGMENTACIÓN Y SLICES
           ======================================================== */
        .number-wrapper {
            position: relative;
            display: inline-block;
            margin: 0.25rem 0 0.75rem;
        }

        .glitch-digits {
            position: relative;
            font-size: clamp(6.5rem, 24vw, 10.5rem);
            font-weight: 900;
            letter-spacing: 0.08em;
            line-height: 0.95;
            color: #ffffff;
            text-shadow: 
                -3px 0 var(--cyan),
                 3px 0 var(--pink);
            animation: num-shift 0.72s infinite steps(1);
            display: block;
        }

        /* Capa 1: Aberración y desfase Cyan */
        .glitch-digits::before {
            content: attr(data-code);
            position: absolute;
            inset: 0;
            color: var(--cyan);
            clip-path: polygon(0 22%, 100% 22%, 100% 44%, 0 44%, 0 66%, 100% 66%, 100% 88%, 0 88%);
            transform: translate(-7px, 0);
            animation: slice-cyan 0.72s infinite steps(1);
            pointer-events: none;
        }

        /* Capa 2: Aberración y desfase Magenta / Pink */
        .glitch-digits::after {
            content: attr(data-code);
            position: absolute;
            inset: 0;
            color: var(--pink);
            clip-path: polygon(0 10%, 100% 10%, 100% 32%, 0 32%, 0 52%, 100% 52%, 100% 74%, 0 74%);
            transform: translate(7px, 0);
            animation: slice-pink 0.72s infinite steps(1);
            pointer-events: none;
        }

        /* Capas de desplazamiento de bandas horizontales (Slicing idéntico al GIF) */
        .slice-band-1,
        .slice-band-2,
        .slice-band-3 {
            position: absolute;
            inset: 0;
            color: #ffffff;
            pointer-events: none;
            overflow: hidden;
        }

        .slice-band-1 {
            clip-path: inset(26% 0 62% 0);
            animation: band-left 0.72s infinite steps(1);
        }

        .slice-band-2 {
            clip-path: inset(48% 0 38% 0);
            animation: band-right 0.72s infinite steps(1);
        }

        .slice-band-3 {
            clip-path: inset(68% 0 16% 0);
            animation: band-left-deep 0.72s infinite steps(1);
        }

        /* Rayas horizontales de corte (barras glitch que sobresalen de los números) */
        .scan-streak {
            position: absolute;
            height: 4px;
            background: #ffffff;
            pointer-events: none;
            z-index: 6;
            animation: streak-flash 0.72s infinite steps(1);
        }

        .streak-1 {
            top: 31%;
            left: -12%;
            width: 48%;
            box-shadow: 0 3px 0 var(--cyan);
            animation-delay: 0.12s;
        }

        .streak-2 {
            top: 54%;
            right: -10%;
            width: 42%;
            box-shadow: 0 -3px 0 var(--pink);
            animation-delay: 0.24s;
        }

        .streak-3 {
            top: 72%;
            left: 14%;
            width: 60%;
            background: #000000;
            box-shadow: 0 2px 0 var(--cyan), 0 -2px 0 var(--pink);
            animation-delay: 0.36s;
        }

        /* ========================================================
           3. SUBTÍTULO: PAGE NOT FOUND (o mensaje dinámico)
           ======================================================== */
        .subtitle-tag {
            position: relative;
            font-size: clamp(1rem, 3.8vw, 1.45rem);
            font-weight: 800;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #ffffff;
            margin-top: 0.5rem;
            margin-bottom: 2.25rem;
            animation: sub-jitter 1.44s infinite steps(1);
            display: inline-block;
        }

        .subtitle-tag::before,
        .subtitle-tag::after {
            content: attr(data-text);
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .subtitle-tag::before {
            color: var(--cyan);
            transform: translate(-3px, 0);
            clip-path: inset(40% 0 10% 0);
            animation: sub-cyan 0.96s infinite steps(1);
        }

        .subtitle-tag::after {
            color: var(--pink);
            transform: translate(3px, 0);
            clip-path: inset(10% 0 50% 0);
            animation: sub-pink 0.84s infinite steps(1);
        }

        /* ========================================================
           4. PÍXELES Y BLOQUES FLOTANTES (Fieles al GIF de Tenor)
           ======================================================== */
        .debris-container {
            position: absolute;
            inset: -15px;
            pointer-events: none;
            z-index: 8;
        }

        .pixel-block {
            position: absolute;
            background: var(--pink);
            box-shadow: -3px 0 0 var(--cyan);
            animation: pixel-pulse 0.72s infinite steps(1);
        }

        .pixel-block.alt {
            background: var(--cyan);
            box-shadow: 3px 0 0 var(--pink);
        }

        /* Mapeo posicional exacto extraído de los fotogramas del GIF */
        .p-1  { width: 18px; height: 18px; top: 11%; left: 50%; animation-delay: 0.06s; }
        .p-2  { width: 8px;  height: 8px;  top: 17%; left: 60%; animation-delay: 0.18s; }
        .p-3  { width: 16px; height: 16px; top: 21%; left: 28%; animation-delay: 0.30s; }
        .p-4  { width: 16px; height: 16px; top: 23%; right: 17%; animation-delay: 0.42s; }
        .p-5  { width: 18px; height: 18px; top: 33%; right: 26%; animation-delay: 0.54s; }
        .p-6  { width: 9px;  height: 9px;  top: 34%; left: 16%; animation-delay: 0.12s; }
        .p-7  { width: 18px; height: 18px; top: 44%; left: 12%; animation-delay: 0.24s; }
        .p-8  { width: 12px; height: 12px; top: 48%; left: 35%; animation-delay: 0.36s; }
        .p-9  { width: 14px; height: 14px; top: 51%; right: 10%; animation-delay: 0.48s; }
        .p-10 { width: 16px; height: 16px; top: 64%; right: 18%; animation-delay: 0.60s; }
        .p-11 { width: 14px; height: 14px; top: 75%; left: 10%; animation-delay: 0.15s; }
        .p-12 { width: 16px; height: 16px; top: 78%; right: 19%; animation-delay: 0.27s; }
        .p-13 { width: 9px;  height: 9px;  top: 79%; left: 24%; animation-delay: 0.39s; }
        .p-14 { width: 10px; height: 10px; bottom: 10%; left: 20%; animation-delay: 0.51s; }
        .p-15 { width: 18px; height: 18px; bottom: 10%; left: 38%; animation-delay: 0.08s; }
        .p-16 { width: 14px; height: 14px; bottom: 8%;  right: 32%; animation-delay: 0.20s; }
        .p-17 { width: 8px;  height: 8px;  bottom: 4%;  left: 56%; animation-delay: 0.32s; }

        /* ========================================================
           5. BOTÓN DE ACCIÓN CON ESTÉTICA NEÓN ISOMÉTRICA
           ======================================================== */
        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            padding: 0.85rem 1.85rem;
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            text-decoration: none;
            background: #000000;
            border: 2px solid #ffffff;
            border-radius: 4px;
            box-shadow: 
                -3px -3px 0 var(--cyan),
                 3px  3px 0 var(--pink);
            transition: transform 0.12s ease, box-shadow 0.12s ease, background-color 0.12s ease;
            cursor: pointer;
            z-index: 10;
        }

        .action-button:hover {
            transform: translate(2px, 2px);
            box-shadow: 
                -1px -1px 0 var(--cyan),
                 1px  1px 0 var(--pink);
            background: #0d0d0d;
        }

        .action-button:active {
            transform: translate(3px, 3px);
            box-shadow: 0 0 0;
        }

        /* ========================================================
           6. ANIMACIONES PASO A PASO (STEPS DISCRETOS DE 60-72ms)
           ======================================================== */
        @keyframes num-shift {
            0%, 100% { transform: translate(0, 0); }
            8.3%  { transform: translate(-4px, 0); }
            16.6% { transform: translate(4px, 0); }
            25%   { transform: translate(0, 0); }
            50%   { transform: translate(6px, 0); }
            58.3% { transform: translate(-5px, 0); }
            66.6% { transform: translate(0, 0); }
            83.3% { transform: translate(-3px, 0); }
        }

        @keyframes slice-cyan {
            0%, 100% { transform: translate(-5px, 0); clip-path: polygon(0 20%, 100% 20%, 100% 42%, 0 42%); }
            16.6%    { transform: translate(-12px, 0); clip-path: polygon(0 58%, 100% 58%, 100% 78%, 0 78%); }
            33.3%    { transform: translate(5px, 0); clip-path: polygon(0 8%, 100% 8%, 100% 28%, 0 28%); }
            50%      { transform: translate(-10px, 0); clip-path: polygon(0 40%, 100% 40%, 100% 68%, 0 68%); }
            66.6%    { transform: translate(7px, 0); clip-path: polygon(0 72%, 100% 72%, 100% 92%, 0 92%); }
            83.3%    { transform: translate(-4px, 0); clip-path: polygon(0 14%, 100% 14%, 100% 48%, 0 48%); }
        }

        @keyframes slice-pink {
            0%, 100% { transform: translate(5px, 0); clip-path: polygon(0 48%, 100% 48%, 100% 68%, 0 68%); }
            16.6%    { transform: translate(11px, 0); clip-path: polygon(0 14%, 100% 14%, 100% 34%, 0 34%); }
            33.3%    { transform: translate(-7px, 0); clip-path: polygon(0 68%, 100% 68%, 100% 88%, 0 88%); }
            50%      { transform: translate(9px, 0); clip-path: polygon(0 28%, 100% 28%, 100% 52%, 0 52%); }
            66.6%    { transform: translate(-5px, 0); clip-path: polygon(0 4%, 100% 4%, 100% 24%, 0 24%); }
            83.3%    { transform: translate(4px, 0); clip-path: polygon(0 44%, 100% 44%, 100% 82%, 0 82%); }
        }

        @keyframes band-left {
            0%, 100% { transform: translate(0, 0); }
            16.6% { transform: translate(-26px, 0); }
            33.3% { transform: translate(0, 0); }
            66.6% { transform: translate(-38px, 0); }
            83.3% { transform: translate(0, 0); }
        }

        @keyframes band-right {
            0%, 100% { transform: translate(0, 0); }
            25%   { transform: translate(28px, 0); }
            41.6% { transform: translate(0, 0); }
            75%   { transform: translate(36px, 0); }
            91.6% { transform: translate(0, 0); }
        }

        @keyframes band-left-deep {
            0%, 100% { transform: translate(0, 0); }
            12.5% { transform: translate(-20px, 0); }
            37.5% { transform: translate(0, 0); }
            58.3% { transform: translate(-32px, 0); }
            79.1% { transform: translate(0, 0); }
        }

        @keyframes streak-flash {
            0%, 100% { opacity: 1; transform: scaleX(1); }
            20%  { opacity: 0;   transform: scaleX(0.4); }
            40%  { opacity: 1;   transform: scaleX(1.15) translate(-6px, 0); }
            60%  { opacity: 0.2; transform: scaleX(0.7); }
            80%  { opacity: 0.9; transform: scaleX(1.05) translate(4px, 0); }
        }

        @keyframes pixel-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            25%  { opacity: 0.15; transform: scale(0.75) translate(2px, -2px); }
            50%  { opacity: 0.95; transform: scale(1.15) translate(-2px, 1px); }
            75%  { opacity: 0;    transform: scale(0.5); }
        }

        @keyframes err-shake {
            0%, 100% { transform: translate(0, 0); }
            14.2% { transform: translate(-4px, 0); }
            28.5% { transform: translate(4px, 0); }
            42.8% { transform: translate(0, 0); }
            71.4% { transform: translate(-6px, 0); }
            85.7% { transform: translate(0, 0); }
        }

        @keyframes err-cyan {
            0%, 100% { transform: translate(-3px, 0); }
            33% { transform: translate(-8px, 0); clip-path: inset(25% 0 50% 0); }
            66% { transform: translate(5px, 0);  clip-path: inset(60% 0 15% 0); }
        }

        @keyframes err-pink {
            0%, 100% { transform: translate(3px, 0); }
            33% { transform: translate(7px, 0);  clip-path: inset(12% 0 65% 0); }
            66% { transform: translate(-6px, 0); clip-path: inset(45% 0 30% 0); }
        }

        @keyframes sub-jitter {
            0%, 100% { transform: translate(0, 0); }
            16% { transform: translate(-3px, 0); }
            32% { transform: translate(3px, 0); }
            48% { transform: translate(0, 0); }
            80% { transform: translate(8px, 0); }
            88% { transform: translate(0, 0); }
        }

        @keyframes sub-cyan {
            0%, 100% { transform: translate(-2px, 0); }
            25% { transform: translate(-7px, 0); }
            75% { transform: translate(5px, 0); }
        }

        @keyframes sub-pink {
            0%, 100% { transform: translate(2px, 0); }
            30% { transform: translate(7px, 0); }
            70% { transform: translate(-6px, 0); }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>
    <div class="glitch-canvas">
        <!-- Píxeles y bloques de glitch idénticos a los del GIF de Tenor -->
        <div class="debris-container" aria-hidden="true">
            <span class="pixel-block p-1"></span>
            <span class="pixel-block alt p-2"></span>
            <span class="pixel-block p-3"></span>
            <span class="pixel-block alt p-4"></span>
            <span class="pixel-block p-5"></span>
            <span class="pixel-block alt p-6"></span>
            <span class="pixel-block p-7"></span>
            <span class="pixel-block alt p-8"></span>
            <span class="pixel-block p-9"></span>
            <span class="pixel-block alt p-10"></span>
            <span class="pixel-block p-11"></span>
            <span class="pixel-block alt p-12"></span>
            <span class="pixel-block p-13"></span>
            <span class="pixel-block alt p-14"></span>
            <span class="pixel-block p-15"></span>
            <span class="pixel-block alt p-16"></span>
            <span class="pixel-block p-17"></span>
        </div>

        <!-- 1. Texto Superior: ERROR (con corte dinámico) -->
        <div class="error-tag" data-text="@yield('header', 'ERROR')">
            @yield('header', 'ERROR')
        </div>

        <!-- 2. Número Central: 404 (o código HTTP) con franjas de corte y scan-streaks -->
        <div class="number-wrapper">
            <div class="glitch-digits" data-code="@yield('code', '404')">
                @yield('code', '404')
            </div>

            <!-- Bandas de desplazamiento horizontal en el cuerpo del número -->
            <div class="slice-band-1" aria-hidden="true">
                <div class="glitch-digits">@yield('code', '404')</div>
            </div>
            <div class="slice-band-2" aria-hidden="true">
                <div class="glitch-digits">@yield('code', '404')</div>
            </div>
            <div class="slice-band-3" aria-hidden="true">
                <div class="glitch-digits">@yield('code', '404')</div>
            </div>

            <!-- Barras horizontales que sobresalen en los momentos de glitch -->
            <span class="scan-streak streak-1" aria-hidden="true"></span>
            <span class="scan-streak streak-2" aria-hidden="true"></span>
            <span class="scan-streak streak-3" aria-hidden="true"></span>
        </div>

        <!-- 3. Subtítulo Inferior: PAGE NOT FOUND (o mensaje de estado) -->
        <div class="subtitle-tag" data-text="@yield('message', 'PAGE NOT FOUND')">
            @yield('message', 'PAGE NOT FOUND')
        </div>

        <!-- 4. Acción de Retorno Dinámica -->
        @if (trim($__env->yieldContent('code')) === '419')
            <a class="action-button" href="{{ request()->fullUrl() }}">
                Recargar Página
            </a>
        @else
            <a class="action-button" href="{{ url('/') }}">
                Volver al Inicio
            </a>
        @endif
    </div>
</body>
</html>