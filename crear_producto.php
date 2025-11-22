<?php
// Aumentamos memoria y tiempo para subida de imágenes
ini_set('memory_limit', '256M');
set_time_limit(300);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/woo.php';
require_once 'includes/wp_helper.php'; // El helper de imágenes
require_once 'includes/header.php';

verificar_permiso(['admin', 'editor']);

// Obtener categorías para el select
try {
    $categorias_woo = $woocommerce->get('products/categories', ['per_page' => 100]);
} catch (Exception $e) { $categorias_woo = []; }

// --- PROCESAR FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    echo "<div class='alert alert-info'><i class='fas fa-spinner fa-spin'></i> Procesando creación del producto... Por favor espera.</div>";
    
    try {
        // 1. Subir Imagen Principal
        $imagen_id = 0;
        if (!empty($_FILES['imagen_principal']['name'])) {
            $imagen_id = subir_imagen_a_wordpress($_FILES['imagen_principal']);
        }

        // 2. Subir Galería
        $galeria_ids = [];
        if (!empty($_FILES['galeria']['name'][0])) {
            $total_files = count($_FILES['galeria']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                $file_array = [
                    'name' => $_FILES['galeria']['name'][$i],
                    'type' => $_FILES['galeria']['type'][$i],
                    'tmp_name' => $_FILES['galeria']['tmp_name'][$i],
                    'error' => $_FILES['galeria']['error'][$i],
                    'size' => $_FILES['galeria']['size'][$i]
                ];
                $id_subido = subir_imagen_a_wordpress($file_array);
                if ($id_subido) {
                    $galeria_ids[] = $id_subido;
                }
            }
        }

        // 3. Preparar datos del producto
        $producto_data = [
            'name' => $_POST['nombre'],
            'type' => 'simple',
            'regular_price' => $_POST['precio_normal'],
            'description' => $_POST['descripcion'],
            'short_description' => $_POST['descripcion_corta'],
            'categories' => [],
            'images' => []
        ];

        // Agregar precio oferta si existe
        if (!empty($_POST['precio_rebajado'])) {
            $producto_data['sale_price'] = $_POST['precio_rebajado'];
        }

        // Asignar Categoría
        if (!empty($_POST['categoria'])) {
            $producto_data['categories'][] = ['id' => (int)$_POST['categoria']];
        }

        // Asignar Imágenes
        if ($imagen_id) {
            $producto_data['images'][] = ['id' => $imagen_id];
        }
        foreach ($galeria_ids as $g_id) {
            $producto_data['images'][] = ['id' => $g_id];
        }

        // 4. ENVIAR A WOOCOMMERCE
        $nuevo_producto = $woocommerce->post('products', $producto_data);

        // 5. GUARDAR EN BASE DE DATOS LOCAL (Para tenerlo listo para editar)
        $stmt = $conn->prepare("INSERT INTO productos_local (id_woo, nombre, precio_regular, stock, sku, imagen_url, cambio_pendiente) VALUES (?, ?, ?, ?, ?, ?, 0)");
        
        $p_id = $nuevo_producto->id;
        $p_nombre = $nuevo_producto->name;
        $p_precio = empty($nuevo_producto->regular_price) ? 0 : (int)$nuevo_producto->regular_price;
        $p_stock = 0; // Por defecto 0 hasta que se gestione stock
        $p_sku = $nuevo_producto->sku;
        $p_img = !empty($nuevo_producto->images) ? $nuevo_producto->images[0]->src : '';

        $stmt->bind_param("isdiss", $p_id, $p_nombre, $p_precio, $p_stock, $p_sku, $p_img);
        $stmt->execute();

        echo "<script>window.location.href='productos.php?msg=Producto creado exitosamente';</script>";

    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error al crear: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form method="POST" enctype="multipart/form-data">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Crear Nuevo Producto</h4>
                        <a href="productos.php" class="btn btn-sm btn-light text-primary fw-bold">Volver</a>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row g-4">
                            
                            <div class="col-md-8">
                                <h5 class="text-muted border-bottom pb-2 mb-3">Información Básica</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nombre del Producto</label>
                                    <input type="text" name="nombre" class="form-control form-control-lg" required placeholder="Ej: Notebook Gamer ASUS...">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Precio Normal ($)</label>
                                        <input type="number" name="precio_normal" class="form-control" required placeholder="10000">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-danger">Precio Oferta ($)</label>
                                        <input type="number" name="precio_rebajado" class="form-control" placeholder="Opcional">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Descripción Corta</label>
                                    <textarea name="descripcion_corta" class="form-control" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Descripción Completa</label>
                                    <textarea name="descripcion" class="form-control" rows="5"></textarea>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <h5 class="text-muted border-bottom pb-2 mb-3">Organización</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Categoría</label>
                                    <div class="d-flex gap-2">
                                        <select name="categoria" class="form-select" required>
                                            <option value="">Seleccionar...</option>
                                            <?php foreach($categorias_woo as $cat): ?>
                                                <option value="<?php echo $cat->id; ?>"><?php echo $cat->name; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <a href="categorias.php" class="btn btn-outline-secondary" title="Crear nueva"><i class="fas fa-plus"></i></a>
                                    </div>
                                </div>

                                <h5 class="text-muted border-bottom pb-2 mb-3 mt-4">Multimedia</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Imagen Principal</label>
                                    <input type="file" name="imagen_principal" class="form-control" accept="image/*" required>
                                    <small class="text-muted">Se usará como portada.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Galería (Múltiples)</label>
                                    <input type="file" name="galeria[]" class="form-control" accept="image/*" multiple>
                                    <small class="text-muted">Mantén Ctrl para seleccionar varias.</small>
                                </div>
                            </div>

                        </div>
                    </div>
                    
                    <div class="card-footer bg-white p-3 text-end">
                        <button type="submit" class="btn btn-success btn-lg px-5 fw-bold">
                            <i class="fas fa-save"></i> Publicar Producto
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>