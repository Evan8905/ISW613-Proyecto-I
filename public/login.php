<?php
require_once __DIR__ . '/../includes/session.php';

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Iniciar sesión</title>

    <!-- Bootstrap 5.3.9 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <main class="container min-vh-100 d-flex align-items-center justify-content-center">

        <div class="card shadow border-0 rounded-4 w-100" style="max-width: 420px;">
            <div class="card-body p-4 p-md-5">
                <!-- Encabezado -->
                <div class="text-center mb-4">
                    <h2 class="fw-bold">Iniciar sesión</h2>
                    <p class="text-muted">
                        Ingresa tus credenciales
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulario -->
                <form action="actions/login_action.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Correo electrónico
                        </label>

                        <input type="email" class="form-control" id="email" name="email"
                            placeholder="nombre@ejemplo.com" autocomplete="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Contraseña
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Ingresa tu contraseña" autocomplete="current-password" required>
                    </div>

                    <div class="d-flex align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">

                            <label class="form-check-label" for="remember">
                                Recordarme
                            </label>
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        Iniciar sesión
                    </button>

                </form>

            </div>
        </div>

    </main>
</body>

</html>