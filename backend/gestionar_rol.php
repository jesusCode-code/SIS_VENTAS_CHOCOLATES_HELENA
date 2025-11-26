<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        $nomusuario = $_POST['nomusuario'];
        $sql = "INSERT INTO TIPO_USUARIO (NOMUSUARIO, ESTADO) VALUES (?, '1')";
        $params = array($nomusuario);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            if (strpos(print_r(sqlsrv_errors(), true), "UNIQUE") !== false) {
                $_SESSION['error_rol'] = "Error: El nombre del rol '" . htmlspecialchars($nomusuario) . "' ya existe.";
            } else { $_SESSION['error_rol'] = "Error al guardar el rol."; }
        } else {
            $_SESSION['mensaje_rol'] = "¡Rol '" . htmlspecialchars($nomusuario) . "' guardado!";
        }
    }
    
    // --- ACCIÓN: EDITAR ---
    if ($accion == 'editar') {
        $idtipo_usuario = $_POST['idtipo_usuario'];
        $nomusuario = $_POST['nomusuario'];
        $estado = $_POST['estado'];

        $sql = "UPDATE TIPO_USUARIO SET NOMUSUARIO = ?, ESTADO = ? WHERE IDTIPO_USUARIO = ?";
        $params = array($nomusuario, $estado, $idtipo_usuario);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            if (strpos(print_r(sqlsrv_errors(), true), "UNIQUE") !== false) {
                $_SESSION['error_rol'] = "Error: El nombre del rol '" . htmlspecialchars($nomusuario) . "' ya existe.";
            } else { $_SESSION['error_rol'] = "Error al actualizar el rol."; }
        } else {
            $_SESSION['mensaje_rol'] = "¡Rol '" . htmlspecialchars($nomusuario) . "' actualizado!";
        }
    }
    header("Location: ../roles.php");
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
        $sql = "UPDATE TIPO_USUARIO SET ESTADO = ? WHERE IDTIPO_USUARIO = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_rol'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_rol'] = "Rol " . $mensaje . " correctamente.";
        }
    }
    header("Location: ../roles.php?pagina=1");
    exit;
}
?>