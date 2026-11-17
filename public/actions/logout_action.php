<?php

require_once __DIR__ . '/../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('location: /dashboard.php');
    exit;
}

$tokenRecibido = $_POST['csrf_token'] ?? null;

if (!is_string($tokenRecibido) || !validarCsrf($tokenRecibido)) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

//limpiar la variables de la sesión

$_SESSION = [];

//Elimina las cookies del navegador

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite']
    ]);
}

//Destruir la sesión

session_destroy();

header('Location: /login.php');
exit;