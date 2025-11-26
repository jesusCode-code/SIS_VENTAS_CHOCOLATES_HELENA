<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Chocolates Helena</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary: #6B4423;
            --secondary: #D4AF37;
            --accent: #8B4513;
            --dark: #3E2723;
            --light-bg: #FFF8F0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* Fondo animado con partículas */
        .login-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #FFF8F0 0%, #F5E6D3 50%, #E8D4B8 100%);
            z-index: -2;
        }

        .login-background::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(107, 68, 35, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(139, 69, 19, 0.05) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -50px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        /* Patrón decorativo */
        .login-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(107, 68, 35, 0.03) 35px, rgba(107, 68, 35, 0.03) 70px);
            z-index: -1;
        }

        /* Wrapper principal */
        .login-wrapper {
            min-height: 100vh;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }

        /* Elementos flotantes decorativos */
        .floating-element {
            position: absolute;
            opacity: 0.1;
            pointer-events: none;
            z-index: 0;
        }

        .floating-chocolate-1 {
            top: 10%;
            left: 10%;
            font-size: 4rem;
            animation: floatSlow 15s ease-in-out infinite;
        }

        .floating-chocolate-2 {
            top: 70%;
            right: 15%;
            font-size: 3rem;
            animation: floatSlow 18s ease-in-out infinite 2s;
        }

        .floating-chocolate-3 {
            bottom: 15%;
            left: 15%;
            font-size: 3.5rem;
            animation: floatSlow 20s ease-in-out infinite 4s;
        }

        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(10deg); }
        }

        /* Logo flotante superior */
        .logo-header {
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10;
        }

        .logo-header .logo-icon {
            font-size: 2.5rem;
            filter: drop-shadow(0 4px 10px rgba(107, 68, 35, 0.3));
        }

        .logo-header .logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .logo-header {
                position: static;
                transform: none;
                justify-content: center;
                margin-bottom: 2rem;
            }

            .floating-element {
                display: none;
            }

            .login-wrapper {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-background"></div>
    
    <!-- Logo Header -->
    <div class="logo-header">
        <span class="logo-icon">🍫</span>
        <span class="logo-text">Chocolates Helena</span>
    </div>

    <!-- Elementos decorativos flotantes -->
    <div class="floating-element floating-chocolate-1">🍫</div>
    <div class="floating-element floating-chocolate-2">🍬</div>
    <div class="floating-element floating-chocolate-3">🎁</div>
    
    <div class="login-wrapper">