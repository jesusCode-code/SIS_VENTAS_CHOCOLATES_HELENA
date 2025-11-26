<?php
session_start();
include '../includes/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    if ($accion == 'crear') {
        if (sqlsrv_begin_transaction($conn) === false) {
            $_SESSION['error_empresa'] = "Error de transacción.";
            header("Location: ../empresas.php?pagina=1"); exit;
        }
        try {
            // 1. Insertar en EMPRESA
            $sql_emp = "INSERT INTO EMPRESA (RUC, RAZON_SOCIAL, DIRECCION, TELEFONO, ESTADO) 
                        OUTPUT INSERTED.IDEMPRESA 
                        VALUES (?, ?, ?, ?, '1')";
            $params_emp = [$_POST['ruc'], $_POST['razon_social'], $_POST['direccion'], $_POST['telefono']];
            $stmt_emp = sqlsrv_query($conn, $sql_emp, $params_emp);
            if ($stmt_emp === false) throw new Exception("Error al registrar empresa (RUC duplicado).");

            $row_id = sqlsrv_fetch_array($stmt_emp, SQLSRV_FETCH_ASSOC);
            $idEmpresa = $row_id['IDEMPRESA'];

            // 2. Insertar en CLIENTE
            $sql_cliente = "INSERT INTO CLIENTE (IDPERSONA, IDEMPRESA, ESTADO) VALUES (NULL, ?, '1')";
            $stmt_cliente = sqlsrv_query($conn, $sql_cliente, array($idEmpresa));
            if ($stmt_cliente === false) throw new Exception("Error al registrar cliente.");

            sqlsrv_commit($conn);
            $_SESSION['mensaje_empresa'] = "Empresa registrada correctamente.";
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            $_SESSION['error_empresa'] = "Error: " . $e->getMessage();
        }
        header("Location: ../empresas.php?pagina=1"); exit;
    }

    if ($accion == 'editar') {
        $idempresa = $_POST['idempresa'];
        $idcliente = $_POST['idcliente'];

        if (sqlsrv_begin_transaction($conn) === false) {
            $_SESSION['error_empresa'] = "Error de transacción.";
            header("Location: ../empresas.php"); exit;
        }
        try {
            $sql_emp = "UPDATE EMPRESA SET RUC = ?, RAZON_SOCIAL = ?, DIRECCION = ?, TELEFONO = ? WHERE IDEMPRESA = ?";
            $params_emp = [$_POST['ruc'], $_POST['razon_social'], $_POST['direccion'], $_POST['telefono'], $idempresa];
            $stmt_emp = sqlsrv_query($conn, $sql_emp, $params_emp);
            if ($stmt_emp === false) throw new Exception("Error al actualizar empresa.");

            $sql_cliente = "UPDATE CLIENTE SET ESTADO = ? WHERE IDCLIENTE = ?";
            $stmt_cliente = sqlsrv_query($conn, $sql_cliente, array($_POST['estado_cliente'], $idcliente));
            if ($stmt_cliente === false) throw new Exception("Error al actualizar estado.");

            sqlsrv_commit($conn);
            $_SESSION['mensaje_empresa'] = "Empresa actualizada correctamente.";
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            $_SESSION['error_empresa'] = "Error: " . $e->getMessage();
        }
        header("Location: ../empresas.php?pagina=1"); exit;
    }
}

// GET (Activar/Desactivar)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    $accion = $_GET['accion'];
    $id = $_GET['id']; // IDCLIENTE
    $pagina = $_GET['pagina'] ?? 1;
    
    $nuevo_estado = ($accion == 'activar') ? '1' : '0';
    $mensaje = ($accion == 'activar') ? 'activada' : 'desactivada';

    $sql = "UPDATE CLIENTE SET ESTADO = ? WHERE IDCLIENTE = ?";
    $stmt = sqlsrv_query($conn, $sql, array($nuevo_estado, $id));

    if ($stmt === false) {
        $_SESSION['error_empresa'] = "Error al cambiar estado.";
    } else {
        $_SESSION['mensaje_empresa'] = "Empresa " . $mensaje . " correctamente.";
    }
    header("Location: ../empresas.php?pagina=" . $pagina);
    exit;
}
?>