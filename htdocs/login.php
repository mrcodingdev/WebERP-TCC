<?php
session_start();
require_once 'db.php';

// Se já está logado, redireciona para o painel
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_perfil']) && $_SESSION['user_perfil'] == 'caixa') {
        header("Location: venda.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $user['password'] === $password) {
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_name']   = $user['username'];
        $_SESSION['user_perfil'] = $user['perfil'];

        if ($user['perfil'] == 'caixa') {
            header("Location: venda.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $erro = "Credenciais inválidas. Tente novamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockControl ERP - Acesso Restrito</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link href="assets/css/inter.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green-neon:   #39e07b;
            --green-mid:    #1db954;
            --card-bg:      rgba(8, 18, 12, 0.88);
            --border-glow:  rgba(57, 224, 123, 0.50);
            --input-bg:     rgba(255,255,255,0.03);
            --input-border: rgba(57, 224, 123, 0.22);
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: #050f09;
            overflow: hidden;
        }

        /* ── Canvas de ondas (preenchido por JS) ── */
        #wave-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        /* ── Vinheta escura nas bordas (tipo SynexAura) ── */
        .vignette {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background: radial-gradient(ellipse at center,
                transparent 30%,
                rgba(0, 15, 40, 0.50) 70%,
                rgba(0, 3, 12, 0.88) 100%
            );
        }

        /* ── Wrapper central ── */
        .login-wrapper {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        /* ── Card principal ── */
        .login-card {
            position: relative;
            width: 100%;
            max-width: 420px;
            padding: 44px 40px 36px;
            background: transparent;
            border-radius: 4px;
            box-shadow:
                0 0 35px rgba(57, 224, 123, 0.20),
                0 0 90px rgba(57, 224, 123, 0.07);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            animation: cardIn 0.55s cubic-bezier(.22,.68,0,1.2) both;
            overflow: hidden;
            z-index: 1; /* contexto para the pseudo backgrounds */
        }

        /* ── Animação de Borda (Luz Circulante) ── */
        .login-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(
                transparent 60%,
                rgba(57, 224, 123, 0.10) 85%,
                var(--green-neon) 100%
            );
            animation: spinBorder 4s linear infinite;
            z-index: -2;
        }

        .login-card::after {
            content: '';
            position: absolute;
            inset: 1px; /* 1px edge width for the border light to shine through */
            border-radius: 3px;
            background: var(--card-bg);
            box-shadow: inset 0 0 60px rgba(0,0,0,0.5);
            z-index: -1;
        }

        @keyframes spinBorder {
            0%   { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(22px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── Título ── */
        .brand-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 5px;
            text-transform: uppercase;
            background: linear-gradient(130deg, #ffffff 0%, var(--green-neon) 55%, #a8ffcb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 12px rgba(57, 224, 123, 0.50));
            margin-bottom: 4px;
        }

        .brand-subtitle {
            text-align: center;
            color: rgba(255,255,255,0.30);
            font-size: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 38px;
        }

        /* ── Inputs ghost (estilo SynexAura) ── */
        .field-group {
            position: relative;
            margin-bottom: 14px;
        }

        .field-group input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 3px;
            color: rgba(255,255,255,0.85);
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            text-align: center;
            padding: 15px 44px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .field-group input::placeholder {
            color: rgba(255,255,255,0.22);
            letter-spacing: 2.5px;
        }

        .field-group input:focus {
            border-color: var(--green-neon);
            background: rgba(57, 224, 123, 0.05);
            box-shadow: 0 0 0 3px rgba(57, 224, 123, 0.10), 0 0 22px rgba(57, 224, 123, 0.08);
        }

        .field-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(57, 224, 123, 0.15); /* bem mais escuros */
            font-size: 13px;
            pointer-events: none;
        }

        /* ── Botão outline neon ── */
        .btn-enter {
            width: 100%;
            margin-top: 10px;
            padding: 15px;
            background: transparent;
            border: 1px solid var(--green-neon);
            border-radius: 3px;
            color: var(--green-neon);
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.22s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-enter::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--green-neon);
            opacity: 0;
            transition: opacity 0.22s;
        }

        .btn-enter:hover {
            box-shadow: 0 0 22px rgba(57,224,123,0.45), 0 0 50px rgba(57,224,123,0.12);
            transform: translateY(-1px);
        }
        .btn-enter:hover::before  { opacity: 0.10; }
        .btn-enter:active::before { opacity: 0.20; }
        .btn-enter span { position: relative; z-index: 1; }

        /* ── Erro ── */
        .error-msg {
            background: rgba(220, 38, 38, 0.10);
            border: 1px solid rgba(220, 38, 38, 0.30);
            border-radius: 3px;
            color: #fca5a5;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 11px 14px;
            margin-bottom: 16px;
            text-align: center;
        }

        /* ── Hint TCC ── */
        .tcc-hint {
            margin-top: 26px;
            padding: 13px 16px;
            background: rgba(57, 224, 123, 0.03);
            border: 1px solid rgba(57, 224, 123, 0.10);
            border-radius: 3px;
            text-align: center;
        }

        .tcc-hint .label {
            font-size: 9px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--green-neon);
            opacity: 0.8;
            margin-bottom: 8px;
        }

        .tcc-hint .cred-row {
            font-size: 11px;
            color: rgba(255,255,255,0.35);
            letter-spacing: 0.5px;
        }

        .tcc-hint code {
            background: rgba(57, 224, 123, 0.10);
            color: #a8ffcb;
            padding: 1px 6px;
            border-radius: 2px;
            font-size: 10px;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

    <!-- Canvas para as ondas animadas (JavaScript puro, sem CDN) -->
    <canvas id="wave-canvas"></canvas>

    <!-- Vinheta escura nas bordas -->
    <div class="vignette"></div>

    <!-- Card de login -->
    <div class="login-wrapper">
        <div class="login-card">

            <div class="brand-title">StockControl</div>
            <div class="brand-subtitle">Sistema de Gestão ERP</div>

            <?php if ($erro): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="off">
                <div class="field-group">
                    <i class="fas fa-user field-icon"></i>
                    <input type="text" name="username" placeholder="Usuário" required autofocus>
                </div>

                <div class="field-group">
                    <i class="fas fa-lock field-icon"></i>
                    <input type="password" name="password" placeholder="Senha" required>
                </div>

                <button type="submit" class="btn-enter">
                    <span>Entrar &nbsp;<i class="fas fa-arrow-right"></i></span>
                </button>
            </form>

            <div class="tcc-hint">
                <div class="label"><i class="fas fa-info-circle me-1"></i> Acesso Banca Avaliadora TCC</div>
                <div class="cred-row">Gestor: <code>admin</code> &nbsp;/&nbsp; PDV: <code>caixa</code></div>
                <div class="cred-row" style="margin-top:4px;">Senha Padrão: <code>1234</code></div>
            </div>

        </div>
    </div>

    <script>
    // ── Ondas animadas com Canvas 2D (100% offline, sem bibliotecas) ──
    (function () {
        const canvas = document.getElementById('wave-canvas');
        const ctx    = canvas.getContext('2d');

        // Cores das ondas (verde neon em vários alphas)
        const waves = [
            { color: 'rgba(57, 224, 123, 0.40)', speed: 0.00015, amp: 70,  offset: 0,    yBase: 0.42 },
            { color: 'rgba(29, 185, 84,  0.30)', speed: 0.00012, amp: 90,  offset: 2.1,  yBase: 0.55 },
            { color: 'rgba(57, 224, 123, 0.25)', speed: 0.00020, amp: 55,  offset: 4.2,  yBase: 0.35 },
            { color: 'rgba(100, 255, 160,0.50)', speed: 0.00010, amp: 110, offset: 1.0,  yBase: 0.65 },
            { color: 'rgba(57, 224, 123, 0.20)', speed: 0.00014, amp: 80,  offset: 3.5,  yBase: 0.28 },
            { color: 'rgba(29, 185, 84,  0.15)', speed: 0.00018, amp: 60,  offset: 5.5,  yBase: 0.72 },
        ];

        let t = 0;

        function resize() {
            canvas.width  = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        function drawWave(w, time) {
            const W = canvas.width;
            const H = canvas.height;
            const yBase = H * w.yBase;

            ctx.beginPath();
            ctx.moveTo(0, yBase);

            // Desenhamos a onda ponto a ponto usando seno com dois harmônicos
            const steps = 180;
            for (let i = 0; i <= steps; i++) {
                const x = (i / steps) * W;
                const phase = (x / W) * Math.PI * 2;
                const y = yBase
                    + Math.sin(phase + time * w.speed * 1.5 + w.offset) * w.amp
                    + Math.sin(phase * 2.3 + time * w.speed * 0.8 + w.offset * 1.5) * (w.amp * 0.35);
                if (i === 0) ctx.moveTo(x, y);
                else         ctx.lineTo(x, y);
            }

            ctx.shadowBlur = 15;
            ctx.shadowColor = w.color;
            ctx.strokeStyle = w.color;
            ctx.lineWidth   = 2.5;
            ctx.stroke();
        }

        function loop(timestamp) {
            t = timestamp;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            waves.forEach(w => drawWave(w, t));
            requestAnimationFrame(loop);
        }

        window.addEventListener('resize', resize);
        resize();
        requestAnimationFrame(loop);
    })();
    </script>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>