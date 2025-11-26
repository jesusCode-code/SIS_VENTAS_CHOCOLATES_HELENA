<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        $nommetodo = $_POST['nommetodo'];
        $sql = "INSERT INTO METODO_PAGO (NOMMETODO, ESTADO) VALUES (?, '1')";
        $params = array($nommetodo);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            if (strpos(print_r(sqlsrv_errors(), true), "UNIQUE") !== false) {
                $_SESSION['error_metodo'] = "Error: El método '" . htmlspecialchars($nommetodo) . "' ya existe.";
            } else { $_SESSION['error_metodo'] = "Error al guardar el método."; }
        } else {
            $_SESSION['mensaje_metodo'] = "¡Método '" . htmlspecialchars($nommetodo) . "' guardado!";
        }
    }
    
    // --- ACCIÓN: EDITAR ---
    if ($accion == 'editar') {
        $idmetodopago = $_POST['idmetodopago'];
        $nommetodo = $_POST['nommetodo'];
        $estado = $_POST['estado'];

        $sql = "UPDATE METODO_PAGO SET NOMMETODO = ?, ESTADO = ? WHERE IDMETODOPAGO = ?";
        $params = array($nommetodo, $estado, $idmetodopago);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            if (strpos(print_r(sqlsrv_errors(), true), "UNIQUE") !== false) {
                $_SESSION['error_metodo'] = "Error: El método '" . htmlspecialchars($nommetodo) . "' ya existe.";
            } else { $_SESSION['error_metodo'] = "Error al actualizar el método."; }
        } else {
            $_SESSION['mensaje_metodo'] = "¡Método '" . htmlspecialchars($nommetodo) . "' actualizado!";
        }
    }
    header("Location: ../metodos_pago.php");
    exit;
}

// 3. Lógica para peticiones GET (Borrado lógico)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    
    $accion = $_GET['accion'];
    $id = $_GET['id'];
    $nuevo_estado = '';
    $mensaje = '';

    if ($accion == 'desactivar') {
        $nuevo_estado = '0'; $mensaje = 'desactivado';
    }
    if ($accion == 'activar') {
        $nuevo_estado = '1'; $mensaje = 'activado';
    }

    if ($nuevo_estado !== '') {
        $sql = "UPDATE METODO_PAGO SET ESTADO = ? WHERE IDMETODOPAGO = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_metodo'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_metodo'] = "Método " . $mensaje . " correctamente.";
        }
    }
    header("Location: ../metodos_pago.php?pagina=1");
    exit;
}
?>