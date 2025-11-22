<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/woo.php';
require_once 'includes/header.php';

verificar_permiso(['admin', 'editor']);

// --- LÓGICA: CREAR CATEGORÍA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_categoria'])) {
    $nombre = $_POST['nombre_cat'];
    
    try {
        $data = ['name' => $nombre];
        $woocommerce->post('products/categories', $data);
        echo "<div class='alert alert-success'>Categoría '$nombre' creada correctamente.</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// --- LÓGICA: LISTAR CATEGORÍAS ---
try {
    $categorias = $woocommerce->get('products/categories', ['per_page' => 100]);
} catch (Exception $e) {
    $categorias = [];
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Nueva Categoría</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" name="nombre_cat" class="form-control" required>
                    </div>
                    <button type="submit" name="nueva_categoria" class="btn btn-primary w-100">Guardar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Categorías Existentes</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Slug</th>
                            <th>Cantidad Productos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categorias as $cat): ?>
                        <tr>
                            <td><?php echo $cat->id; ?></td>
                            <td><strong><?php echo $cat->name; ?></strong></td>
                            <td><?php echo $cat->slug; ?></td>
                            <td><span class="badge bg-secondary"><?php echo $cat->count; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>