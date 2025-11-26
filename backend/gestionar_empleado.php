<?php
// 1. Incluimos seguridad y conexión
include '../includes/seguridad.php'; 
include '../includes/conexion.php';

// 2. Lógica para peticiones POST (Crear y Editar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // --- ACCIÓN: CREAR EMPLEADO ---
    if ($accion == 'crear_empleado') {
        
        $idpersona = $_POST['idpersona']; // <-- El ID de la persona que será empleado

        // --- INICIAR TRANSACCIÓN ---
        if (sqlsrv_begin_transaction($conn) === false) {
            $_SESSION['error_empleado'] = "Error al iniciar la transacción.";
            header("Location: ../empleados.php?pagina=1"); exit;
        }

        try {
            // 1. Recoger datos de EMPLEADO
            $params_empleado = [
                $idpersona, // Usamos el ID de la persona seleccionada
                $_POST['idcargo'],
                $_POST['idcontrato'],
                $_POST['salario'],
                $_POST['fec_contratacion'],
                '1' // Estado Activo
            ];

            // 2. Insertar en EMPLEADO
            // (Ya no necesitamos el OUTPUT IDEMPLEADO para esto)
            $sql_empleado = "INSERT INTO EMPLEADO (IDPERSONA, IDCARGO, IDCONTRATO, SALARIO, FEC_CONTRATACION, ESTADO) 
                             VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt_empleado = sqlsrv_query($conn, $sql_empleado, $params_empleado);
            if ($stmt_empleado === false) {
                // Error de constraint (ej: IDPERSONA ya es empleado)
                if (strpos(print_r(sqlsrv_errors(), true), "UNIQUE") !== false) {
                    throw new Exception("Error: Esta persona ya está registrada como empleado.");
                } else {
                    throw new Exception("Error al guardar los datos del empleado.");
                }
            }

            // 3. (Opcional) Insertar en USUARIO
            if (isset($_POST['crear_usuario']) && $_POST['crear_usuario'] == '1') {
                
                // ==========================================================
                // ¡¡¡CAMBIO CLAVE AQUÍ!!!
                // Usamos el $idpersona (de $_POST) en lugar de un $idempleado
                // ==========================================================
                $params_usuario = [
                    $idpersona, // <-- IDPERSONA
                    $_POST['idtipo_usuario'],
                    $_POST['logeo'],
                    $_POST['clave'], // (Sin encriptar, como acordamos)
                    '1'
                ];

                $sql_usuario = "INSERT INTO USUARIO (IDPERSONA, IDTIPO_USUARIO, LOGEO, CLAVE, ESTADO) 
                                VALUES (?, ?, ?, ?, ?)";
                $stmt_usuario = sqlsrv_query($conn, $sql_usuario, $params_usuario);
                if ($stmt_usuario === false) {
                    // Error de constraint (ej: Logeo duplicado o IDPERSONA ya tiene usuario)
                     if (strpos(print_r(sqlsrv_errors(), true), "UNIQUE") !== false) {
                         throw new Exception("Error: El 'Logeo' o la Persona ya tienen un usuario existente.");
                    } else {
                        throw new Exception("Error al crear el usuario.");
                    }
                }
            }

            // --- COMMIT ---
            sqlsrv_commit($conn);
            $_SESSION['mensaje_empleado'] = "Empleado registrado exitosamente.";

        } catch (Exception $e) {
            // --- ROLLBACK ---
            sqlsrv_rollback($conn);
            $_SESSION['error_empleado'] = "Error: " . $e->getMessage();
        }
        
        header("Location: ../empleados.php?pagina=1");
        exit;
    }
    
    // --- ACCIÓN: EDITAR EMPLEADO ---
    // (Esta lógica no se ve afectada por el cambio de BD,
    // ya que actualiza EMPLEADO por IDEMPLEADO y USUARIO por IDUSUARIO)
    elseif ($accion == 'editar_empleado') {
        
        // --- INICIAR TRANSACCIÓN ---
        if (sqlsrv_begin_transaction($conn) === false) {
            $_SESSION['error_empleado'] = "Error al iniciar la transacción.";
            header("Location: ../empleados.php?pagina=1"); exit;
        }

        try {
            // 1. Actualizar EMPLEADO
            $idempleado = $_POST['idempleado'];
            $params_empleado = [
                $_POST['idcargo'],
                $_POST['idcontrato'],
                $_POST['salario'],
                $_POST['fec_contratacion'],
                $_POST['estado_empleado'],
                $idempleado // WHERE
            ];

            $sql_empleado = "UPDATE EMPLEADO SET 
                                IDCARGO = ?, IDCONTRATO = ?, SALARIO = ?, 
                                FEC_CONTRATACION = ?, ESTADO = ?
                             WHERE IDEMPLEADO = ?";
            $stmt_empleado = sqlsrv_query($conn, $sql_empleado, $params_empleado);
            if ($stmt_empleado === false) {
                throw new Exception("Error al actualizar los datos del empleado.");
            }

            // 2. (Opcional) Actualizar USUARIO
            if (isset($_POST['idusuario']) && !empty($_POST['idusuario'])) {
                $idusuario = $_POST['idusuario'];
                $logeo = $_POST['logeo'];
                $idtipo_usuario = $_POST['idtipo_usuario'];
                $clave = $_POST['clave']; 

                if (!empty($clave)) {
                    $sql_usuario = "UPDATE USUARIO SET LOGEO = ?, IDTIPO_USUARIO = ?, CLAVE = ? 
                                    WHERE IDUSUARIO = ?";
                    $params_usuario = [$logeo, $idtipo_usuario, $clave, $idusuario];
                } else {
                    $sql_usuario = "UPDATE USUARIO SET LOGEO = ?, IDTIPO_USUARIO = ? 
                                    WHERE IDUSUARIO = ?";
                    $params_usuario = [$logeo, $idtipo_usuario, $idusuario];
                }
                
                $stmt_usuario = sqlsrv_query($conn, $sql_usuario, $params_usuario);
                if ($stmt_usuario === false) {
                    throw new Exception("Error al actualizar el usuario. Verifique 'Logeo' duplicado.");
                }
            }

            // --- COMMIT ---
            sqlsrv_commit($conn);
            $_SESSION['mensaje_empleado'] = "Empleado actualizado exitosamente.";

        } catch (Exception $e) {
            // --- ROLLBACK ---
            sqlsrv_rollback($conn);
            $_SESSION['error_empleado'] = "Error: " . $e->getMessage();
        }
        
        header("Location: ../empleados.php?pagina=1");
        exit;
    }

} // Fin del POST

// ===================================
// 3. Lógica GET (Activar/Desactivar) (¡CORREGIDA!)
// ===================================
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    
    $accion = $_GET['accion'];
    $idempleado = $_GET['id'];
    $pagina_actual = $_GET['pagina'] ?? 1;
    $nuevo_estado = '';
    $mensaje = '';

    if ($accion == 'desactivar') {
        $nuevo_estado = '0'; $mensaje = 'desactivado';
    } elseif ($accion == 'activar') {
        $nuevo_estado = '1'; $mensaje = 'activado';
    }

    if ($nuevo_estado !== '') {
        // --- INICIAR TRANSACCIÓN ---
        if (sqlsrv_begin_transaction($conn) === false) {
            $_SESSION['error_empleado'] = "Error al iniciar la transacción.";
            header("Location: ../empleados.php?pagina=1"); exit;
        }
        
        try {
            // 1. Actualizar EMPLEADO
            $sql_empleado = "UPDATE EMPLEADO SET ESTADO = ? WHERE IDEMPLEADO = ?";
            $stmt_empleado = sqlsrv_query($conn, $sql_empleado, array($nuevo_estado, $idempleado));
            if ($stmt_empleado === false) throw new Exception("Error al actualizar empleado.");

            // 2. ==================================================
            //    ¡¡¡CAMBIO CLAVE AQUÍ!!!
            //    Buscamos el IDPERSONA de este empleado para anular su usuario
            //    ==================================================
            $sql_get_persona = "SELECT IDPERSONA FROM EMPLEADO WHERE IDEMPLEADO = ?";
            $stmt_get_persona = sqlsrv_query($conn, $sql_get_persona, array($idempleado));
            if ($stmt_get_persona === false) throw new Exception("Error al buscar la persona del empleado.");
            
            $persona = sqlsrv_fetch_array($stmt_get_persona, SQLSRV_FETCH_ASSOC);
            
            if ($persona && $persona['IDPERSONA']) {
                $idpersona = $persona['IDPERSONA'];
                
                // 3. Actualizar USUARIO (si existe) usando IDPERSONA
                $sql_usuario = "UPDATE USUARIO SET ESTADO = ? WHERE IDPERSONA = ?";
                $stmt_usuario = sqlsrv_query($conn, $sql_usuario, array($nuevo_estado, $idpersona));
                if ($stmt_usuario === false) throw new Exception("Error al actualizar el usuario asociado.");
            }

            // --- COMMIT ---
            sqlsrv_commit($conn);
            $_SESSION['mensaje_empleado'] = "Empleado " . $mensaje . " correctamente (incluyendo su usuario).";
            
        } catch (Exception $e) {
            // --- ROLLBACK ---
            sqlsrv_rollback($conn);
            $_SESSION['error_empleado'] = "Error al " . $mensaje . " el empleado: " . $e->getMessage();
        }
    }
    
    header("Location: ../empleados.php?pagina=" . $pagina_actual);
    exit;
}
?>