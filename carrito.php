<?php
// 1. Incluimos el header público y la conexión
// (Asegúrate de que la ruta sea correcta si moviste los archivos)
include 'includes/public_header.php';
include 'includes/conexion.php'; 

// 2. Lógica para leer el carrito de la sesión
$carrito = $_SESSION['carrito'] ?? [];
$total_carrito = 0;
$subtotal_carrito = 0;
$igv_carrito = 0;
$IGV_TASA = 0.18;
?>

<div class="mb-4 pb-2 border-bottom">
    <h1 class="h2">Tu Carrito de Compras</h1>
    <p>Revisa tus productos y finaliza tu pedido.</p>
</div>

<?php
if (isset($_SESSION['mensaje_carrito'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['mensaje_carrito'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['mensaje_carrito']);
}
if (isset($_SESSION['error_carrito'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error_carrito'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['error_carrito']);
}
?>

<div class="row">

    <?php if (empty($carrito)): ?>
        <div class="col-12 text-center">
            <div class="card shadow-sm border-0 p-4">
                <div class="card-body">
                    <h3 class="text-muted">Tu carrito está vacío</h3>
                    <p>Parece que aún no has añadido productos.</p>
                    <a href="tienda.php" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-left"></i> Volver a la Tienda
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <h5 class="mb-0">Tus Productos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 px-3" colspan="2">Producto</th>
                                    <th class="py-3 px-3">Precio</th>
                                    <th class="py-3 px-3" style="width: 120px;">Cantidad</th>
                                    <th class="py-3 px-3 text-end">Importe</th>
                                    <th class="py-3 px-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($carrito as $item): 
                                    $id = $item['id'];
                                    $importe = $item['precio'] * $item['cantidad'];
                                    $subtotal_carrito += $importe; // Sumamos al subtotal
                                    
                                    // Lógica de Imagen
                                    $imagen_url = "img/" . htmlspecialchars($item['imagen']);
                                    if (empty($item['imagen']) || !file_exists($imagen_url)) {
                                        $imagen_url = "https://www.lapurita.com/cdn/shop/collections/LAPURITA4229.jpg?v=1724384944&width=320";
                                    }
                                ?>
                                <tr>
                                    <td class="p-3" style="width: 80px;">
                                        <img src="<?php echo $imagen_url; ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>" class="img-fluid rounded" style="width: 80px; height: 80px; object-fit: cover;">
                                    </td>
                                    <td class="p-3">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['nombre']); ?></h6>
                                    </td>
                                    <td class="p-3">S/ <?php echo number_format($item['precio'], 2); ?></td>
                                    <td class="p-3">
                                        <form action="backend/gestionar_carrito.php" method="POST" class="d-flex">
                                            <input type="hidden" name="accion" value="actualizar">
                                            <input type="hidden" name="idproducto" value="<?php echo $id; ?>">
                                            <input type="number" name="cantidad" class="form-control form-control-sm" value="<?php echo $item['cantidad']; ?>" min="1" onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td class="p-3 text-end fw-bold">S/ <?php echo number_format($importe, 2); ?></td>
                                    <td class="p-3">
                                        <form action="backend/gestionar_carrito.php" method="POST">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="idproducto" value="<?php echo $id; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"
                                                    onclick="return confirm('¿Quitar este producto del carrito?');">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <a href="tienda.php" class="btn btn-outline-secondary mt-3">
                <i class="bi bi-arrow-left"></i> Seguir Comprando
            </a>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 100px;">
                <div class="card-header">
                    <h5 class="mb-0">Resumen del Pedido</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Calculamos totales finales
                    $igv_carrito = $subtotal_carrito * $IGV_TASA;
                    $total_carrito = $subtotal_carrito + $igv_carrito;
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal:</span>
                        <span class="fw-bold">S/ <?php echo number_format($subtotal_carrito, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">IGV (18%):</span>
                        <span class="fw-bold">S/ <?php echo number_format($igv_carrito, 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <h4 class="mb-0">Total:</h4>
                        <h4 class="mb-0 text-primary fw-bold">S/ <?php echo number_format($total_carrito, 2); ?></h4>
                    </div>

                    <div class="d-grid mt-4">
                        <a href="checkout.php" class="btn btn-primary btn-lg">
                            Finalizar Compra <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; // Fin del if (empty) ?>

</div>


<?php
// 3. Incluimos el footer público
include 'includes/public_footer.php';
?>