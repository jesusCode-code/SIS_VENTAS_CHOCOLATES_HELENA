<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Lógica del Carrito: Contar cuántos productos hay
$total_productos_carrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total_productos_carrito += $item['cantidad'];
    }
}

// Detectar página actual para resaltar en el menú
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chocolates Helena - Tradición Iqueña desde 1975</title>
    <meta name="description" content="Descubre los mejores chocolates, tejas y bombones artesanales de Ica. Chocolates Helena, tradición y calidad desde 1975.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6B4423;
            --secondary-color: #D4AF37;
            --accent-color: #8B4513;
            --light-bg: #FFF8F0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
        }
        
        /* Navbar Premium */
        .navbar-premium {
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF8F0 100%);
            box-shadow: 0 4px 20px rgba(107, 68, 35, 0.1);
            border-bottom: 2px solid var(--secondary-color);
            transition: all 0.3s ease;
        }
        
        .navbar-premium.scrolled {
            box-shadow: 0 6px 30px rgba(107, 68, 35, 0.15);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        .navbar-brand .brand-icon {
            font-size: 2rem;
            filter: drop-shadow(2px 2px 4px rgba(107, 68, 35, 0.3));
        }
        
        .nav-link {
            color: var(--primary-color) !important;
            font-weight: 500;
            position: relative;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 80%;
        }
        
        .nav-link.active {
            color: var(--accent-color) !important;
            font-weight: 600;
        }
        
        /* Botón Carrito Premium */
        .btn-cart {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(107, 68, 35, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-cart::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }
        
        .btn-cart:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(107, 68, 35, 0.4);
            color: white;
        }
        
        .cart-badge {
            background: linear-gradient(135deg, #DC3545 0%, #C82333 100%);
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Usuario logueado */
        .user-menu .nav-link {
            background: linear-gradient(135deg, rgba(107, 68, 35, 0.1) 0%, rgba(212, 175, 55, 0.1) 100%);
            border-radius: 20px;
            padding: 0.5rem 1rem !important;
        }
        
        .user-menu .nav-link:hover {
            background: linear-gradient(135deg, rgba(107, 68, 35, 0.15) 0%, rgba(212, 175, 55, 0.15) 100%);
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .btn-cart {
                width: 100%;
                margin-top: 1rem;
            }
            
            .nav-link::after {
                display: none;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-premium sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="brand-icon">🍫</span>
                <span>Chocolates Helena</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPublica">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarPublica">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                            <i class="bi bi-house-door me-1"></i>Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'tienda.php') ? 'active' : ''; ?>" href="tienda.php">
                            <i class="bi bi-shop me-1"></i>Tienda
                        </a>
                    </li>
                    
                    <?php if (isset($_SESSION['cliente_logueado']) && $_SESSION['cliente_logueado'] === true): ?>
                        <li class="nav-item user-menu">
                            <a class="nav-link <?php echo ($current_page == 'mi_cuenta.php') ? 'active' : ''; ?>" href="mi_cuenta.php">
                                <i class="bi bi-person-circle me-1"></i>Mi Cuenta
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cliente_logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>" href="login.php">
                                <i class="bi bi-person-lock me-1"></i>Iniciar Seccion
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <a href="carrito.php" class="btn btn-cart position-relative">
                    <i class="bi bi-cart-fill me-2"></i>
                    <span>Carrito</span>
                    <?php if ($total_productos_carrito > 0): ?>
                        <span class="cart-badge position-absolute top-0 start-100 translate-middle" id="contador-carrito">
                            <?php echo $total_productos_carrito; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-4 my-md-5">

<script>
// Efecto scroll en navbar
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar-premium');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});
</script>