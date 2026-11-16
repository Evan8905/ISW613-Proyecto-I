<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

// Verificar que la solicitud sea POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// Obtener credenciales del formulario.
$correo = trim($_POST['email'] ?? '');
$contrasena = $_POST['password'] ?? '';

// Redirigir al Login con un mensaje de error.
function errorLogin(): void
{
    $_SESSION['login_error'] = 'Correo o contraseña incorrectos.';
    header('Location: ../login.php');
    exit;
}

// Validar los campos recibidos.
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $contrasena === '') {
    errorLogin();
}

try {
    // Buscar al usuario por correo electrónico.
    $stmt = $dbh->prepare(
        "SELECT id, rol_id, nombre, correo, password_hash,
                estado, intentos_fallidos
         FROM usuarios
         WHERE correo = :correo
         LIMIT 1"
    );

    $stmt->execute([
        ':correo' => $correo
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Usuario inexistente.
    if (!$usuario) {
        errorLogin();
    }

    // Validar contraseña contra el hash almacenado.
    if (!password_verify($contrasena, $usuario['password_hash'])) {

        // Registrar el intento fallido.
        $stmt = $dbh->prepare(
            "UPDATE usuarios
             SET intentos_fallidos = intentos_fallidos + 1
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $usuario['id']
        ]);

        errorLogin();
    }

    // Verificar que la cuenta esté activa.
    if ((int) $usuario['estado'] !== 1) {
        errorLogin();
    }

    // Restablecer los intentos fallidos al autenticar.
    $stmt = $dbh->prepare(
        "UPDATE usuarios
         SET intentos_fallidos = 0
         WHERE id = :id"
    );

    $stmt->execute([
        ':id' => $usuario['id']
    ]);

    // Creación de sesión (se revisará en ISWP1-27).
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['rol_id'] = $usuario['rol_id'];

    // Acceso correcto.
    header('Location: ../dashboard.php');
    exit;

} catch (PDOException $e) {

    // Registrar el error internamente, sin mostrar detalles de MySQL.
    error_log($e->getMessage());

    $_SESSION['login_error'] =
        'No fue posible procesar el inicio de sesión. Inténtalo nuevamente.';

    header('Location: ../login.php');
    exit;
}