<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        $nomcargo = $_POST['nomcargo'];
        $estado = '1'; // Siempre activo al crear

        $sql = "INSERT INTO CARGO (NOMCARGO, ESTADO) VALUES (?, ?)";
        $params = array($nomcargo, $estado);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            // Manejo de error de Dúplicado (UNIQUE constraint)
            if (strpos(print_r(sqlsrv_errors(), true), "UNIQUE") !== false) {
                $_SESSION['error_cargo'] = "Error: El nombre del cargo '" . htmlspecialchars($nomcargo) . "' ya existe.";
            } else {
                $_SESSION['error_cargo'] = "Error al guardar el cargo.";
            }
        } else {
            $_SESSION['mensaje_cargo'] = "¡Cargo '" . htmlspecialchars($nomcargo) . "' guardado!";
        }
    }
    
    // --- ACCIÓN: EDITAR ---
    if ($accion == 'editar') {
        $idcargo = $_POST['idcargo'];
        $nomcargo = $_POST['nomcargo'];
        $estado = $_POST['estado'];

        $sql = "UPDATE CARGO SET NOMCARGO = ?, ESTADO = ? WHERE IDCARGO = ?";
        $params = array($nomcargo, $estado, $idcargo);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            if (strpos(print_r(sqlsrv_errors(), true), "UNIQUE") !== false) {
                $_SESSION['error_cargo'] = "Error: El nombre del cargo '" . htmlspecialchars($nomcargo) . "' ya existe.";
            } else {
                $_SESSION['error_cargo'] = "Error al actualizar el cargo.";
            }
        } else {
            $_SESSION['mensaje_cargo'] = "¡Cargo '" . htmlspecialchars($nomcargo) . "' actualizado!";
        }
    }
    
    header("Location: ../cargos.php");
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
        $sql = "UPDATE CARGO SET ESTADO = ? WHERE IDCARGO = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_cargo'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_cargo'] = "Cargo " . $mensaje . " correctamente.";
        }
    }
    
    header("Location: ../cargos.php?pagina=1");
    exit;
}
?>