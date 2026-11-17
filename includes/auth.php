<?php

require_once __DIR__ . '/session.php';

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
}