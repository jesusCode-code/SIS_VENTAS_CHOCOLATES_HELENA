<?php
// 1. Iniciar Sesión y Conexión
session_start();
include '../includes/conexion.php';

// 2. Seguridad del Cliente
if (!isset($_SESSION['cliente_logueado']) || $_SESSION['cliente_logueado'] !== true) {
    header("Location: ../login.php");
    exit;
}

// 3. Validar que sea POST y que el carrito no esté vacío
if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($_SESSION['carrito'])) {
    header("Location: ../carrito.php");
    exit;
}

// 4. Recoger datos
$carrito = $_SESSION['carrito'];
$idusuario_logueado = $_SESSION['idusuario_cliente']; // ID del Usuario (quién hizo la acción)

// ===================================
// ¡¡¡CAMBIO CLAVE AQUÍ!!!
// El IDCLIENTE ahora viene del formulario, no de la sesión
// ===================================
$idcliente_facturar = $_POST['idcliente_facturar']; 
$idMetodoPago = $_POST['idMetodoPago'];

// 5. Recalcular Totales (Seguridad)
$subtotal = 0;
$IGV_TASA = 0.18;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}
$igv = $subtotal * $IGV_TASA;
$total = $subtotal + $igv;


// 6. --- INICIAR LA TRANSACCIÓN ---
if (sqlsrv_begin_transaction($conn) === false) {
    $_SESSION['error_checkout'] = "Error crítico: No se pudo iniciar la transacción.";
    header("Location: ../checkout.php"); exit;
}

try {
    // 7. --- PASO 1: INSERTAR EN LA TABLA 'VENTA' ---
    $sqlVenta = "INSERT INTO VENTA (IDCLIENTE, IDUSUARIO, IDMETODOPAGO, FECHAVENTA, SUBTOTAL, IGV, TOTAL, ESTADO) 
                 OUTPUT INSERTED.IDVENTA 
                 VALUES (?, ?, ?, GETDATE(), ?, ?, ?, '1')";
    // Usamos $idcliente_facturar y $idusuario_logueado
    $paramsVenta = array($idcliente_facturar, $idusuario_logueado, $idMetodoPago, $subtotal, $igv, $total);
    
    $stmtVenta = sqlsrv_query($conn, $sqlVenta, $paramsVenta);
    if ($stmtVenta === false) { throw new Exception("Error al guardar la venta principal."); }

    // 8. --- PASO 2: OBTENER EL ID DE LA VENTA RECIÉN CREADA ---
    $rowId = sqlsrv_fetch_array($stmtVenta, SQLSRV_FETCH_ASSOC);
    $idVenta = $rowId['IDVENTA'];
    if (!$idVenta) { throw new Exception("Error crítico al obtener el ID de la nueva venta."); }

    // 9. --- PASO 3: INSERTAR DETALLE Y ACTUALIZAR STOCK ---
    foreach ($carrito as $item) {
        
        // (A) Insertar en DETALLE_VENTA
        $sqlDetalle = "INSERT INTO DETALLE_VENTA (IDVENTA, IDPRODUCTO, CANTIDAD, PRECIO_UNITARIO, SUBTOTAL, ESTADO) 
                       VALUES (?, ?, ?, ?, ?, '1')";
        $paramsDetalle = array($idVenta, $item['id'], $item['cantidad'], $item['precio'], $item['precio'] * $item['cantidad']);
        $stmtDetalle = sqlsrv_query($conn, $sqlDetalle, $paramsDetalle);
        if ($stmtDetalle === false) { throw new Exception("Error al guardar el detalle del producto."); }

        // (B) Actualizar el STOCK
        $sqlStock = "UPDATE PRODUCTO SET STOCK = STOCK - ? 
                     WHERE IDPRODUCTO = ? AND STOCK >= ?";
        $paramsStock = array($item['cantidad'], $item['id'], $item['cantidad']);
        $stmtStock = sqlsrv_query($conn, $sqlStock, $paramsStock);
        if ($stmtStock === false) { throw new Exception("Error al actualizar el stock."); }
        
        $rows_affected = sqlsrv_rows_affected($stmtStock);
        if ($rows_affected == 0) {
            throw new Exception("Stock insuficiente para: " . htmlspecialchars($item['nombre']) . ". El pedido ha sido cancelado.");
        }
    }

    // 10. --- COMMIT ---
    sqlsrv_commit($conn);

    // 11. Limpiar el carrito y guardar datos de éxito
    unset($_SESSION['carrito']);
    $_SESSION['pedido_exitoso_id'] = $idVenta;
    $_SESSION['pedido_exitoso_total'] = $total;
    
    header("Location: ../pedido_confirmado.php");
    exit;

} catch (Exception $e) {
    // 12. --- ROLLBACK ---
    sqlsrv_rollback($conn);
    $_SESSION['error_checkout'] = "Error al procesar el pedido: " . $e->getMessage();
    header("Location: ../checkout.php");
    exit;
}
?>