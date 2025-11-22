<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

verificar_permiso(['admin', 'editor']);

// Verificar ID
if (!isset($_GET['id'])) die("ID no especificado");
$id = (int)$_GET['id'];

// Obtener datos actuales
$stmt = $conn->prepare("SELECT * FROM productos_local WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
if (!$producto) die("Producto no encontrado");

// Cargar categorías Woo
$categorias_woo = [];
try {
    require_once 'includes/woo.php';
    $categorias_woo = $woocommerce->get('products/categories', ['per_page' => 100]);
} catch (Exception $e) {}

// ---------------------------------------------------------
// LÓGICA DE PROCESAMIENTO (POST)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- CASO 1: BORRAR FOTO ESPECÍFICA ---
    if (isset($_POST['accion']) && $_POST['accion'] === 'borrar_foto') {
        $tipo = $_POST['tipo_foto']; // 'principal' o 'galeria'
        
        if ($tipo === 'principal') {
            // Borrar solo la referencia a la principal
            $conn->query("UPDATE productos_local SET ruta_imagen_local = '', cambio_pendiente = 1 WHERE id = $id");
        } 
        elseif ($tipo === 'galeria') {
            $indice_a_borrar = (int)$_POST['indice_galeria'];
            $galeria_actual = json_decode($producto['galeria_local'], true);
            
            if (is_array($galeria_actual) && isset($galeria_actual[$indice_a_borrar])) {
                // Eliminar del array y re-indexar (importante array_values)
                unset($galeria_actual[$indice_a_borrar]);
                $nuevo_json = json_encode(array_values($galeria_actual));
                
                // Actualizamos la BD con el nuevo JSON
                $stmt_upd = $conn->prepare("UPDATE productos_local SET galeria_local = ?, cambio_pendiente = 1 WHERE id = ?");
                $stmt_upd->bind_param("si", $nuevo_json, $id);
                $stmt_upd->execute();
            }
        }
        // Recargar página para ver cambios
        echo "<script>window.location.href='editar_local.php?id=$id&msg=Imagen eliminada';</script>";
        exit;
    }

    // --- CASO 2: GUARDAR CAMBIOS DEL PRODUCTO ---
    if (!isset($_POST['accion'])) {
        $nombre = $conn->real_escape_string($_POST['nombre']);
        $sku    = $conn->real_escape_string($_POST['sku']);
        $desc   = $conn->real_escape_string($_POST['descripcion']);
        $desc_corta = $conn->real_escape_string($_POST['descripcion_corta']);
        $precio_reg = (int)$_POST['precio_normal'];
        $precio_sale = !empty($_POST['precio_rebajado']) ? (int)$_POST['precio_rebajado'] : 0;
        $manage_stock = isset($_POST['manage_stock']) ? 1 : 0;
        $stock_qty    = $manage_stock ? (int)$_POST['stock'] : 0;
        $cat_id = (int)$_POST['categoria'];

        // 1. IMAGEN PRINCIPAL (Reemplazo)
        $ruta_principal = $producto['ruta_imagen_local']; 
        if (!empty($_FILES['imagen_principal']['name'])) {
            $ext = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
            $nombre_archivo = time() . "_main_v2." . $ext;
            $ruta_destino = "assets/fotos_temp/" . $nombre_archivo;
            if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $ruta_destino)) {
                $ruta_principal = $ruta_destino;
            }
        }

        // 2. GALERÍA (Añadir nuevas a las existentes o reemplazar)
        // Estrategia: Si subes nuevas, se AÑADEN a las que ya tenías, no reemplazan todo.
        $galeria_array = json_decode($producto['galeria_local'], true) ?? [];
        
        if (!empty($_FILES['galeria']['name'][0])) {
            $total = count($_FILES['galeria']['name']);
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['galeria']['error'][$i] === 0) {
                    $ext = pathinfo($_FILES['galeria']['name'][$i], PATHINFO_EXTENSION);
                    $nombre_archivo = time() . "_galeria_v2_{$i}." . $ext;
                    $ruta_destino = "assets/fotos_temp/" . $nombre_archivo;
                    if (move_uploaded_file($_FILES['galeria']['tmp_name'][$i], $ruta_destino)) {
                        $galeria_array[] = $ruta_destino; // Añadir al array existente
                    }
                }
            }
        }
        $galeria_json = json_encode($galeria_array);

        // 3. UPDATE
        $sql = "UPDATE productos_local SET 
                nombre='$nombre', sku='$sku', descripcion='$desc', descripcion_corta='$desc_corta',
                precio_regular=$precio_reg, precio_rebajado=$precio_sale,
                manage_stock=$manage_stock, stock=$stock_qty,
                categoria_id=$cat_id, ruta_imagen_local='$ruta_principal', galeria_local='$galeria_json',
                cambio_pendiente=1 
                WHERE id=$id";

        if ($conn->query($sql)) {
            echo "<script>window.location.href='productos.php?msg=Producto editado correctamente';</script>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}
?>

<style>
    /* Estilos para las miniaturas con botón de borrar */
    .img-thumb-wrapper {
        position: relative;
        display: inline-block;
        margin: 5px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
    }
    .img-thumb-wrapper img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        display: block;
    }
    .btn-delete-img {
        position: absolute;
        top: 0;
        right: 0;
        background: rgba(220, 53, 69, 0.8);
        color: white;
        border: none;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-delete-img:hover {
        background: rgb(220, 53, 69);
    }
</style>

<div class="container mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-pencil-alt text-primary"></i> Editar Producto (Local)</h3>
        <a href="productos.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" enctype="multipart/form-data" id="mainForm">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary">Información Básica</h5>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Nombre del Producto</label>
                            <input type="text" name="nombre" class="form-control form-control-lg" value="<?php echo $producto['nombre']; ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Código de Barra (SKU)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                <input type="text" name="sku" id="codigoBarra" class="form-control" value="<?php echo $producto['sku']; ?>" required>
                                <button type="button" class="btn btn-outline-primary" onclick="generarCodigo()">
                                    Generar
                                </button>
                            </div>
                        </div>

                        <div class="row bg-light p-3 rounded mb-3 mx-1 border">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="fw-bold">Precio Normal ($)</label>
                                <input type="number" name="precio_normal" class="form-control" value="<?php echo $producto['precio_regular']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="text-danger fw-bold">Precio Oferta</label>
                                <input type="number" name="precio_rebajado" class="form-control border-danger" value="<?php echo $producto['precio_rebajado']; ?>">
                            </div>
                            <div class="col-md-4 border-start">
                                <div class="form-check form-switch mb-2 pt-1">
                                    <input class="form-check-input" type="checkbox" id="checkStock" name="manage_stock" <?php echo ($producto['manage_stock'] ? 'checked' : ''); ?> onchange="toggleStock()">
                                    <label class="form-check-label fw-bold" for="checkStock">Controlar Stock</label>
                                </div>
                                <div id="stockInputGroup" style="display: <?php echo ($producto['manage_stock'] ? 'block' : 'none'); ?>;">
                                    <input type="number" name="stock" class="form-control" value="<?php echo $producto['stock']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción Corta</label>
                            <textarea name="descripcion_corta" class="form-control" rows="2"><?php echo $producto['descripcion_corta']; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción Completa</label>
                            <textarea name="descripcion" class="form-control" rows="5"><?php echo $producto['descripcion']; ?></textarea>
                        </div>

                    </div>
                </div>
            </form> </div>

        <div class="col-lg-4">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 text-primary">Categoría</h5></div>
                <div class="card-body">
                    <?php if (!empty($categorias_woo)): ?>
                        <select name="categoria" form="mainForm" class="form-select" required>
                            <?php foreach($categorias_woo as $cat): ?>
                                <option value='<?php echo $cat->id; ?>' <?php echo ($cat->id == $producto['categoria_id'] ? 'selected' : ''); ?>>
                                    <?php echo $cat->name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="number" name="categoria" form="mainForm" class="form-control" value="<?php echo $producto['categoria_id']; ?>">
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3"><h5 class="mb-0 text-primary">Imágenes</h5></div>
                <div class="card-body">
                    
                    <label class="fw-bold d-block mb-2">Foto Principal</label>
                    
                    <?php if(!empty($producto['ruta_imagen_local'])): ?>
                        <div class="d-flex align-items-center mb-3 bg-light p-2 rounded">
                            <img src="<?php echo $producto['ruta_imagen_local']; ?>" class="rounded border" style="width: 60px; height: 60px; object-fit: cover;">
                            <div class="ms-3 flex-grow-1">
                                <small class="text-muted d-block">Actual</small>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="accion" value="borrar_foto">
                                    <input type="hidden" name="tipo_foto" value="principal">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Quitar imagen">
                                        <i class="fas fa-trash-alt"></i> Quitar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary py-2 small text-center">Sin imagen principal</div>
                    <?php endif; ?>

                    <input type="file" name="imagen_principal" form="mainForm" class="form-control mb-4" accept="image/*">

                    <hr>

                    <label class="fw-bold d-block mb-2">Galería</label>
                    
                    <div class="mb-3">
                        <?php 
                        $imgs_galeria = json_decode($producto['galeria_local'], true);
                        if (!empty($imgs_galeria) && is_array($imgs_galeria)): 
                        ?>
                            <div class="d-flex flex-wrap">
                                <?php foreach($imgs_galeria as $index => $ruta): ?>
                                    <div class="img-thumb-wrapper">
                                        <img src="<?php echo $ruta; ?>">
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="accion" value="borrar_foto">
                                            <input type="hidden" name="tipo_foto" value="galeria">
                                            <input type="hidden" name="indice_galeria" value="<?php echo $index; ?>">
                                            <button type="submit" class="btn-delete-img" title="Borrar esta foto">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small mb-2">La galería está vacía.</div>
                        <?php endif; ?>
                    </div>

                    <input type="file" name="galeria[]" form="mainForm" class="form-control" accept="image/*" multiple>
                    <small class="text-muted">Las nuevas fotos se añadirán a las existentes.</small>

                    <button type="submit" form="mainForm" class="btn btn-primary w-100 mt-4 fw-bold shadow-sm">
                        <i class="fas fa-save"></i> GUARDAR CAMBIOS
                    </button>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
function toggleStock() {
    const check = document.getElementById('checkStock');
    document.getElementById('stockInputGroup').style.display = check.checked ? 'block' : 'none';
}
function generarCodigo() {
    let result = '';
    const characters = '0123456789';
    for (let i = 0; i < 12; i++) { result += characters.charAt(Math.floor(Math.random() * characters.length)); }
    document.getElementById('codigoBarra').value = result;
}
</script>

<?php require_once 'includes/footer.php'; ?>