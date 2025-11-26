<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        $nomdocumento = $_POST['nomdocumento'];
        $sql = "INSERT INTO TIPO_DOCUMENTO (NOMDOCUMENTO, ESTADO) VALUES (?, '1')";
        $params = array($nomdocumento);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_doc'] = "Error al guardar el tipo de documento.";
        } else {
            $_SESSION['mensaje_doc'] = "¡Tipo '" . htmlspecialchars($nomdocumento) . "' guardado!";
        }
    }
    
    // --- ACCIÓN: EDITAR ---
    if ($accion == 'editar') {
        $idtipo_documento = $_POST['idtipo_documento'];
        $nomdocumento = $_POST['nomdocumento'];
        $estado = $_POST['estado'];

        $sql = "UPDATE TIPO_DOCUMENTO SET NOMDOCUMENTO = ?, ESTADO = ? WHERE IDTIPO_DOCUMENTO = ?";
        $params = array($nomdocumento, $estado, $idtipo_documento);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
             $_SESSION['error_doc'] = "Error al actualizar el tipo de documento.";
        } else {
            $_SESSION['mensaje_doc'] = "¡Tipo '" . htmlspecialchars($nomdocumento) . "' actualizado!";
        }
    }
    header("Location: ../tipos_documento.php");
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
        $sql = "UPDATE TIPO_DOCUMENTO SET ESTADO = ? WHERE IDTIPO_DOCUMENTO = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_doc'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_doc'] = "Tipo de documento " . $mensaje . " correctamente.";
        }
    }
    header("Location: ../tipos_documento.php?pagina=1");
    exit;
}
?>