<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        $nomcontrato = $_POST['nomcontrato'];
        $estado = '1'; // Siempre activo al crear

        $sql = "INSERT INTO CONTRATO (NOMCONTRATO, ESTADO) VALUES (?, ?)";
        $params = array($nomcontrato, $estado);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            if (strpos(print_r(sqlsrv_errors(), true), "UNIQUE") !== false) {
                $_SESSION['error_contrato'] = "Error: El nombre del contrato '" . htmlspecialchars($nomcontrato) . "' ya existe.";
            } else {
                $_SESSION['error_contrato'] = "Error al guardar el contrato.";
            }
        } else {
            $_SESSION['mensaje_contrato'] = "¡Contrato '" . htmlspecialchars($nomcontrato) . "' guardado!";
        }
    }
    
    // --- ACCIÓN: EDITAR ---
    if ($accion == 'editar') {
        $idcontrato = $_POST['idcontrato'];
        $nomcontrato = $_POST['nomcontrato'];
        $estado = $_POST['estado'];

        $sql = "UPDATE CONTRATO SET NOMCONTRATO = ?, ESTADO = ? WHERE IDCONTRATO = ?";
        $params = array($nomcontrato, $estado, $idcontrato);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            if (strpos(print_r(sqlsrv_errors(), true), "UNIQUE") !== false) {
                $_SESSION['error_contrato'] = "Error: El nombre del contrato '" . htmlspecialchars($nomcontrato) . "' ya existe.";
            } else {
                $_SESSION['error_contrato'] = "Error al actualizar el contrato.";
            }
        } else {
            $_SESSION['mensaje_contrato'] = "¡Contrato '" . htmlspecialchars($nomcontrato) . "' actualizado!";
        }
    }
    
    header("Location: ../contratos.php");
    exit;
}

// 3. Lógica para peticiones GET (Borrado lógico)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    
    $accion = $_GET['accion'];
    $id = $_GET['id'];
    $nuevo_estado = '';
    $mensaje = '';

    if ($accion == 'desactivar') {
        $nuevo_estado = '0'; // Inactivo
        $mensaje = 'desactivado';
    }
    
    if ($accion == 'activar') {
        $nuevo_estado = '1'; // Activo
        $mensaje = 'activado';
    }

    if ($nuevo_estado !== '') {
        $sql = "UPDATE CONTRATO SET ESTADO = ? WHERE IDCONTRATO = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_contrato'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_contrato'] = "Contrato " . $mensaje . " correctamente.";
        }
    }
    
    header("Location: ../contratos.php?pagina=1");
    exit;
}
?>