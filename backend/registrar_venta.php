<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Validar que la petición sea POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../ventas.php");
    exit;
}

// 3. --- INICIAR LA TRANSACCIÓN ---
if (sqlsrv_begin_transaction($conn) === false) {
    $_SESSION['error_venta'] = "Error crítico: No se pudo iniciar la transacción.";
    header("Location: ../ventas.php");
    exit;
}

try {
    // 4. Recoger todos los datos
    $idCliente = $_POST['idCliente'];
    $idMetodoPago = $_POST['idMetodoPago'];
    $subtotal = $_POST['subtotal_final'];
    $igv = $_POST['igv_final'];
    $total = $_POST['total_final'];
    $carrito_json = $_POST['carrito_json'];
    $idUsuario = $_SESSION['idusuario']; 
    $carrito = json_decode($carrito_json, true);

    if (empty($carrito)) {
        throw new Exception("El carrito está vacío.");
    }

    // 6. --- PASO 1: INSERTAR EN LA TABLA 'VENTA' ---
    // ======================================================================
    // CAMBIO AQUÍ: Añadimos la cláusula "OUTPUT INSERTED.IDVENTA"
    // ======================================================================
    $sqlVenta = "INSERT INTO VENTA (IDCLIENTE, IDUSUARIO, IDMETODOPAGO, FECHAVENTA, SUBTOTAL, IGV, TOTAL, ESTADO) 
                 OUTPUT INSERTED.IDVENTA 
                 VALUES (?, ?, ?, GETDATE(), ?, ?, ?, '1')";
    $paramsVenta = array($idCliente, $idUsuario, $idMetodoPago, $subtotal, $igv, $total);
    
    $stmtVenta = sqlsrv_query($conn, $sqlVenta, $paramsVenta);
    
    if ($stmtVenta === false) {
        throw new Exception("Error al guardar los datos principales de la venta.");
    }

    // ======================================================================
    // CAMBIO AQUÍ: Obtenemos el ID directamente del resultado del INSERT
    // ======================================================================
    $rowId = sqlsrv_fetch_array($stmtVenta, SQLSRV_FETCH_ASSOC);
    $idVenta = $rowId['IDVENTA'];

    if (!$idVenta) {
        // Este error ahora SÍ es crítico si falla
        throw new Exception("Error crítico al obtener el ID de la nueva venta.");
    }
    // (El antiguo 'SELECT SCOPE_IDENTITY()' se elimina)
    // ======================================================================

    // 8. --- PASO 3: INSERTAR DETALLE Y ACTUALIZAR STOCK ---
    foreach ($carrito as $item) {
        
        // (A) Insertar en DETALLE_VENTA
        $sqlDetalle = "INSERT INTO DETALLE_VENTA (IDVENTA, IDPRODUCTO, CANTIDAD, PRECIO_UNITARIO, SUBTOTAL, ESTADO) 
                       VALUES (?, ?, ?, ?, ?, '1')";
        $paramsDetalle = array(
            $idVenta, 
            $item['id'], 
            $item['cantidad'], 
            $item['precio'], // Este es el precio_final (con descuento si aplica)
            $item['subtotal']
        );
        $stmtDetalle = sqlsrv_query($conn, $sqlDetalle, $paramsDetalle);
        if ($stmtDetalle === false) {
            throw new Exception("Error al guardar el detalle del producto: " . htmlspecialchars($item['nombre']));
        }

        // (B) Actualizar el STOCK en la tabla PRODUCTO
        $sqlStock = "UPDATE PRODUCTO SET STOCK = STOCK - ? 
                     WHERE IDPRODUCTO = ? AND STOCK >= ?";
        $paramsStock = array($item['cantidad'], $item['id'], $item['cantidad']);
        
        $stmtStock = sqlsrv_query($conn, $sqlStock, $paramsStock);
        if ($stmtStock === false) {
            throw new Exception("Error al actualizar el stock del producto: " . htmlspecialchars($item['nombre']));
        }
        $rows_affected = sqlsrv_rows_affected($stmtStock);
        if ($rows_affected == 0) {
            throw new Exception("Stock insuficiente para: " . htmlspecialchars($item['nombre']) . ". Venta cancelada.");
        }
    }

    // 9. --- COMMIT ---
    sqlsrv_commit($conn);
    $_SESSION['mensaje_venta'] = "¡Venta registrada exitosamente con el ID: $idVenta!";
    header("Location: ../ventas.php");
    exit;

} catch (Exception $e) {
    // 11. --- ROLLBACK ---
    sqlsrv_rollback($conn);
    $_SESSION['error_venta'] = "Error al registrar la venta: " . $e->getMessage();
    header("Location: ../ventas.php");
    exit;
}
?>