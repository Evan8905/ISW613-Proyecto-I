<?php

require_once __DIR__ . '/session.php';

// Generar u obtener el token CSRF de la sesión.
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Validar el token recibido desde un formulario.
function validarCsrf(?string $token): bool
{
    $tokenGuardado = $_SESSION['csrf_token'] ?? '';

    return is_string($tokenGuardado)
        && $tokenGuardado !== ''
        && is_string($token)
        && hash_equals($tokenGuardado, $token);
}