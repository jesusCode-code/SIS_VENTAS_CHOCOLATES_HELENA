<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        $nomprovincia = $_POST['nomprovincia'];
        $iddepartamento = $_POST['iddepartamento'];
        
        $sql = "INSERT INTO PROVINCIA (IDDEPARTAMENTO, NOMPROVINCIA, ESTADO) VALUES (?, ?, '1')";
        $params = array($iddepartamento, $nomprovincia);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
             $_SESSION['error_prov'] = "Error al guardar la provincia.";
        } else {
            $_SESSION['mensaje_prov'] = "¡Provincia '" . htmlspecialchars($nomprovincia) . "' guardada!";
        }
    }
    
    // --- ACCIÓN: EDITAR ---
    if ($accion == 'editar') {
        $idprovincia = $_POST['idprovincia'];
        $nomprovincia = $_POST['nomprovincia'];
        $iddepartamento = $_POST['iddepartamento'];
        $estado = $_POST['estado'];

        $sql = "UPDATE PROVINCIA SET NOMPROVINCIA = ?, IDDEPARTAMENTO = ?, ESTADO = ? WHERE IDPROVINCIA = ?";
        $params = array($nomprovincia, $iddepartamento, $estado, $idprovincia);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
             $_SESSION['error_prov'] = "Error al actualizar la provincia.";
        } else {
            $_SESSION['mensaje_prov'] = "¡Provincia '" . htmlspecialchars($nomprovincia) . "' actualizada!";
        }
    }
    header("Location: ../provincias.php?pagina=1");
    exit;
}

// 3. Lógica para peticiones GET (Borrado lógico)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    
    $accion = $_GET['accion'];
    $id = $_GET['id'];
    $nuevo_estado = '';
    $mensaje = '';

    if ($accion == 'desactivar') {
        $nuevo_estado = '0'; $mensaje = 'desactivada';
    }
    if ($accion == 'activar') {
        $nuevo_estado = '1'; $mensaje = 'activada';
    }

    if ($nuevo_estado !== '') {
        $sql = "UPDATE PROVINCIA SET ESTADO = ? WHERE IDPROVINCIA = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_prov'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_prov'] = "Provincia " . $mensaje . " correctamente.";
        }
    }
    header("Location: ../provincias.php?pagina=1");
    exit;
}
?>