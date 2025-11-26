<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR PERSONA ---
    if ($accion == 'crear_persona') {
        
        // --- INICIAR TRANSACCIÓN ---
        if (sqlsrv_begin_transaction($conn) === false) {
            $_SESSION['error_cliente'] = "Error al iniciar la transacción.";
            header("Location: ../clientes.php"); exit;
        }

        try {
            // 1. Recoger datos de PERSONA
            $params_persona = [
                $_POST['iddistrito'],
                $_POST['idtipo_documento'],
                $_POST['nombres'],
                $_POST['apepaterno'],
                $_POST['apematerno'],
                $_POST['est_civil'],
                $_POST['fec_nacimiento'],
                $_POST['doc_identidad'],
                $_POST['direccion'],
                $_POST['celular'],
                $_POST['correo'],
                '1' // Estado Activo
            ];

            // 2. Insertar en PERSONA
            // ======================================================================
            // CAMBIO AQUÍ: Añadimos la cláusula "OUTPUT INSERTED.IDPERSONA"
            // Esto hace que el INSERT devuelva el ID de la fila recién creada.
            // ======================================================================
            $sql_persona = "INSERT INTO PERSONA (IDDISTRITO, IDTIPO_DOCUMENTO, NOMBRES, APEPATERNO, APEMATERNO, EST_CIVIL, FEC_NACIMIENTO, DOC_IDENTIDAD, DIRECCION, CELULAR, CORREO, ESTADO) 
                            OUTPUT INSERTED.IDPERSONA 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_persona = sqlsrv_query($conn, $sql_persona, $params_persona);
            if ($stmt_persona === false) {
                throw new Exception("Error al guardar los datos de la persona. Verifique que el DNI, Celular o Correo no estén duplicados.");
            }

            // ======================================================================
            // CAMBIO AQUÍ: Obtenemos el ID directamente del resultado del INSERT
            // ======================================================================
            $row_id = sqlsrv_fetch_array($stmt_persona, SQLSRV_FETCH_ASSOC);
            $idPersona = $row_id['IDPERSONA'];

            if (!$idPersona) {
                throw new Exception("Error crítico: No se pudo recuperar el ID de la persona.");
            }

            // 4. Insertar en CLIENTE
            $sql_cliente = "INSERT INTO CLIENTE (IDPERSONA, IDEMPRESA, ESTADO) VALUES (?, NULL, '1')";
            $params_cliente = [$idPersona];
            $stmt_cliente = sqlsrv_query($conn, $sql_cliente, $params_cliente);
            if ($stmt_cliente === false) {
                // Si falla aquí, ahora SÍ es un error de la tabla CLIENTE
                throw new Exception("Error al vincular la persona como cliente.");
            }

            // --- COMMIT ---
            sqlsrv_commit($conn);
            $_SESSION['mensaje_cliente'] = "Cliente (Persona) '" . htmlspecialchars($_POST['nombres']) . "' creado exitosamente.";

        } catch (Exception $e) {
            // --- ROLLBACK ---
            sqlsrv_rollback($conn);
            $_SESSION['error_cliente'] = "Error: " . $e->getMessage();
        }
        
        header("Location: ../clientes.php");
        exit;
    }
    
    // --- ACCIÓN: CREAR EMPRESA ---
    elseif ($accion == 'crear_empresa') {
        
        // --- INICIAR TRANSACCIÓN ---
        if (sqlsrv_begin_transaction($conn) === false) {
            $_SESSION['error_cliente'] = "Error al iniciar la transacción.";
            header("Location: ../clientes.php"); exit;
        }

        try {
            // 1. Recoger datos de EMPRESA
            $params_empresa = [
                $_POST['ruc'],
                $_POST['razon_social'],
                $_POST['direccion'],
                $_POST['telefono'],
                '1' // Estado Activo
            ];

            // 2. Insertar en EMPRESA
            // ======================================================================
            // CAMBIO AQUÍ: Añadimos la cláusula "OUTPUT INSERTED.IDEMPRESA"
            // ======================================================================
            $sql_empresa = "INSERT INTO EMPRESA (RUC, RAZON_SOCIAL, DIRECCION, TELEFONO, ESTADO) 
                            OUTPUT INSERTED.IDEMPRESA
                            VALUES (?, ?, ?, ?, ?)";
            
            $stmt_empresa = sqlsrv_query($conn, $sql_empresa, $params_empresa);
            if ($stmt_empresa === false) {
                throw new Exception("Error al guardar la empresa. Verifique que el RUC, Razón Social o Teléfono no estén duplicados.");
            }

            // ======================================================================
            // CAMBIO AQUÍ: Obtenemos el ID directamente del resultado del INSERT
            // ======================================================================
            $row_id = sqlsrv_fetch_array($stmt_empresa, SQLSRV_FETCH_ASSOC);
            $idEmpresa = $row_id['IDEMPRESA'];

            if (!$idEmpresa) {
                throw new Exception("Error crítico: No se pudo recuperar el ID de la empresa.");
            }

            // 4. Insertar en CLIENTE
            $sql_cliente = "INSERT INTO CLIENTE (IDPERSONA, IDEMPRESA, ESTADO) VALUES (NULL, ?, '1')";
            $params_cliente = [$idEmpresa];
            $stmt_cliente = sqlsrv_query($conn, $sql_cliente, $params_cliente);
            if ($stmt_cliente === false) {
                throw new Exception("Error al vincular la empresa como cliente.");
            }

            // --- COMMIT ---
            sqlsrv_commit($conn);
            $_SESSION['mensaje_cliente'] = "Cliente (Empresa) '" . htmlspecialchars($_POST['razon_social']) . "' creado exitosamente.";

        } catch (Exception $e) {
            // --- ROLLBACK ---
            sqlsrv_rollback($conn);
            $_SESSION['error_cliente'] = "Error: " . $e->getMessage();
        }
        
        header("Location: ../clientes.php");
        exit;
    }

    // --- ACCIÓN: EDITAR PERSONA ---
    elseif ($accion == 'editar_persona') {
        
        // --- INICIAR TRANSACCIÓN ---
        if (sqlsrv_begin_transaction($conn) === false) {
            $_SESSION['error_cliente'] = "Error al iniciar la transacción.";
            header("Location: ../clientes.php"); exit;
        }

        try {
            // 1. Recoger datos
            $idcliente = $_POST['idcliente'];
            $idpersona = $_POST['idpersona'];
            $estado_cliente = $_POST['estado_cliente'];

            $params_persona = [
                $_POST['iddistrito'],
                $_POST['idtipo_documento'],
                $_POST['nombres'],
                $_POST['apepaterno'],
                $_POST['apematerno'],
                $_POST['est_civil'],
                $_POST['fec_nacimiento'],
                $_POST['doc_identidad'],
                $_POST['direccion'],
                $_POST['celular'],
                $_POST['correo'],
                $idpersona // ID para el WHERE
            ];

            // 2. Actualizar PERSONA
            $sql_persona = "UPDATE PERSONA SET 
                                IDDISTRITO = ?, IDTIPO_DOCUMENTO = ?, NOMBRES = ?, APEPATERNO = ?, 
                                APEMATERNO = ?, EST_CIVIL = ?, FEC_NACIMIENTO = ?, DOC_IDENTIDAD = ?, 
                                DIRECCION = ?, CELULAR = ?, CORREO = ?
                            WHERE IDPERSONA = ?";
            $stmt_persona = sqlsrv_query($conn, $sql_persona, $params_persona);
            if ($stmt_persona === false) {
                throw new Exception("Error al actualizar datos de la persona. Verifique duplicados.");
            }

            // 3. Actualizar el estado en CLIENTE
            $sql_cliente = "UPDATE CLIENTE SET ESTADO = ? WHERE IDCLIENTE = ?";
            $stmt_cliente = sqlsrv_query($conn, $sql_cliente, array($estado_cliente, $idcliente));
            if ($stmt_cliente === false) {
                throw new Exception("Error al actualizar el estado del cliente.");
            }

            // --- COMMIT ---
            sqlsrv_commit($conn);
            $_SESSION['mensaje_cliente'] = "Cliente '" . htmlspecialchars($_POST['nombres']) . "' actualizado exitosamente.";

        } catch (Exception $e) {
            // --- ROLLBACK ---
            sqlsrv_rollback($conn);
            $_SESSION['error_cliente'] = "Error: " . $e->getMessage();
        }
        
        header("Location: ../clientes.php");
        exit;
    }

    // --- ACCIÓN: EDITAR EMPRESA ---
    elseif ($accion == 'editar_empresa') {

        // --- INICIAR TRANSACCIÓN ---
        if (sqlsrv_begin_transaction($conn) === false) {
            $_SESSION['error_cliente'] = "Error al iniciar la transacción.";
            header("Location: ../clientes.php"); exit;
        }

        try {
            // 1. Recoger datos
            $idcliente = $_POST['idcliente'];
            $idempresa = $_POST['idempresa'];
            $estado_cliente = $_POST['estado_cliente'];

            $params_empresa = [
                $_POST['ruc'],
                $_POST['razon_social'],
                $_POST['direccion'],
                $_POST['telefono'],
                $idempresa // ID para el WHERE
            ];

            // 2. Actualizar EMPRESA
            $sql_empresa = "UPDATE EMPRESA SET 
                                RUC = ?, RAZON_SOCIAL = ?, DIRECCION = ?, TELEFONO = ?
                            WHERE IDEMPRESA = ?";
            $stmt_empresa = sqlsrv_query($conn, $sql_empresa, $params_empresa);
            if ($stmt_empresa === false) {
                throw new Exception("Error al actualizar datos de la empresa. Verifique duplicados.");
            }

            // 3. Actualizar el estado en CLIENTE
            $sql_cliente = "UPDATE CLIENTE SET ESTADO = ? WHERE IDCLIENTE = ?";
            $stmt_cliente = sqlsrv_query($conn, $sql_cliente, array($estado_cliente, $idcliente));
            if ($stmt_cliente === false) {
                throw new Exception("Error al actualizar el estado del cliente.");
            }

            // --- COMMIT ---
            sqlsrv_commit($conn);
            $_SESSION['mensaje_cliente'] = "Cliente '" . htmlspecialchars($_POST['razon_social']) . "' actualizado exitosamente.";

        } catch (Exception $e) {
            // --- ROLLBACK ---
            sqlsrv_rollback($conn);
            $_SESSION['error_cliente'] = "Error: " . $e->getMessage();
        }
        
        header("Location: ../clientes.php");
        exit;
    }

} // Fin del POST

// 3. Lógica para peticiones GET (Activar/Desactivar)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    
    $accion = $_GET['accion'];
    $idcliente = $_GET['id'];
    $nuevo_estado = '';
    $mensaje = '';

    if ($accion == 'desactivar') {
        $nuevo_estado = '0';
        $mensaje = 'desactivado';
    } elseif ($accion == 'activar') {
        $nuevo_estado = '1';
        $mensaje = 'activado';
    }

    if ($nuevo_estado !== '') {
        // Actualizamos el estado en la tabla CLIENTE
        $sql = "UPDATE CLIENTE SET ESTADO = ? WHERE IDCLIENTE = ?";
        $params = array($nuevo_estado, $idcliente);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['error_cliente'] = "Error al cambiar el estado del cliente.";
        } else {
            $_SESSION['mensaje_cliente'] = "Cliente " . $mensaje . " correctamente.";
        }
    }
    
    header("Location: ../clientes.php?pagina=1");
    exit;
}
?>