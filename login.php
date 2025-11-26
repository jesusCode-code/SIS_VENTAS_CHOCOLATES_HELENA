<?php
include 'includes/login_header.php';
?>

<div class="login-card" style="width: 100%; max-width: 1000px;">
    <div class="row g-0">
        
        <!-- Panel Lateral Izquierdo -->
        <div class="col-lg-5 d-none d-lg-flex">
            <div class="login-left-panel">
                <div class="panel-content">
                    <div class="brand-section">
                        <div class="brand-icon-large">🍫</div>
                        <h2 class="brand-title">Chocolates Helena</h2>
                        <p class="brand-tagline">La tradición Iqueña en cada bocado</p>
                    </div>
                    
                    <div class="features-list">
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>50+ años de tradición</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Productos 100% artesanales</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>La mejor calidad garantizada</span>
                        </div>
                    </div>
                    
                    <div class="decorative-box">
                        <i class="bi bi-box2-heart"></i>
                    </div>
                </div>
                
                <div class="panel-decoration">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path fill="rgba(255,255,255,0.1)" d="M43.3,-58.9C54.8,-49.1,62.3,-34.8,66.4,-19.2C70.5,-3.6,71.2,13.3,65.9,28C60.6,42.7,49.3,55.2,35.8,61.9C22.3,68.6,6.6,69.5,-8.7,67.8C-24,66.1,-39,61.8,-51.4,53.4C-63.8,45,-73.6,32.5,-76.9,18.5C-80.2,4.5,-77,-11,-68.9,-23.8C-60.8,-36.6,-47.8,-46.7,-34.3,-55.9C-20.8,-65.1,-6.8,-73.4,5.6,-71.8C18,-70.2,31.8,-68.7,43.3,-58.9Z" transform="translate(100 100)" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Panel Derecho - Formulario -->
        <div class="col-lg-7">
            <div class="login-form-panel">
                <div class="form-header">
                    <h3 class="form-title">¡Bienvenido de vuelta! 👋</h3>
                    <p class="form-subtitle">Ingresa tus credenciales para continuar</p>
                </div>

                <?php
                if (isset($_SESSION['error_login'])) {
                    echo '<div class="alert alert-danger alert-dismissible fade show custom-alert" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>
                                <strong>Error de acceso</strong><br>
                                ' . $_SESSION['error_login'] . '
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                    unset($_SESSION['error_login']);
                }
                ?>

                <form action="backend/validar_login.php" method="POST" class="login-form">
                    <div class="form-group-modern">
                        <label for="logeo" class="form-label-modern">
                            <i class="bi bi-person-circle me-2"></i>Usuario
                        </label>
                        <div class="input-wrapper">
                            <i class="bi bi-person input-icon"></i>
                            <input type="text" 
                                   id="logeo" 
                                   name="logeo" 
                                   class="form-control-modern" 
                                   required 
                                   placeholder="Ingresa tu usuario"
                                   autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group-modern">
                        <label for="clave" class="form-label-modern">
                            <i class="bi bi-shield-lock me-2"></i>Contraseña
                        </label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" 
                                   id="clave" 
                                   name="clave" 
                                   class="form-control-modern" 
                                   required 
                                   placeholder="Ingresa tu contraseña"
                                   autocomplete="current-password">
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <span>Iniciar Sesión</span>
                        <i class="bi bi-arrow-right-circle ms-2"></i>
                    </button>
                </form>

                <div class="form-footer">
                    <div class="divider">
                        <span>o</span>
                    </div>
                    
                    <p class="register-link">
                        ¿Aún no tienes una cuenta?
                        <a href="cliente_registro.php">
                            Regístrate aquí <i class="bi bi-arrow-right"></i>
                        </a>
                    </p>

                    <a href="index.php" class="back-home">
                        <i class="bi bi-house-door me-2"></i>Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/login.css">

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('clave');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        }
    }
</script>

<?php
include 'includes/login_footer.php';
?>