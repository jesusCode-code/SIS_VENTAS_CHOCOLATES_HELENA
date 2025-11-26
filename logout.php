<?php
session_start(); // Unirse a la sesión existente

session_unset(); // Limpiar todas las variables de sesión

session_destroy(); // Destruir la sesión

// Redirigir al index (página de bienvenida)
header("Location: index.php");
exit;
?>