<?php
require_once __DIR__ . '/../includes/auth.php';

// llama a la funcion para impedir el acceso sin iniciar sesión
requireAuth();

$titulo = 'Dashboard';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container min-vh-100 d-flex align-items-center justify-content-center">

    <div class="card shadow border-0 rounded-4 w-100"
         style="max-width: 420px;">

        <div class="card-body p-4 p-md-5 text-center">

            <h2 class="fw-bold">
                Bienvenido a SIGEL
            </h2>

            <p class="text-muted mb-0">
                Hola, <?= htmlspecialchars(
                    $_SESSION['usuario_nombre'] ?? 'Usuario',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>.
            </p>

        </div>
    </div>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>