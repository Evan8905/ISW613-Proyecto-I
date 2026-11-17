<?php

require_once __DIR__ . '/session.php';
//const TIEMPO_INACTIVIDAD = 300; // 5 minutos 
//const TIEMPO_INACTIVIDAD = 10; // 10 Segundos solo para pruebas
const TIEMPO_INACTIVIDAD = 900; // 15 minutos solo para pruebas

 //Verifica que el usuario tenga una sesión autenticada.
function requireAuth(): void
{
    if (
        !isset($_SESSION['usuario_id']) ||
        ($_SESSION['autenticado'] ?? false) !== true
    ) {
        header('Location: /login.php');
        exit;
    }

    // Comprobar expiración por inactividad.
    $ultimoAcceso = $_SESSION['ultimo_acceso'] ?? 0;

    if (time() - $ultimoAcceso >= TIEMPO_INACTIVIDAD) {

        // Eliminar datos de sesión.
        $_SESSION = [];

        // Eliminar cookie de sesión.
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

        session_destroy();

        header('Location: /login.php?expired=1');
        exit;
    }

    // Actualizar la última actividad.
    $_SESSION['ultimo_acceso'] = time();

}