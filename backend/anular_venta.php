<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Validar que tengamos un ID por GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_venta_anulada'] = "Error: No se especificó ninguna venta.";
    header("Location: ../listado_ventas.php?pagina=1");
    exit;
}
$idventa = $_GET['id'];

// ===================================
// LÓGICA DE REDIRECCIÓN (Mantiene la página y el filtro de búsqueda)
// ===================================
$pagina_actual = $_GET['pagina'] ?? 1; 
$search_term = $_GET['search'] ?? '';
$search_url_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
$redirect_url = "../listado_ventas.php?pagina=" . $pagina_actual . $search_url_param;
// ===================================


// 3. --- INICIAR LA TRANSACCIÓN ---
if (sqlsrv_begin_transaction($conn) === false) {
    $_SESSION['error_venta_anulada'] = "Error crítico: No se pudo iniciar la transacción.";
    header("Location: " . $redirect_url);
    exit;
}

try {
    // 4. Verificar que la venta no esté ya anulada
    $sql_check = "SELECT ESTADO FROM VENTA WHERE IDVENTA = ?";
    $stmt_check = sqlsrv_query($conn, $sql_check, array($idventa));
    $venta = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

    if (!$venta) {
        throw new Exception("La venta #" . $idventa . " no existe.");
    }
    if ($venta['ESTADO'] == '0') {
        throw new Exception("La venta #" . $idventa . " ya se encontraba anulada.");
    }

    // 5. --- PASO 1: Devolver el Stock ---
    $sql_detalles = "SELECT IDPRODUCTO, CANTIDAD FROM DETALLE_VENTA WHERE IDVENTA = ?";
    $stmt_detalles = sqlsrv_query($conn, $sql_detalles, array($idventa));
    if ($stmt_detalles === false) {
        throw new Exception("Error al leer los detalles de la venta.");
    }

    while ($detalle = sqlsrv_fetch_array($stmt_detalles, SQLSRV_FETCH_ASSOC)) {
        // Por cada producto, actualizamos su stock (sumamos lo que se vendió)
        $sql_stock = "UPDATE PRODUCTO SET STOCK = STOCK + ? WHERE IDPRODUCTO = ?";
        $params_stock = array($detalle['CANTIDAD'], $detalle['IDPRODUCTO']);
        $stmt_stock = sqlsrv_query($conn, $sql_stock, $params_stock);
        if ($stmt_stock === false) {
            throw new Exception("Error al devolver el stock del producto ID: " . $detalle['IDPRODUCTO']);
        }
    }

    // 6. --- PASO 2: Anular la VENTA, DETALLES y COMPROBANTES ---
    $sql_anular_venta = "UPDATE VENTA SET ESTADO = '0' WHERE IDVENTA = ?";
    $stmt_anular_venta = sqlsrv_query($conn, $sql_anular_venta, array($idventa));
    if ($stmt_anular_venta === false) throw new Exception("Error al anular la venta.");

    $sql_anular_detalle = "UPDATE DETALLE_VENTA SET ESTADO = '0' WHERE IDVENTA = ?";
    $stmt_anular_detalle = sqlsrv_query($conn, $sql_anular_detalle, array($idventa));
    if ($stmt_anular_detalle === false) throw new Exception("Error al anular los detalles.");

    $sql_anular_boleta = "UPDATE BOLETA SET ESTADO = '0' WHERE IDVENTA = ?";
    $stmt_anular_boleta = sqlsrv_query($conn, $sql_anular_boleta, array($idventa));
    if ($stmt_anular_boleta === false) throw new Exception("Error al anular la boleta.");

    $sql_anular_factura = "UPDATE FACTURA SET ESTADO = '0' WHERE IDVENTA = ?";
    $stmt_anular_factura = sqlsrv_query($conn, $sql_anular_factura, array($idventa));
    if ($stmt_anular_factura === false) throw new Exception("Error al anular la factura.");
    

    // 7. --- COMMIT (CONFIRMAR LA TRANSACCIÓN) ---
    sqlsrv_commit($conn);
    $_SESSION['mensaje_venta_anulada'] = "¡Venta #" . $idventa . " anulada exitosamente! El stock ha sido devuelto.";
    
} catch (Exception $e) {
    // 8. --- ROLLBACK (REVERTIR LA TRANSACCIÓN) ---
    sqlsrv_rollback($conn);
    $_SESSION['error_venta_anulada'] = "Error al anular la venta: " . $e->getMessage();
}

// 9. Redirigir de vuelta (a la URL construida)
header("Location: " . $redirect_url);
exit;
?>