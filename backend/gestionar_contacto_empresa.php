<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR ---
    if ($accion == 'crear') {
        $idempresa = $_POST['idempresa'];
        $idpersona = $_POST['idpersona'];
        $cargo_contacto = $_POST['cargo_contacto'];
        
        $sql = "INSERT INTO EMPRESA_CONTACTO (IDEMPRESA, IDPERSONA, CARGO_CONTACTO, ESTADO) VALUES (?, ?, ?, '1')";
        $params = array($idempresa, $idpersona, $cargo_contacto);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
             // Verificamos si es un error de duplicado (UQ_EMPRESA_PERSONA)
            if (strpos(print_r(sqlsrv_errors(), true), "UQ_EMPRESA_PERSONA") !== false) {
                $_SESSION['error_contacto_emp'] = "Error: Esta persona ya está registrada como contacto de esta empresa.";
            } else {
                $_SESSION['error_contacto_emp'] = "Error al guardar el vínculo.";
            }
        } else {
            $_SESSION['mensaje_contacto_emp'] = "¡Vínculo de contacto creado!";
        }
    }
    
    // --- ACCIÓN: EDITAR ---
    if ($accion == 'editar') {
        $idcontacto = $_POST['idcontacto'];
        $cargo_contacto = $_POST['cargo_contacto'];
        $estado = $_POST['estado'];

        $sql = "UPDATE EMPRESA_CONTACTO SET CARGO_CONTACTO = ?, ESTADO = ? WHERE IDCONTACTO = ?";
        $params = array($cargo_contacto, $estado, $idcontacto);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
             $_SESSION['error_contacto_emp'] = "Error al actualizar el vínculo.";
        } else {
            $_SESSION['mensaje_contacto_emp'] = "¡Vínculo actualizado!";
        }
    }
    header("Location: ../contactos_empresa.php?pagina=1");
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
        $sql = "UPDATE EMPRESA_CONTACTO SET ESTADO = ? WHERE IDCONTACTO = ?";
        $params = array($nuevo_estado, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_contacto_emp'] = "Error al cambiar el estado.";
        } else {
            $_SESSION['mensaje_contacto_emp'] = "Vínculo " . $mensaje . " correctamente.";
        }
    }
    header("Location: ../contactos_empresa.php?pagina=1");
    exit;
}
?>