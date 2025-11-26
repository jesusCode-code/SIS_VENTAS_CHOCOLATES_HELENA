<?php
session_start();
include '../includes/conexion.php';

// Constantes de Roles (Deben coincidir con tu base de datos)
define('ROL_ADMIN', 'Administrador');
define('ROL_VENDEDOR', 'Vendedor'); 
define('ROL_CLIENTE', 'Cliente'); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $logeo = $_POST['logeo'];
    $clave_form = $_POST['clave'];

    // 1. Buscar usuario activo
    $sql = "SELECT 
                u.IDUSUARIO, u.CLAVE, 
                p.NOMBRES, p.APEPATERNO, 
                tu.NOMUSUARIO AS ROL,
                c.IDCLIENTE
            FROM USUARIO u
            INNER JOIN PERSONA p ON u.IDPERSONA = p.IDPERSONA
            INNER JOIN TIPO_USUARIO tu ON u.IDTIPO_USUARIO = tu.IDTIPO_USUARIO
            LEFT JOIN CLIENTE c ON p.IDPERSONA = c.IDPERSONA 
            WHERE u.LOGEO = ? AND u.ESTADO = '1'";

    $params = array($logeo);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) { 
        $_SESSION['error_login'] = "Error de conexión. Intente más tarde.";
        header("Location: ../login.php");
        exit;
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    // 2. Validar contraseña (comparación directa según tu sistema actual)
    if ($row && $clave_form === $row['CLAVE']) {
        
        $rol_usuario = $row['ROL'];

        // --- CASO 1: PERSONAL INTERNO (Admin y Vendedor) ---
        if ($rol_usuario == ROL_ADMIN || $rol_usuario == ROL_VENDEDOR) {
            
            $_SESSION['logueado'] = true;
            $_SESSION['idusuario'] = $row['IDUSUARIO'];
            $_SESSION['nombre_completo'] = $row['NOMBRES'] . ' ' . $row['APEPATERNO'];
            $_SESSION['rol'] = $rol_usuario; // Guardamos el rol para usarlo en el Header

            header("Location: ../dashboard.php");
            exit;

        // --- CASO 2: CLIENTE (Tienda Virtual) ---
        } elseif ($rol_usuario == ROL_CLIENTE) {
            
            if (empty($row['IDCLIENTE'])) {
                $_SESSION['error_login'] = "Usuario válido pero sin perfil de cliente asociado.";
                header("Location: ../login.php");
                exit;
            }

            $_SESSION['cliente_logueado'] = true; 
            $_SESSION['idusuario_cliente'] = $row['IDUSUARIO'];
            $_SESSION['idcliente'] = $row['IDCLIENTE'];
            $_SESSION['nombre_cliente'] = $row['NOMBRES'] . ' ' . $row['APEPATERNO'];

            header("Location: ../mi_cuenta.php");
            exit;

        } else {
            $_SESSION['error_login'] = "Rol de usuario no reconocido.";
            header("Location: ../login.php");
            exit;
        }

    } else {
        $_SESSION['error_login'] = "Usuario o contraseña incorrectos.";
        header("Location: ../login.php");
        exit;
    }

} else {
    header("Location: ../login.php");
    exit;
}
?>