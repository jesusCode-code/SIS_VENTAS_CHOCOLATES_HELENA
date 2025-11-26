<?php
session_start();
include '../includes/conexion.php';

// 1. Seguridad del Cliente
if (!isset($_SESSION['cliente_logueado']) || $_SESSION['cliente_logueado'] !== true) {
    header("Location: ../login.php"); exit;
}

// 2. Validar que sea POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../mi_cuenta.php?seccion=clave"); exit;
}

// 3. Recoger datos
$idusuario_actual = $_SESSION['idusuario_cliente'];
$clave_actual = $_POST['clave_actual'];
$clave_nueva = $_POST['clave_nueva'];
$clave_confirmar = $_POST['clave_confirmar'];

try {
    // 4. Validar que las nuevas contraseñas coincidan
    if ($clave_nueva !== $clave_confirmar) {
        throw new Exception("Las contraseñas nuevas no coinciden.");
    }
    
    // 5. Validar la contraseña actual
    $sql_check = "SELECT CLAVE FROM USUARIO WHERE IDUSUARIO = ?";
    $stmt_check = sqlsrv_query($conn, $sql_check, array($idusuario_actual));
    if ($stmt_check === false) throw new Exception("Error al verificar la cuenta.");
    
    $usuario = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
    
    if (!$usuario || $usuario['CLAVE'] !== $clave_actual) { // Comparación simple (no encriptada)
        throw new Exception("La contraseña actual es incorrecta.");
    }

    // 6. Actualizar la contraseña
    $sql_update = "UPDATE USUARIO SET CLAVE = ? WHERE IDUSUARIO = ?";
    $params_update = array($clave_nueva, $idusuario_actual);
    $stmt_update = sqlsrv_query($conn, $sql_update, $params_update);
    if ($stmt_update === false) {
        throw new Exception("Error al actualizar la contraseña.");
    }
    
    $_SESSION['mensaje_cuenta'] = "¡Tu contraseña ha sido actualizada exitosamente!";

} catch (Exception $e) {
    $_SESSION['error_cuenta'] = $e->getMessage();
}

// 7. Redirigir de vuelta
header("Location: ../mi_cuenta.php?seccion=clave");
exit;
?>