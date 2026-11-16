<?php
$titulo = $titulo ?? 'SIGEL';
$mostrarMenu = $mostrarMenu ?? true;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?> | SIGEL</title>

    <!-- Bootstrap 5.3.9 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- CSS personalizado -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body class="bg-light">

    <?php if ($mostrarMenu): ?>
        <header>
            <nav class="navbar navbar-expand-lg bg-white border-bottom">
                <div class="container">

                    <a class="navbar-brand fw-bold" href="/dashboard.php">
                        SIGEL
                    </a>

                    <div class="d-flex gap-3">
                        <a class="nav-link" href="/dashboard.php">
                            Inicio
                        </a>

                        <a class="nav-link" href="/equipos.php">
                            Equipos
                        </a>

                        <a class="nav-link" href="/prestamos.php">
                            Préstamos
                        </a>
                    </div>

                </div>
            </nav>
        </header>
    <?php endif; ?>