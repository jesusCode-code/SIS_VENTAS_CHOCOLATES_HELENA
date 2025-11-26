<?php
// 1. Iniciar Sesión y Conexión
session_start();
include '../includes/conexion.php';

// 2. Seguridad del Cliente
if (!isset($_SESSION['cliente_logueado']) || $_SESSION['cliente_logueado'] !== true) {
    header("Location: ../login.php");
    exit;
}

// 3. Validar que sea POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Recoger datos
    $idcliente = $_SESSION['idcliente'];
    $idproducto = $_POST['idproducto'];
    $puntuacion = $_POST['puntuacion'];
    $comentario = $_POST['comentario'] ?? ''; // Opcional
    
    // Estado '0' = Pendiente de Aprobación por el Admin
    $estado_pendiente = '0';
    
    // Validación simple
    if (empty($idproducto) || empty($puntuacion)) {
        $_SESSION['error_val_cliente'] = "Error: Faltaron datos para enviar la valoración.";
        header("Location: ../mi_cuenta.php?seccion=valorar");
        exit;
    }

    // 5. Insertar en la Base de Datos
    $sql = "INSERT INTO VALORACION (IDPRODUCTO, IDCLIENTE, PUNTUACION, COMENTARIO, FECHA_VALORACION, ESTADO) 
            VALUES (?, ?, ?, ?, GETDATE(), ?)";
    
    $params = array($idproducto, $idcliente, $puntuacion, $comentario, $estado_pendiente);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        $_SESSION['error_val_cliente'] = "Error al guardar la valoración. Es posible que ya hayas valorado este producto.";
    } else {
        $_SESSION['mensaje_val_cliente'] = "¡Gracias! Tu valoración ha sido enviada y está pendiente de aprobación.";
    }

    // 6. Redirigir de vuelta
    header("Location: ../mi_cuenta.php?seccion=valorar");
    exit;
    
} else {
    // Si no es POST, redirigir
    header("Location: ../mi_cuenta.php");
    exit;
}
?>