<?php
// Configuración de la conexión a SQL Server (Azure)
$serverName = "adminchocolates.database.windows.net"; 

$connectionOptions = array(
    "Database" => "SIS_VENTAS_CHOCOLATES_HELENA_V1", 
    "Uid" => "chocolates",       
    "PWD" => "a1234567_",
    // Es recomendable añadir esto para evitar problemas con tildes y caracteres especiales
    "CharacterSet" => "UTF-8" 
);

// Intentar conectar
$conn = sqlsrv_connect($serverName, $connectionOptions); 

if ($conn === false) {
    // Si la conexión falla, muestra el error detallado y detiene la ejecución
    die(print_r(sqlsrv_errors(), true)); 
}
?>
