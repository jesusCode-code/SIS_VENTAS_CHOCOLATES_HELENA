<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        
        $params = [
            $_POST['idproducto'],
            $_POST['nompromocion'],
            $_POST['descripcion'],
            $_POST['porcentaje_desc'],
            $_POST['fecha_inicio'],
            $_POST['fecha_fin'],
            '1' // Estado Activo
        ];

        // Validación simple de fechas
        if ($_POST['fecha_fin'] < $_POST['fecha_inicio']) {
            $_SESSION['error_promo'] = "Error: La fecha de fin no puede ser anterior a la fecha de inicio.";
            header("Location: ../promociones.php");
            exit;
        }

        $sql = "INSERT INTO PROMOCION (IDPRODUCTO, NOMPROMOCION, DESCRIPCION, PORCENTAJE_DESC, FECHA_INICIO, FECHA_FIN, ESTADO) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_promo'] = "Error al guardar la promoción: " . print_r(sqlsrv_errors(), true);
        } else {
            $_SESSION['mensaje_promo'] = "¡Promoción '" . htmlspecialchars($_POST['nompromocion']) . "' guardada!";
        }
        
        header("Location: ../promociones.php");
        exit;
    }
    
    // ===================================
    // --- ACCIÓN: EDITAR (NUEVO) ---
    // ===================================
    elseif ($accion == 'editar') {
        
        $idpromocion = $_POST['idpromocion'];
        
        $params = [
            $_POST['idproducto'],
            $_POST['nompromocion'],
            $_POST['descripcion'],
            $_POST['porcentaje_desc'],
            $_POST['fecha_inicio'],
            $_POST['fecha_fin'],
            $_POST['estado'],
            $idpromocion // WHERE
        ];

        // Validación simple de fechas
        if ($_POST['fecha_fin'] < $_POST['fecha_inicio']) {
            $_SESSION['error_promo'] = "Error: La fecha de fin no puede ser anterior a la fecha de inicio.";
            header("Location: ../editar_promocion.php?id=" . $idpromocion); // Regresamos al form de edición
            exit;
        }

        $sql = "UPDATE PROMOCION SET
                    IDPRODUCTO = ?,
                    NOMPROMOCION = ?,
                    DESCRIPCION = ?,
                    PORCENTAJE_DESC = ?,
                    FECHA_INICIO = ?,
                    FECHA_FIN = ?,
                    ESTADO = ?
                WHERE IDPROMOCION = ?";
        
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_promo'] = "Error al actualizar la promoción: " . print_r(sqlsrv_errors(), true);
        } else {
            $_SESSION['mensaje_promo'] = "¡Promoción '" . htmlspecialchars($_POST['nompromocion']) . "' actualizada!";
        }
        
        header("Location: ../promociones.php");
        exit;
    }
}

// 3. Lógica para peticiones GET (Borrado lógico)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    
    $accion = $_GET['accion'];
    $id = $_GET['id'];
    $nuevo_estado = '';
    $mensaje = '';

    if ($accion == 'desactivar') {
        $nuevo_estado = '0'; // Inactivo
        $mensaje = 'desactivada';
    } elseif ($accion == 'activar') {
        $nuevo_estado = '1'; // Activo
        $mensaje = 'activada';
    }

    if ($nuevo_estado !== '') {
        $sql = "UPDATE PROMOCION SET ESTADO = ? WHERE IDPROMOCION = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_promo'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_promo'] = "Promoción " . $mensaje . " correctamente.";
        }
    }
    
    header("Location: ../promociones.php?pagina=1");
    exit;
}
?>