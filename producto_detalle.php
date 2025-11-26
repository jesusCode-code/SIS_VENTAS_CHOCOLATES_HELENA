<?php
// 1. Incluimos el header público y la conexión
include 'includes/public_header.php';
include 'includes/conexion.php';

// 2. Validar que tengamos un ID
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: tienda.php");
    exit;
}
$idproducto = $_GET['id'];

// 3. Obtener los datos COMPLETOS del producto (con JOINs)
$sql_producto = "
    SELECT 
        p.IDPRODUCTO, p.NOMPRODUCTO, p.DESCRIPCION, p.PRECIO AS PRECIO_ORIGINAL, p.STOCK, p.IMAGEN_URL,
        c.NOMCATEGORIA,
        pr.PORCENTAJE_DESC, pr.NOMPROMOCION
    FROM PRODUCTO p
    LEFT JOIN CATEGORIA_PRODUCTO c ON p.IDCATEGORIA = c.IDCATEGORIA
    LEFT JOIN PROMOCION pr ON p.IDPRODUCTO = pr.IDPRODUCTO 
                          AND pr.ESTADO = '1' 
                          AND GETDATE() BETWEEN pr.FECHA_INICIO AND pr.FECHA_FIN
    WHERE p.IDPRODUCTO = ? AND p.ESTADO = '1'";

$params_producto = array($idproducto);
$stmt = sqlsrv_query($conn, $sql_producto, $params_producto);
if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
$producto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$producto) {
    header("Location: tienda.php");
    exit;
}

// 4. Lógica de Precios
$precio_original = (float)$producto['PRECIO_ORIGINAL'];
$porcentaje_desc = (float)$producto['PORCENTAJE_DESC'];
$precio_final = $precio_original;
$es_oferta = $porcentaje_desc > 0;
if ($es_oferta) {
    $precio_final = $precio_original * (1 - ($porcentaje_desc / 100));
}

// 5. Lógica de Imagen
$imagen_url_base = "img/" . htmlspecialchars($producto['IMAGEN_URL']);
if (empty($producto['IMAGEN_URL']) || !file_exists($imagen_url_base)) {
    $imagen_url_base = "https://www.lapurita.com/cdn/shop/collections/LAPURITA4229.jpg?v=1724384944&width=320" . urlencode(htmlspecialchars($producto['NOMPRODUCTO']));
}
?>

<?php
if (isset($_SESSION['mensaje_carrito'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['mensaje_carrito'] . 
         '<a href="carrito.php" class="btn btn-sm btn-success ms-3">Ver Carrito</a>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['mensaje_carrito']);
}
?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4 p-md-5">
        <div class="row g-5">
            
            <div class="col-lg-6">
                <?php if ($es_oferta): ?>
                    <span class="badge bg-danger position-absolute top-0 start-0 m-3 fs-5">
                        <?php echo number_format($porcentaje_desc, 0); ?>% OFF
                    </span>
                <?php endif; ?>
                <img src="<?php echo $imagen_url_base; ?>?v=<?php echo time(); ?>" class="img-fluid rounded shadow-lg" alt="<?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?>">
            </div>

            <div class="col-lg-6 d-flex flex-column">
                
                <span class="text-muted text-uppercase small"><?php echo htmlspecialchars($producto['NOMCATEGORIA']); ?></span>
                <h1 class="display-5 fw-bold text-primary"><?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?></h1>
                
                <div class="mb-3">
                    <?php if ($es_oferta): ?>
                        <h2 class="display-4 fw-bold text-danger">S/ <?php echo number_format($precio_final, 2); ?></h2>
                        <h4 class="text-muted text-decoration-line-through ms-2">S/ <?php echo number_format($precio_original, 2); ?></h4>
                        <span class="badge bg-danger-subtle text-danger-emphasis"><?php echo htmlspecialchars($producto['NOMPROMOCION']); ?></span>
                    <?php else: ?>
                        <h2 class="display-4 fw-bold text-dark">S/ <?php echo number_format($precio_final, 2); ?></h2>
                    <?php endif; ?>
                </div>
                
                <p class="lead text-muted mb-4"><?php echo nl2br(htmlspecialchars($producto['DESCRIPCION'])); ?></p>
                
                <div class_mt-auto">
                    <?php if ($producto['STOCK'] > 0): ?>
                        <p class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Disponible en Stock (<?php echo $producto['STOCK']; ?> unid.)</p>
                    <?php else: ?>
                        <p class="text-danger fw-bold"><i class="bi bi-x-circle-fill"></i> Agotado</p>
                    <?php endif; ?>
                </div>
                
                <form action="backend/gestionar_carrito.php" method="POST" class="mt-4">
                    <input type="hidden" name="accion" value="agregar">
                    <input type="hidden" name="idproducto" value="<?php echo $producto['IDPRODUCTO']; ?>">
                    <input type="hidden" name="nombre_producto" value="<?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?>">
                    <input type="hidden" name="precio_final" value="<?php echo $precio_final; ?>">
                    <input type="hidden" name="imagen_url" value="<?php echo htmlspecialchars($producto['IMAGEN_URL']); ?>">
                    <input type="hidden" name="pagina_anterior" value="<?php echo $_SERVER['REQUEST_URI']; ?>">

                    <div class="row align-items-end g-2">
                        <div class="col-4">
                            <label for="cantidad" class="form-label">Cantidad:</label>
                            <input type="number" id="cantidad" name="cantidad" class="form-control" value="1" min="1" max="<?php echo $producto['STOCK']; ?>" <?php echo ($producto['STOCK'] == 0) ? 'disabled' : ''; ?>>
                        </div>
                        <div class="col-8 d-grid">
                            <button class="btn btn-primary btn-lg" type="submit" <?php echo ($producto['STOCK'] == 0) ? 'disabled' : ''; ?>>
                                <i class="bi bi-cart-plus-fill me-1"></i> Añadir al Carrito
                            </button>
                        </div>
                    </div>
                </form>

                <a href="tienda.php" class="btn btn-outline-secondary mt-3">← Volver a la tienda</a>
            </div>
        </div>
    </div>
</div>

<?php
// 3. Incluimos el footer público
include 'includes/public_footer.php';
?>