<?php
// 1. INICIAMOS LA SESIÓN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Seguridad del Cliente
if (!isset($_SESSION['cliente_logueado']) || $_SESSION['cliente_logueado'] !== true) {
    $_SESSION['error_login'] = "Por favor, inicia sesión para finalizar tu compra.";
    header("Location: login.php");
    exit;
}

// 3. Incluimos el header PÚBLICO y la conexión
include 'includes/public_header.php';
include 'includes/conexion.php'; 

// 4. Verificar que el carrito NO esté vacío
$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    header("Location: carrito.php");
    exit;
}

// 5. IDs de Sesión
$idcliente_actual = $_SESSION['idcliente']; // El IDCLIENTE personal
$idusuario_actual = $_SESSION['idusuario_cliente'];

// ==================================================
// ¡NUEVA LÓGICA! BUSCAR TODOS LOS PERFILES DE CLIENTE
// ==================================================

// Obtenemos el IDPERSONA del usuario logueado
$sql_get_persona = "SELECT IDPERSONA FROM USUARIO WHERE IDUSUARIO = ?";
$stmt_get_persona = sqlsrv_query($conn, $sql_get_persona, array($idusuario_actual));
$idpersona_actual = sqlsrv_fetch_array($stmt_get_persona, SQLSRV_FETCH_ASSOC)['IDPERSONA'];

$perfiles_cliente = [];

// 1. Añadir el perfil PERSONAL
$perfiles_cliente[$idcliente_actual] = $_SESSION['nombre_cliente'] . " (Personal)";

// 2. Buscar perfiles de EMPRESA (usando EMPRESA_CONTACTO)
$sql_empresas = "
    SELECT c.IDCLIENTE, e.RAZON_SOCIAL
    FROM EMPRESA_CONTACTO ec
    JOIN EMPRESA e ON ec.IDEMPRESA = e.IDEMPRESA
    JOIN CLIENTE c ON e.IDEMPRESA = c.IDEMPRESA
    WHERE ec.IDPERSONA = ? AND ec.ESTADO = '1' AND c.ESTADO = '1'";

$stmt_empresas = sqlsrv_query($conn, $sql_empresas, array($idpersona_actual));
if ($stmt_empresas) {
    while ($row_emp = sqlsrv_fetch_array($stmt_empresas, SQLSRV_FETCH_ASSOC)) {
        // Añadimos la empresa a la lista de perfiles
        $perfiles_cliente[$row_emp['IDCLIENTE']] = $row_emp['RAZON_SOCIAL'] . " (Empresa)";
    }
}

// 6. Cargar Métodos de Pago
$sql_metodos = "SELECT IDMETODOPAGO, NOMMETODO FROM METODO_PAGO WHERE ESTADO = '1' ORDER BY NOMMETODO";
$stmt_metodos = sqlsrv_query($conn, $sql_metodos);
$metodos_pago = [];
while ($row = sqlsrv_fetch_array($stmt_metodos, SQLSRV_FETCH_ASSOC)) {
    $metodos_pago[] = $row;
}

// 7. Calcular Totales
$subtotal_carrito = 0;
$IGV_TASA = 0.18;
foreach ($carrito as $item) {
    $subtotal_carrito += $item['precio'] * $item['cantidad'];
}
$igv_carrito = $subtotal_carrito * $IGV_TASA;
$total_carrito = $subtotal_carrito + $igv_carrito;
?>

<div class="mb-4 pb-2 border-bottom">
    <h1 class="h2">Finalizar Compra</h1>
    <p>Revisa tu pedido y confirma el pago.</p>
</div>

<?php
if (isset($_SESSION['error_checkout'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error_checkout'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['error_checkout']);
}
?>

<div class="row g-5">
    
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header"><h5 class="mb-0">Resumen de tu Pedido</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            <?php foreach ($carrito as $item): ?>
                            <tr class="align-middle">
                                <td class="p-3" style="width: 80px;">
                                    <?php
                                    $imagen_url = "img/" . htmlspecialchars($item['imagen']);
                                    if (empty($item['imagen']) || !file_exists($imagen_url)) { $imagen_url = "https://www.lapurita.com/cdn/shop/collections/LAPURITA4229.jpg?v=1724384944&width=320"; }
                                    ?>
                                    <img src="<?php echo $imagen_url; ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>" class="img-fluid rounded">
                                </td>
                                <td class="p-3"><h6 class="mb-0"><?php echo htmlspecialchars($item['nombre']); ?></h6><small class="text-muted">Cantidad: <?php echo $item['cantidad']; ?></small></td>
                                <td class="p-3 text-end fw-bold">S/ <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card shadow-sm border-0 sticky-top" style="top: 100px;">
            <div class="card-header"><h5 class="mb-0">Confirmar Pago</h5></div>
            <div class="card-body">
                <form action="backend/procesar_checkout.php" method="POST">
                    
                    <div class="mb-3">
                        <label for="idcliente_facturar" class="form-label fw-bold">Facturar a:</label>
                        <select id="idcliente_facturar" name="idcliente_facturar" class="form-select" required>
                            <option value="">-- Seleccione a quién facturar --</option>
                            <?php foreach ($perfiles_cliente as $id_perfil => $nombre_perfil): ?>
                                <option value="<?php echo $id_perfil; ?>">
                                    <?php echo htmlspecialchars($nombre_perfil); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="idMetodoPago" class="form-label">Método de Pago:</label>
                        <select id="idMetodoPago" name="idMetodoPago" class="form-select" required>
                            <?php foreach ($metodos_pago as $metodo): ?>
                                <option value="<?php echo $metodo['IDMETODOPAGO']; ?>">
                                    <?php echo htmlspecialchars($metodo['NOMMETODO']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal:</span><span class="fw-bold">S/ <?php echo number_format($subtotal_carrito, 2); ?></span></div>
                    <div class="d-flex justify-content-between mb-3"><span class="text-muted">IGV (18%):</span><span class="fw-bold">S/ <?php echo number_format($igv_carrito, 2); ?></span></div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center"><h4 class="mb-0">Total:</h4><h4 class="mb-0 text-primary fw-bold">S/ <?php echo number_format($total_carrito, 2); ?></h4></div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Pagar y Realizar Pedido</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// 3. Incluimos el footer público
include 'includes/public_footer.php';
?>