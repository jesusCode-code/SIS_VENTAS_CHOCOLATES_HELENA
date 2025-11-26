<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        $nomdepartamento = $_POST['nomdepartamento'];
        $sql = "INSERT INTO DEPARTAMENTO (NOMDEPARTAMENTO, ESTADO) VALUES (?, '1')";
        $params = array($nomdepartamento);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
             $_SESSION['error_dep'] = "Error al guardar el departamento.";
        } else {
            $_SESSION['mensaje_dep'] = "¡Departamento '" . htmlspecialchars($nomdepartamento) . "' guardado!";
        }
    }
    
    // --- ACCIÓN: EDITAR ---
    if ($accion == 'editar') {
        $iddepartamento = $_POST['iddepartamento'];
        $nomdepartamento = $_POST['nomdepartamento'];
        $estado = $_POST['estado'];

        $sql = "UPDATE DEPARTAMENTO SET NOMDEPARTAMENTO = ?, ESTADO = ? WHERE IDDEPARTAMENTO = ?";
        $params = array($nomdepartamento, $estado, $iddepartamento);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
             $_SESSION['error_dep'] = "Error al actualizar el departamento.";
        } else {
            $_SESSION['mensaje_dep'] = "¡Departamento '" . htmlspecialchars($nomdepartamento) . "' actualizado!";
        }
    }
    header("Location: ../departamentos.php");
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
        $sql = "UPDATE DEPARTAMENTO SET ESTADO = ? WHERE IDDEPARTAMENTO = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_dep'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_dep'] = "Departamento " . $mensaje . " correctamente.";
        }
    }
    header("Location: ../departamentos.php");
    exit;
}
?>