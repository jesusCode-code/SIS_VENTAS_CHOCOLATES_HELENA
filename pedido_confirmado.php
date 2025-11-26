<?php
// 1. INICIAMOS LA SESIÓN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Seguridad: Verificamos que VENGAN de un pedido exitoso
if (!isset($_SESSION['pedido_exitoso_id'])) {
    header("Location: tienda.php"); // Si recargan la página, los mandamos a la tienda
    exit;
}

// 3. Obtenemos los datos del pedido y los limpiamos de la sesión
$pedido_id = $_SESSION['pedido_exitoso_id'];
$pedido_total = $_SESSION['pedido_exitoso_total'];
$nombre_cliente = $_SESSION['nombre_cliente'];

unset($_SESSION['pedido_exitoso_id']);
unset($_SESSION['pedido_exitoso_total']);

// 4. Incluimos el header público
include 'includes/public_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 text-center">
        <div class="card shadow-sm border-0 p-4 p-md-5">
            <div class="card-body">
                
                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                
                <h1 class="h2 mt-3">¡Gracias por tu compra, <?php echo htmlspecialchars($nombre_cliente); ?>!</h1>
                <p class="lead text-muted">Tu pedido ha sido confirmado exitosamente.</p>
                <hr>
                
                <div class="alert alert-success">
                    Tu número de pedido es: <strong class="fs-5">#<?php echo $pedido_id; ?></strong>
                    <br>
                    El total pagado fue: <strong>S/ <?php echo number_format($pedido_total, 2); ?></strong>
                </div>

                <p>Puedes revisar el estado de tu pedido en cualquier momento desde "Mi Cuenta".</p>
                
                <div class="mt-4">
                    <a href="tienda.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-arrow-left"></i> Seguir Comprando
                    </a>
                    <a href="mi_cuenta.php?seccion=historial" class="btn btn-primary">
                        Ir a Mis Pedidos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 5. Incluimos el footer público
include 'includes/public_footer.php';
?>