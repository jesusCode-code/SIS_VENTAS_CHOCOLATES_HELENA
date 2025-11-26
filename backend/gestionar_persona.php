<?php
session_start();
include '../includes/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    if ($accion == 'crear') {
        $sql = "INSERT INTO PERSONA (IDDISTRITO, IDTIPO_DOCUMENTO, NOMBRES, APEPATERNO, APEMATERNO, EST_CIVIL, FEC_NACIMIENTO, DOC_IDENTIDAD, DIRECCION, CELULAR, CORREO, ESTADO) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1')";
        $params = [
            $_POST['iddistrito'], $_POST['idtipo_documento'], $_POST['nombres'], $_POST['apepaterno'], $_POST['apematerno'],
            $_POST['est_civil'], $_POST['fec_nacimiento'] ?: null, $_POST['doc_identidad'], $_POST['direccion'], $_POST['celular'], $_POST['correo']
        ];
        
        if (sqlsrv_query($conn, $sql, $params)) {
            $_SESSION['mensaje_persona'] = "Persona registrada exitosamente.";
        } else {
            $_SESSION['error_persona'] = "Error al registrar. Verifica el Documento o Correo.";
        }
        header("Location: ../personas.php?pagina=1"); exit;
    }

    if ($accion == 'editar') {
        $sql = "UPDATE PERSONA SET 
                    IDDISTRITO=?, IDTIPO_DOCUMENTO=?, NOMBRES=?, APEPATERNO=?, APEMATERNO=?, 
                    EST_CIVIL=?, FEC_NACIMIENTO=?, DOC_IDENTIDAD=?, DIRECCION=?, CELULAR=?, CORREO=?, ESTADO=?
                WHERE IDPERSONA=?";
        $params = [
            $_POST['iddistrito'], $_POST['idtipo_documento'], $_POST['nombres'], $_POST['apepaterno'], $_POST['apematerno'],
            $_POST['est_civil'], $_POST['fec_nacimiento'] ?: null, $_POST['doc_identidad'], $_POST['direccion'], $_POST['celular'], $_POST['correo'], $_POST['estado'],
            $_POST['idpersona']
        ];

        if (sqlsrv_query($conn, $sql, $params)) {
            $_SESSION['mensaje_persona'] = "Persona actualizada exitosamente.";
        } else {
            $_SESSION['error_persona'] = "Error al actualizar.";
        }
        header("Location: ../personas.php?pagina=1"); exit;
    }
}

// GET: Activar/Desactivar
if (isset($_GET['accion'])) {
    $estado = ($_GET['accion'] == 'activar') ? '1' : '0';
    $id = $_GET['id'];
    $sql = "UPDATE PERSONA SET ESTADO = ? WHERE IDPERSONA = ?";
    if (sqlsrv_query($conn, $sql, [$estado, $id])) {
        $_SESSION['mensaje_persona'] = "Estado actualizado correctamente.";
    } else {
        $_SESSION['error_persona'] = "Error al cambiar estado.";
    }
    header("Location: ../personas.php?pagina=1"); exit;
}
?>