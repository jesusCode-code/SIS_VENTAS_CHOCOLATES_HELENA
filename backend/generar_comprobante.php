<?php
// 1. Seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Validar que tengamos los datos por GET
if (!isset($_GET['idventa']) || !isset($_GET['tipo'])) {
    $_SESSION['error_comprobante'] = "Error: Faltan datos para generar el comprobante.";
    header("Location: ../comprobantes.php");
    exit;
}

$idventa = $_GET['idventa'];
$tipo = $_GET['tipo']; // 'boleta' o 'factura'

// 3. --- INICIAR TRANSACCIÓN ---
// (Aunque es una sola inserción, es buena práctica por si la lógica crece)
if (sqlsrv_begin_transaction($conn) === false) {
    $_SESSION['error_comprobante'] = "Error al iniciar la transacción.";
    header("Location: ../comprobantes.php");
    exit;
}

try {
    
    // 4. Lógica para generar Serie y Número
    // Esto es una simplificación. En un sistema real, los correlativos
    // se manejan de forma más robusta (ej. en una tabla de configuración).
    
    $serie = '';
    $tabla_destino = '';
    $sql_ultimo_num = '';

    if ($tipo == 'boleta') {
        $serie = 'B001';
        $tabla_destino = 'BOLETA';
        $sql_ultimo_num = "SELECT ISNULL(MAX(NUMERO), '00000') AS UltimoNumero FROM BOLETA WHERE SERIE = ?";
    
    } else if ($tipo == 'factura') {
        $serie = 'F001';
        $tabla_destino = 'FACTURA';
        $sql_ultimo_num = "SELECT ISNULL(MAX(NUMERO), '00000') AS UltimoNumero FROM FACTURA WHERE SERIE = ?";
    
    } else {
        throw new Exception("Tipo de comprobante no válido.");
    }

    // 5. Obtener el último número de esa serie
    $params_num = array($serie);
    $stmt_num = sqlsrv_query($conn, $sql_ultimo_num, $params_num);
    if ($stmt_num === false) {
        throw new Exception("Error al obtener el correlativo.");
    }
    
    $row_num = sqlsrv_fetch_array($stmt_num, SQLSRV_FETCH_ASSOC);
    $ultimo_numero = (int)$row_num['UltimoNumero'];
    
    // 6. Calcular el nuevo número (ej: 1 -> "00001", 2 -> "00002")
    $nuevo_numero_int = $ultimo_numero + 1;
    // str_pad rellena con ceros a la izquierda hasta un total de 5 caracteres
    $nuevo_numero_str = str_pad($nuevo_numero_int, 5, "0", STR_PAD_LEFT); 

    // 7. Insertar el nuevo comprobante
    $sql_insert = "INSERT INTO $tabla_destino (IDVENTA, SERIE, NUMERO, FECHA_EMISION, ESTADO) 
                   VALUES (?, ?, ?, GETDATE(), '1')";
    
    $params_insert = array($idventa, $serie, $nuevo_numero_str);
    $stmt_insert = sqlsrv_query($conn, $sql_insert, $params_insert);

    if ($stmt_insert === false) {
        throw new Exception("Error al guardar el comprobante en la base de datos.");
    }

    // 8. --- COMMIT (CONFIRMAR) ---
    sqlsrv_commit($conn);
    $_SESSION['mensaje_comprobante'] = "¡" . ucfirst($tipo) . " generada exitosamente! (Serie: $serie, Número: $nuevo_numero_str)";
    
} catch (Exception $e) {
    // 9. --- ROLLBACK (REVERTIR) ---
    sqlsrv_rollback($conn);
    $_SESSION['error_comprobante'] = "Error: " . $e->getMessage();
}

// 10. Redirigir de vuelta
header("Location: ../comprobantes.php");
exit;
?>