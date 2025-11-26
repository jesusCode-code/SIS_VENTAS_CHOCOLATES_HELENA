<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        $nomdistrito = $_POST['nomdistrito'];
        $idprovincia = $_POST['idprovincia'];
        
        $sql = "INSERT INTO DISTRITO (IDPROVINCIA, NOMDISTRITO, ESTADO) VALUES (?, ?, '1')";
        $params = array($idprovincia, $nomdistrito);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
             $_SESSION['error_dist'] = "Error al guardar el distrito.";
        } else {
            $_SESSION['mensaje_dist'] = "¡Distrito '" . htmlspecialchars($nomdistrito) . "' guardado!";
        }
    }
    
    // --- ACCIÓN: EDITAR ---
    if ($accion == 'editar') {
        $iddistrito = $_POST['iddistrito'];
        $nomdistrito = $_POST['nomdistrito'];
        $idprovincia = $_POST['idprovincia'];
        $estado = $_POST['estado'];

        $sql = "UPDATE DISTRITO SET NOMDISTRITO = ?, IDPROVINCIA = ?, ESTADO = ? WHERE IDDISTRITO = ?";
        $params = array($nomdistrito, $idprovincia, $estado, $iddistrito);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
             $_SESSION['error_dist'] = "Error al actualizar el distrito.";
        } else {
            $_SESSION['mensaje_dist'] = "¡Distrito '" . htmlspecialchars($nomdistrito) . "' actualizado!";
        }
    }
    header("Location: ../distritos.php?pagina=1");
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
        $sql = "UPDATE DISTRITO SET ESTADO = ? WHERE IDDISTRITO = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_dist'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_dist'] = "Distrito " . $mensaje . " correctamente.";
        }
    }
    header("Location: ../distritos.php?pagina=1");
    exit;
}
?>