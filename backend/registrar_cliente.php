<?php
session_start();
include '../includes/conexion.php';

// Verificamos que sea POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../cliente_registro.php");
    exit;
}

// 1. Validar Contraseñas
$clave = $_POST['clave'];
$clave_confirm = $_POST['clave_confirm'];

if ($clave !== $clave_confirm) {
    $_SESSION['error_registro'] = "Error: Las contraseñas no coinciden.";
    header("Location: ../cliente_registro.php");
    exit;
}
$sql_rol = "SELECT IDTIPO_USUARIO FROM TIPO_USUARIO WHERE NOMUSUARIO = 'Cliente' OR NOMUSUARIO = 'CLIENTE'";
$stmt_rol = sqlsrv_query($conn, $sql_rol);

if ($stmt_rol === false) {
    $_SESSION['error_registro'] = "Error interno al verificar roles.";
    header("Location: ../cliente_registro.php"); exit;
}

$row_rol = sqlsrv_fetch_array($stmt_rol, SQLSRV_FETCH_ASSOC);

if ($row_rol) {
    $ID_ROL_CLIENTE = $row_rol['IDTIPO_USUARIO'];
} else {
    // Si no encuentra el rol "Cliente", detenemos todo por seguridad.
    $_SESSION['error_registro'] = "Error de configuración: El rol 'Cliente' no existe en la base de datos. Contacte al administrador.";
    header("Location: ../cliente_registro.php"); exit;
}
// ====================================================================

// 2. --- INICIAR TRANSACCIÓN ---
if (sqlsrv_begin_transaction($conn) === false) {
    $_SESSION['error_registro'] = "Error crítico: No se pudo iniciar la transacción.";
    header("Location: ../cliente_registro.php"); exit;
}

try {
    // 3. --- PASO 1: INSERTAR EN PERSONA ---
    // Nota: IDDISTRITO se deja NULL intencionalmente para que lo llenen luego en "Mi Cuenta"
    $params_persona = [
        null, // IDDISTRITO
        $_POST['idtipo_documento'],
        $_POST['nombres'],
        $_POST['apepaterno'],
        $_POST['apematerno'],
        null, // EST_CIVIL
        null, // FEC_NACIMIENTO
        $_POST['doc_identidad'],
        null, // DIRECCION
        $_POST['celular'],
        $_POST['correo'],
        '1' // Estado
    ];

    $sql_persona = "INSERT INTO PERSONA (IDDISTRITO, IDTIPO_DOCUMENTO, NOMBRES, APEPATERNO, APEMATERNO, EST_CIVIL, FEC_NACIMIENTO, DOC_IDENTIDAD, DIRECCION, CELULAR, CORREO, ESTADO) 
                    OUTPUT INSERTED.IDPERSONA 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_persona = sqlsrv_query($conn, $sql_persona, $params_persona);
    if ($stmt_persona === false) {
        // Capturamos el error para saber si es duplicado
        $errors = sqlsrv_errors();
        $msg = "Error al guardar datos personales. Verifique que el DNI o Correo no estén ya registrados.";
        if(isset($errors[0]['message'])) { $msg .= " (" . $errors[0]['message'] . ")"; }
        throw new Exception($msg);
    }
    
    // 4. --- PASO 2: OBTENER EL IDPERSONA CREADO ---
    $row_id = sqlsrv_fetch_array($stmt_persona, SQLSRV_FETCH_ASSOC);
    $idPersona = $row_id['IDPERSONA'];

    // 5. --- PASO 3: INSERTAR EN CLIENTE ---
    $sql_cliente = "INSERT INTO CLIENTE (IDPERSONA, IDEMPRESA, ESTADO) VALUES (?, NULL, '1')";
    $params_cliente = [$idPersona];
    $stmt_cliente = sqlsrv_query($conn, $sql_cliente, $params_cliente);
    if ($stmt_cliente === false) {
        throw new Exception("Error al crear el perfil de cliente.");
    }

    // 6. --- PASO 4: INSERTAR EN USUARIO ---
    $params_usuario = [
        $idPersona,
        $ID_ROL_CLIENTE, 
        $_POST['logeo'],
        $clave, 
        '1'
    ];
    
    // Verificamos si el usuario ya existe antes de intentar insertar
    $check_user = sqlsrv_query($conn, "SELECT IDUSUARIO FROM USUARIO WHERE LOGEO = ?", array($_POST['logeo']));
    if (sqlsrv_has_rows($check_user)) {
        throw new Exception("El nombre de usuario (Logeo) ya está en uso. Por favor elige otro.");
    }
    
    $sql_usuario = "INSERT INTO USUARIO (IDPERSONA, IDTIPO_USUARIO, LOGEO, CLAVE, ESTADO) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmt_usuario = sqlsrv_query($conn, $sql_usuario, $params_usuario);
    if ($stmt_usuario === false) {
        throw new Exception("Error al crear la cuenta de usuario.");
    }

    // 7. --- COMMIT ---
    sqlsrv_commit($conn);
    $_SESSION['mensaje_registro'] = "¡Cuenta creada exitosamente! Ahora puedes iniciar sesión.";
    header("Location: ../login.php");
    exit;

} catch (Exception $e) {
    // 8. --- ROLLBACK ---
    sqlsrv_rollback($conn);
    $_SESSION['error_registro'] = $e->getMessage();
    header("Location: ../cliente_registro.php");
    exit;
}
?>
