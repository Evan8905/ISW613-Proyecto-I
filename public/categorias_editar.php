<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$id) {
    http_response_code(400);
    exit('Identificador inválido.');
}

$stmt = $dbh->prepare(
    "SELECT id, nombre, dias_max_prestamo
     FROM categorias
     WHERE id = :id
       AND eliminado_en IS NULL
     LIMIT 1"
);

$stmt->execute([':id' => $id]);

$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    http_response_code(404);
    exit('Categoría no encontrada.');
}

$errores = $_SESSION['categoria_editar_errores'] ?? [];
$anterior = $_SESSION['categoria_editar_anterior'] ?? [];

unset(
    $_SESSION['categoria_editar_errores'],
    $_SESSION['categoria_editar_anterior']
);

$categoria = array_merge($categoria, $anterior);

$titulo = 'Editar categoría';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold">Editar categoría</h2>
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

            <form action="/actions/categoria_actualizar.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>">

                <input
                    type="hidden"
                    name="id"
                    value="<?= e((string) $id) ?>">

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
                        value="<?= e($categoria['nombre']) ?>"
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
                        value="<?= e((string) $categoria['dias_max_prestamo']) ?>"
                        required>
                </div>

                <div class="d-flex justify-content-end gap-2">

                    <a href="/categorias.php"
                       class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>

                </div>

            </form>

        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>