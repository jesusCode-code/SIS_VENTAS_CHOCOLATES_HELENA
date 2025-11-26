<?php
session_start();
include '../includes/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];

    // CREAR USUARIO
    if ($accion == 'crear') {
        // Verificar duplicados de Logeo
        $check = sqlsrv_query($conn, "SELECT IDUSUARIO FROM USUARIO WHERE LOGEO = ?", [$_POST['logeo']]);
        if (sqlsrv_has_rows($check)) {
            $_SESSION['error_usuario'] = "Error: El nombre de usuario ya existe.";
            header("Location: ../usuarios.php"); exit;
        }

        $sql = "INSERT INTO USUARIO (IDPERSONA, IDTIPO_USUARIO, LOGEO, CLAVE, ESTADO) VALUES (?, ?, ?, ?, '1')";
        $params = [
            $_POST['idpersona'],
            $_POST['idtipo_usuario'],
            $_POST['logeo'],
            $_POST['clave'] // (Sin encriptar, según tu estándar actual)
        ];
        
        if (sqlsrv_query($conn, $sql, $params)) {
            $_SESSION['mensaje_usuario'] = "Usuario creado exitosamente.";
        } else {
            $_SESSION['error_usuario'] = "Error al crear usuario.";
        }
        header("Location: ../usuarios.php?pagina=1"); exit;
    }

    // EDITAR USUARIO
    if ($accion == 'editar') {
        $idusuario = $_POST['idusuario'];
        $logeo = $_POST['logeo'];
        $rol = $_POST['idtipo_usuario'];
        $estado = $_POST['estado'];
        $clave = $_POST['clave'];

        // Construir SQL dinámico (si hay clave nueva o no)
        if (!empty($clave)) {
            $sql = "UPDATE USUARIO SET LOGEO=?, IDTIPO_USUARIO=?, ESTADO=?, CLAVE=? WHERE IDUSUARIO=?";
            $params = [$logeo, $rol, $estado, $clave, $idusuario];
        } else {
            $sql = "UPDATE USUARIO SET LOGEO=?, IDTIPO_USUARIO=?, ESTADO=? WHERE IDUSUARIO=?";
            $params = [$logeo, $rol, $estado, $idusuario];
        }

        if (sqlsrv_query($conn, $sql, $params)) {
            $_SESSION['mensaje_usuario'] = "Usuario actualizado exitosamente.";
        } else {
            // Probablemente logeo duplicado
            $_SESSION['error_usuario'] = "Error al actualizar (posible usuario duplicado).";
        }
        header("Location: ../usuarios.php?pagina=1"); exit;
    }
}

// GET: Activar/Desactivar
if (isset($_GET['accion'])) {
    $estado = ($_GET['accion'] == 'activar') ? '1' : '0';
    $id = $_GET['id'];
    $pagina = $_GET['pagina'] ?? 1;

    $sql = "UPDATE USUARIO SET ESTADO = ? WHERE IDUSUARIO = ?";
    if (sqlsrv_query($conn, $sql, [$estado, $id])) {
        $_SESSION['mensaje_usuario'] = "Estado del usuario actualizado.";
    } else {
        $_SESSION['error_usuario'] = "Error al cambiar estado.";
    }
    header("Location: ../usuarios.php?pagina=" . $pagina); exit;
}
?>
```

---

### 3. Actualizar el Menú (`includes/header.php`)

Finalmente, añade el enlace al menú. Recomiendo ponerlo en "RR.HH. y Roles".

**Edita `includes/header.php`:**

```php
// ... dentro del grupo RR.HH. y Roles
<ul class="menu-items collapse" id="submenu-rrhhsistema">
    <li><a class="nav-link" href="cargos.php">Cargos</a></li>
    <li><a class="nav-link" href="contratos.php">Contratos</a></li>
    <li><a class="nav-link" href="roles.php">Roles (Tipo Usuario)</a></li>
    <!-- NUEVO -->
    <li><a class="nav-link" href="usuarios.php">Usuarios</a></li> 
</ul>