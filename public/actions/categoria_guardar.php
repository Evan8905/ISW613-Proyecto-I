<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

$token = $_POST['csrf_token'] ?? null;

if (!is_string($token) || !validarCsrf($token)) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

if (
    !is_string($_POST['nombre'] ?? null) ||
    !is_string($_POST['dias_max_prestamo'] ?? null)
) {
    http_response_code(400);
    exit('Datos inválidos.');
}

$nombre = trim($_POST['nombre']);
$diasRecibidos = trim($_POST['dias_max_prestamo']);

$dias = filter_var(
    $diasRecibidos,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 365]]
);

$errores = [];

if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 100) {
    $errores[] = 'El nombre es obligatorio y debe tener máximo 100 caracteres.';
}

if ($dias === false) {
    $errores[] = 'Los días máximos deben ser un número entero entre 1 y 365.';
}

function regresarRegistroCategoria(
    array $errores,
    string $nombre,
    string $dias
): void {
    $_SESSION['categoria_errores'] = $errores;
    $_SESSION['categoria_anterior'] = [
        'nombre' => $nombre,
        'dias_max_prestamo' => $dias
    ];

    header('Location: /categorias_crear.php');
    exit;
}

if (!empty($errores)) {
    regresarRegistroCategoria($errores, $nombre, $diasRecibidos);
}

try {

    $stmt = $dbh->prepare(
        "INSERT INTO categorias (nombre, dias_max_prestamo)
         VALUES (:nombre, :dias)"
    );

    $stmt->execute([
        ':nombre' => $nombre,
        ':dias' => $dias
    ]);

    $_SESSION['categoria_exito'] = 'Categoría registrada correctamente.';

    header('Location: /categorias.php');
    exit;

} catch (PDOException $e) {

    if ($e->getCode() === '23000') {
        regresarRegistroCategoria(
            ['Ya existe una categoría con ese nombre.'],
            $nombre,
            $diasRecibidos
        );
    }

    error_log($e->getMessage());

    regresarRegistroCategoria(
        ['No fue posible registrar la categoría.'],
        $nombre,
        $diasRecibidos
    );
}