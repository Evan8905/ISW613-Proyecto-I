<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

requireAuth();

// Verificar autorización en el servidor.
if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

// Solo aceptar POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /usuarios.php');
    exit;
}

// Validar CSRF.
$token = $_POST['csrf_token'] ?? null;

if (!is_string($token) || !validarCsrf($token)) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

// Recuperar campos.
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$correo = trim((string) ($_POST['correo'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmarPassword = $_POST['confirmar_password'] ?? '';
$rolId = filter_var(
    $_POST['rol_id'] ?? null,
    FILTER_VALIDATE_INT
);

$errores = [];

// Validar nombre.
if (
    $nombre === '' ||
    mb_strlen($nombre, 'UTF-8') > 100
) {
    $errores[] = 'El nombre es obligatorio y debe tener máximo 100 caracteres.';
}

// Validar correo.
if (
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 150
) {
    $errores[] = 'Ingrese un correo electrónico válido.';
}

// Validar contraseña.
if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
    $errores[] = 'La contraseña debe tener entre 8 y 72 bytes.';
}

if (!is_string($confirmarPassword) || $password !== $confirmarPassword) {
    $errores[] = 'Las contraseñas no coinciden.';
}

// Validar rol.
if ($rolId === false || $rolId === null || $rolId <= 0) {
    $errores[] = 'Seleccione un rol válido.';
}

// Comprobar que el rol existe.
if (empty($errores)) {
    $stmt = $dbh->prepare(
        "SELECT id FROM roles WHERE id = :id LIMIT 1"
    );

    $stmt->execute([':id' => $rolId]);

    if (!$stmt->fetchColumn()) {
        $errores[] = 'El rol seleccionado no existe.';
    }
}

// Volver al formulario con errores.
function regresarFormulario(
    array $errores,
    string $nombre,
    string $correo,
    $rolId
): void {
    $_SESSION['usuario_errores'] = $errores;

    $_SESSION['usuario_anterior'] = [
        'nombre' => $nombre,
        'correo' => $correo,
        'rol_id' => (string) $rolId
    ];

    header('Location: /usuarios_crear.php');
    exit;
}

if (!empty($errores)) {
    regresarFormulario($errores, $nombre, $correo, $rolId);
}

try {

    // Generar hash seguro.
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Registrar usuario.
    $stmt = $dbh->prepare(
        "INSERT INTO usuarios
            (rol_id, nombre, correo, password_hash)
         VALUES
            (:rol_id, :nombre, :correo, :password_hash)"
    );

    $stmt->execute([
        ':rol_id' => $rolId,
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':password_hash' => $hash
    ]);

    $_SESSION['usuario_exito'] = 'Usuario registrado correctamente.';

    header('Location: /usuarios.php');
    exit;

} catch (PDOException $e) {

    // Error 23000: violación de restricción de integridad,
    // por ejemplo, correo duplicado.
    if ($e->getCode() === '23000') {
        regresarFormulario(
            ['El correo ya está registrado o los datos relacionados no son válidos.'],
            $nombre,
            $correo,
            $rolId
        );
    }

    error_log($e->getMessage());

    regresarFormulario(
        ['No fue posible registrar el usuario.'],
        $nombre,
        $correo,
        $rolId
    );
}