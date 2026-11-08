<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #0a0a0f;
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Scanlines background effect */
        body::before {
            content: " ";
            display: block;
            position: absolute;
            top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.4) 50%),
                        linear-gradient(90deg, rgba(255, 0, 0, 0.05), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.05));
            z-index: 2;
            background-size: 100% 3px, 3px 100%;
            pointer-events: none;
        }

        /* Subtle radial glow */
        .glow-bg {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(236, 72, 153, 0.05) 50%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            pointer-events: none;
        }

        .container {
            position: relative;
            z-index: 3;
            text-align: center;
            padding: 2rem;
            max-width: 640px;
        }

        .glitch-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .glitch-title {
            font-size: clamp(4rem, 15vw, 9rem);
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            position: relative;
            color: #f8fafc;
            text-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
        }

        .glitch-title::before,
        .glitch-title::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            clip: rect(0, 0, 0, 0);
        }

        .glitch-title::before {
            left: 2px;
            text-shadow: -2px 0 #ef4444;
            animation: glitch-anim-1 2.5s infinite linear alternate-reverse;
        }

        .glitch-title::after {
            left: -2px;
            text-shadow: -2px 0 #06b6d4, 2px 2px #ec4899;
            animation: glitch-anim-2 3s infinite linear alternate-reverse;
        }

        .glitch-sub {
            font-size: clamp(1rem, 3vw, 1.4rem);
            font-weight: 700;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: #ef4444;
            margin-bottom: 1rem;
            text-shadow: 0 0 10px rgba(239, 68, 68, 0.6);
            display: inline-block;
            position: relative;
        }

        .desc {
            font-size: 1rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.85rem 2rem;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 25px rgba(99, 102, 241, 0.4);
            transition: all 0.25s ease;
        }

        .btn-home:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            box-shadow: 0 0 35px rgba(99, 102, 241, 0.7);
        }

        .btn-home:active {
            transform: translateY(0);
        }

        .badge-terminal {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.35rem 0.8rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.15);
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.8rem;
            color: #64748b;
        }

        @keyframes glitch-anim-1 {
            0% { clip: rect(24px, 9999px, 86px, 0); transform: skew(0.3deg); }
            10% { clip: rect(65px, 9999px, 14px, 0); transform: skew(0.5deg); }
            20% { clip: rect(12px, 9999px, 59px, 0); transform: skew(-0.2deg); }
            30% { clip: rect(82px, 9999px, 31px, 0); transform: skew(0.6deg); }
            40% { clip: rect(41px, 9999px, 98px, 0); transform: skew(-0.4deg); }
            50% { clip: rect(95px, 9999px, 6px, 0); transform: skew(0.1deg); }
            60% { clip: rect(18px, 9999px, 72px, 0); transform: skew(-0.3deg); }
            70% { clip: rect(54px, 9999px, 20px, 0); transform: skew(0.4deg); }
            80% { clip: rect(89px, 9999px, 45px, 0); transform: skew(-0.1deg); }
            90% { clip: rect(33px, 9999px, 81px, 0); transform: skew(0.2deg); }
            100% { clip: rect(70px, 9999px, 27px, 0); transform: skew(0deg); }
        }

        @keyframes glitch-anim-2 {
            0% { clip: rect(78px, 9999px, 32px, 0); transform: skew(-0.4deg); }
            10% { clip: rect(15px, 9999px, 94px, 0); transform: skew(0.2deg); }
            20% { clip: rect(92px, 9999px, 11px, 0); transform: skew(-0.5deg); }
            30% { clip: rect(37px, 9999px, 76px, 0); transform: skew(0.3deg); }
            40% { clip: rect(63px, 9999px, 28px, 0); transform: skew(-0.2deg); }
            50% { clip: rect(5px, 9999px, 88px, 0); transform: skew(0.6deg); }
            60% { clip: rect(71px, 9999px, 43px, 0); transform: skew(-0.1deg); }
            70% { clip: rect(22px, 9999px, 60px, 0); transform: skew(0.4deg); }
            80% { clip: rect(85px, 9999px, 19px, 0); transform: skew(-0.3deg); }
            90% { clip: rect(48px, 9999px, 93px, 0); transform: skew(0.1deg); }
            100% { clip: rect(10px, 9999px, 50px, 0); transform: skew(0deg); }
        }
    </style>
</head>
<body>
    <div class="glow-bg"></div>
    <div class="container">
        <div class="glitch-wrapper">
            <h1 class="glitch-title" data-text="404">404</h1>
        </div>
        <div>
            <span class="glitch-sub">ERROR! PAGE NOT FOUND</span>
        </div>
        <p class="desc">
            La ruta o recurso solicitado no existe, ha sido reubicada o la señal en la matriz fue interrumpida.
        </p>
        <a href="{{ url('/') }}" class="btn-home">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Volver al Inicio
        </a>
        <br>
        <span class="badge-terminal">STATUS_CODE: 404_NOT_FOUND // CAPITAL_GESTOR</span>
    </div>
</body>
</html>
