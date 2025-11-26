<?php
session_start();
include '../includes/conexion.php';

// 1. Seguridad del Cliente
if (!isset($_SESSION['cliente_logueado']) || $_SESSION['cliente_logueado'] !== true) {
    header("Location: ../login.php"); exit;
}

// 2. Validar que sea POST
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['idpersona'])) {
    header("Location: ../mi_cuenta.php?seccion=datos"); exit;
}

// 3. Recoger datos
$idpersona = $_POST['idpersona'];

try {
    // 4. Actualizar la tabla PERSONA
    $sql = "UPDATE PERSONA SET 
                IDDISTRITO = ?, 
                IDTIPO_DOCUMENTO = ?, 
                NOMBRES = ?, 
                APEPATERNO = ?, 
                APEMATERNO = ?, 
                EST_CIVIL = ?, 
                FEC_NACIMIENTO = ?, 
                DOC_IDENTIDAD = ?, 
                DIRECCION = ?, 
                CELULAR = ?, 
                CORREO = ?
            WHERE IDPERSONA = ?";
    
    $params = [
        $_POST['iddistrito'],
        $_POST['idtipo_documento'],
        $_POST['nombres'],
        $_POST['apepaterno'],
        $_POST['apematerno'],
        $_POST['est_civil'],
        $_POST['fec_nacimiento'],
        $_POST['doc_identidad'],
        $_POST['direccion'],
        $_POST['celular'],
        $_POST['correo'],
        $idpersona
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        throw new Exception("Error al actualizar tus datos. Verifica que el DNI, Celular o Correo no estén duplicados.");
    }
    
    // 5. Actualizar el nombre en la sesión
    $_SESSION['nombre_cliente'] = $_POST['nombres'] . ' ' . $_POST['apepaterno'];
    
    $_SESSION['mensaje_cuenta'] = "¡Tus datos han sido actualizados exitosamente!";

} catch (Exception $e) {
    $_SESSION['error_cuenta'] = $e->getMessage();
}

// 6. Redirigir de vuelta
header("Location: ../mi_cuenta.php?seccion=datos");
exit;
?>