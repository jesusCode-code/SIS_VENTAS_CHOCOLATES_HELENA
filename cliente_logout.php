<?php
session_start();

// Destruimos SOLO las variables de sesión del cliente
unset($_SESSION['cliente_logueado']);
unset($_SESSION['idusuario_cliente']);
unset($_SESSION['nombre_cliente']);

// Redirigimos al inicio PÚBLICO
header("Location: index.php");
exit;
?>