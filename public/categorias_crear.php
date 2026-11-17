<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

$errores = $_SESSION['categoria_errores'] ?? [];
$anterior = $_SESSION['categoria_anterior'] ?? [];

unset(
    $_SESSION['categoria_errores'],
    $_SESSION['categoria_anterior']
);

$titulo = 'Registrar categoría';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold">Registrar categoría</h2>
        <p class="text-muted">
            Complete los datos de la nueva categoría.
        </p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/actions/categoria_guardar.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>">

                <div class="mb-3">
                    <label for="nombre" class="form-label">
                        Nombre de la categoría
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        value="<?= e($anterior['nombre'] ?? '') ?>"
                        required>
                </div>

                <div class="mb-4">
                    <label for="dias_max_prestamo" class="form-label">
                        Días máximos de préstamo
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="dias_max_prestamo"
                        name="dias_max_prestamo"
                        min="1"
                        max="365"
                        step="1"
                        value="<?= e($anterior['dias_max_prestamo'] ?? '') ?>"
                        required>
                </div>

                <div class="d-flex justify-content-end gap-2">

                    <a href="/categorias.php"
                       class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Guardar categoría
                    </button>

                </div>

            </form>

        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>