<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        $nomcategoria = $_POST['nomcategoria'];
        $estado = '1'; 

        $sql = "INSERT INTO CATEGORIA_PRODUCTO (NOMCATEGORIA, ESTADO) VALUES (?, ?)";
        $params = array($nomcategoria, $estado);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_categoria'] = "Error al guardar la categoría.";
        } else {
            $_SESSION['mensaje_categoria'] = "¡Categoría '" . htmlspecialchars($nomcategoria) . "' guardada!";
        }
    }
    
    // --- ACCIÓN: EDITAR ---
    if ($accion == 'editar') {
        $idcategoria = $_POST['idcategoria'];
        $nomcategoria = $_POST['nomcategoria'];
        $estado = $_POST['estado'];

        $sql = "UPDATE CATEGORIA_PRODUCTO SET NOMCATEGORIA = ?, ESTADO = ? WHERE IDCATEGORIA = ?";
        $params = array($nomcategoria, $estado, $idcategoria);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_categoria'] = "Error al actualizar la categoría.";
        } else {
            $_SESSION['mensaje_categoria'] = "¡Categoría '" . htmlspecialchars($nomcategoria) . "' actualizada!";
        }
    }
    
    header("Location: ../categorias.php");
    exit;
}

// 3. Lógica para peticiones GET 
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    
    $accion = $_GET['accion'];
    $id = $_GET['id'];
    $nuevo_estado = '';
    $mensaje = '';

    // --- ACCIÓN: ELIMINAR (Desactivar) ---
    if ($accion == 'eliminar') {
        $nuevo_estado = '0'; // Inactivo
        $mensaje = 'desactivada';
    }
    
    // --- ACCIÓN: ACTIVAR ---
    if ($accion == 'activar') {
        $nuevo_estado = '1'; // Activo
        $mensaje = 'activada';
    }

    if ($nuevo_estado !== '') {
        $sql = "UPDATE CATEGORIA_PRODUCTO SET ESTADO = ? WHERE IDCATEGORIA = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_categoria'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_categoria'] = "Categoría " . $mensaje . " correctamente.";
        }
    }
    
    header("Location: ../categorias.php?pagina=1");
    exit;
}
?>