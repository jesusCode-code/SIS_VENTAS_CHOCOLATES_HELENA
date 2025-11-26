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

// ===================================
// ID del Rol "cliente"
// (Basado en tu BD, el ID=1 es 'cliente')
// ===================================
$ID_ROL_CLIENTE = 1; 

// 2. --- INICIAR TRANSACCIÓN ---
if (sqlsrv_begin_transaction($conn) === false) {
    $_SESSION['error_registro'] = "Error crítico: No se pudo iniciar la transacción.";
    header("Location: ../cliente_registro.php"); exit;
}

try {
    // 3. --- PASO 1: INSERTAR EN PERSONA ---
    $params_persona = [
        null, // IDDISTRITO (lo dejamos nulo por ahora, el cliente puede llenarlo en "Mis Datos")
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
        throw new Exception("Error al guardar los datos personales. Verifique que el DNI, Celular o Correo no estén duplicados.");
    }
    
    // 4. --- PASO 2: OBTENER EL IDPERSONA CREADO ---
    $row_id = sqlsrv_fetch_array($stmt_persona, SQLSRV_FETCH_ASSOC);
    $idPersona = $row_id['IDPERSONA'];

    // 5. --- PASO 3: INSERTAR EN CLIENTE ---
    $sql_cliente = "INSERT INTO CLIENTE (IDPERSONA, IDEMPRESA, ESTADO) VALUES (?, NULL, '1')";
    $params_cliente = [$idPersona];
    $stmt_cliente = sqlsrv_query($conn, $sql_cliente, $params_cliente);
    if ($stmt_cliente === false) {
        throw new Exception("Error al vincular la persona como cliente.");
    }

    // 6. --- PASO 4: INSERTAR EN USUARIO ---
    $params_usuario = [
        $idPersona,
        $ID_ROL_CLIENTE, // ID 1 = 'cliente'
        $_POST['logeo'],
        $clave, // (Sin encriptar, como acordamos)
        '1'
    ];
    $sql_usuario = "INSERT INTO USUARIO (IDPERSONA, IDTIPO_USUARIO, LOGEO, CLAVE, ESTADO) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmt_usuario = sqlsrv_query($conn, $sql_usuario, $params_usuario);
    if ($stmt_usuario === false) {
        throw new Exception("Error al crear la cuenta de usuario. El 'Logeo' (nombre de usuario) ya existe.");
    }

    // 7. --- COMMIT ---
    sqlsrv_commit($conn);
    $_SESSION['mensaje_registro'] = "¡Cuenta creada exitosamente! Ahora puedes iniciar sesión.";
    header("Location: ../login.php"); // Redirigir al Login
    exit;

} catch (Exception $e) {
    // 8. --- ROLLBACK ---
    sqlsrv_rollback($conn);
    // Mostramos el mensaje de error específico
    $_SESSION['error_registro'] = $e->getMessage();
    header("Location: ../cliente_registro.php");
    exit;
}
?>