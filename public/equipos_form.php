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

$id = null;
$esEdicion = isset($_GET['id']);

$equipo = [
    'codigo' => '',
    'nombre' => '',
    'categoria_id' => '',
    'estado' => 'Disponible'
];

if ($esEdicion) {

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
        "SELECT id, codigo, nombre, categoria_id, estado
         FROM equipos
         WHERE id = :id
           AND eliminado_en IS NULL
         LIMIT 1"
    );

    $stmt->execute([':id' => $id]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$equipo) {
        http_response_code(404);
        exit('Equipo no encontrado.');
    }
}

// Categorías activas.
$stmt = $dbh->query(
    "SELECT id, nombre
     FROM categorias
     WHERE eliminado_en IS NULL
     ORDER BY nombre"
);

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recuperar errores del formulario correspondiente.
$claveFormulario = $esEdicion ? 'editar_' . $id : 'crear';
$errores = $_SESSION['equipo_errores'][$claveFormulario] ?? [];
$anterior = $_SESSION['equipo_anterior'][$claveFormulario] ?? [];

unset(
    $_SESSION['equipo_errores'][$claveFormulario],
    $_SESSION['equipo_anterior'][$claveFormulario]
);

$equipo = array_merge($equipo, $anterior);

$titulo = $esEdicion ? 'Editar equipo' : 'Registrar equipo';
$mostrarMenu = true;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <h2 class="fw-bold mb-4">
        <?= $esEdicion ? 'Editar equipo' : 'Registrar equipo' ?>
    </h2>

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

            <?php if (empty($categorias)): ?>
                <div class="alert alert-warning">
                    No existen categorías disponibles.
                    Debe registrar una categoría primero.
                </div>
            <?php endif; ?>

            <form
                action="<?= $esEdicion
                    ? '/actions/equipo_actualizar.php'
                    : '/actions/equipo_guardar.php' ?>"
                method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>">

                <?php if ($esEdicion): ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?= e((string) $id) ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="codigo" class="form-label">
                        Código del equipo
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="codigo"
                        name="codigo"
                        maxlength="50"
                        value="<?= e($equipo['codigo']) ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label for="nombre" class="form-label">
                        Nombre del equipo
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nombre"
                        name="nombre"
                        maxlength="150"
                        value="<?= e($equipo['nombre']) ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label for="categoria_id" class="form-label">
                        Categoría
                    </label>

                    <select
                        class="form-select"
                        id="categoria_id"
                        name="categoria_id"
                        required>

                        <option value="">Seleccione una categoría</option>

                        <?php foreach ($categorias as $categoria): ?>
                            <option
                                value="<?= e((string) $categoria['id']) ?>"
                                <?= (string) $equipo['categoria_id'] ===
                                    (string) $categoria['id']
                                    ? 'selected'
                                    : '' ?>>

                                <?= e($categoria['nombre']) ?>

                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="mb-4">
                    <label for="estado" class="form-label">
                        Estado
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="estado"
                        name="estado"
                        maxlength="30"
                        value="<?= e($equipo['estado']) ?>"
                        required>
                </div>

                <div class="d-flex justify-content-end gap-2">

                    <a href="/equipos.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        <?= empty($categorias) ? 'disabled' : '' ?>>
                        <?= $esEdicion ? 'Guardar cambios' : 'Registrar equipo' ?>
                    </button>

                </div>

            </form>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>