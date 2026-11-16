
<?php
// Configuración de conexión PDO a la base de datos SIGEL.
$config = require __DIR__ . '/config.local.php';

$host = $config['host'];
$port = $config['port'];
$db = $config['database'];
$charSet= $config['charset'];

$dsn = "mysql:host=" . $host
     . ";port=" . $port
     . ";dbname=" . $db
     . ";charset=" . $charSet;

$usuario = $config['username'];
$contrasena = $config['password'];

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES =>	false
];

try {
    $dbh = new PDO($dsn, $usuario, $contrasena, $options);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('No fue posible conectar con la base de datos.');
}

?>