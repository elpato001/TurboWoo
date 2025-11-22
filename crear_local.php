<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Cargar categorías Woo (si hay internet)
$categorias_woo = [];
try {
    require_once 'includes/woo.php';
    $categorias_woo = $woocommerce->get('products/categories', ['per_page' => 100]);
} catch (Exception $e) {}

verificar_permiso(['admin', 'editor']);

// --- LÓGICA DE GUARDADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $sku    = $conn->real_escape_string($_POST['sku']);
    $desc   = $conn->real_escape_string($_POST['descripcion']);
    $desc_corta = $conn->real_escape_string($_POST['descripcion_corta']);
    
    $precio_reg = (int)$_POST['precio_normal'];
    $precio_sale = !empty($_POST['precio_rebajado']) ? (int)$_POST['precio_rebajado'] : 0;
    
    // Stock
    $manage_stock = isset($_POST['manage_stock']) ? 1 : 0;
    $stock_qty    = $manage_stock ? (int)$_POST['stock'] : 0;

    $cat_id = (int)$_POST['categoria'];

    // 1. IMAGEN PRINCIPAL
    $ruta_principal = '';
    if (!empty($_FILES['imagen_principal']['name'])) {
        $ext = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = time() . "_main." . $ext;
        $ruta_destino = "assets/fotos_temp/" . $nombre_archivo;
        if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $ruta_destino)) {
            $ruta_principal = $ruta_destino;
        }
    }

    // 2. GALERÍA
    $rutas_galeria = [];
    if (!empty($_FILES['galeria']['name'][0])) {
        $total = count($_FILES['galeria']['name']);
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['galeria']['error'][$i] === 0) {
                $ext = pathinfo($_FILES['galeria']['name'][$i], PATHINFO_EXTENSION);
                $nombre_archivo = time() . "_galeria_{$i}." . $ext;
                $ruta_destino = "assets/fotos_temp/" . $nombre_archivo;
                if (move_uploaded_file($_FILES['galeria']['tmp_name'][$i], $ruta_destino)) {
                    $rutas_galeria[] = $ruta_destino;
                }
            }
        }
    }
    $galeria_json = json_encode($rutas_galeria);

    // 3. INSERTAR
    $sql = "INSERT INTO productos_local 
            (id_woo, nombre, precio_regular, precio_rebajado, stock, manage_stock, sku, descripcion, descripcion_corta, categoria_id, ruta_imagen_local, galeria_local, es_nuevo, cambio_pendiente) 
            VALUES 
            (0, '$nombre', $precio_reg, $precio_sale, $stock_qty, $manage_stock, '$sku', '$desc', '$desc_corta', $cat_id, '$ruta_principal', '$galeria_json', 1, 1)";

    if ($conn->query($sql)) {
        echo "<script>window.location.href='productos.php?msg=Producto creado correctamente';</script>";
    } else {
        echo "<div class='alert alert-danger'>Error SQL: " . $conn->error . "</div>";
    }
}
?>

<div class="container mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-bolt text-warning"></i> Nuevo Producto (Local)</h3>
        <a href="productos.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary">Información Básica</h5>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Nombre del Producto</label>
                            <input type="text" name="nombre" class="form-control form-control-lg" required placeholder="Ej: Audífonos Gamer...">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Código de Barra (SKU)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                <input type="text" name="sku" id="codigoBarra" class="form-control" required placeholder="Escanea o genera uno...">
                                <button type="button" class="btn btn-outline-primary" onclick="generarCodigo()">
                                    Generar Aleatorio
                                </button>
                            </div>
                        </div>

                        <div class="row bg-light p-3 rounded mb-3 mx-1 border">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label fw-bold">Precio Normal ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="precio_normal" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-danger">Precio Oferta</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="precio_rebajado" class="form-control border-danger" placeholder="0">
                                </div>
                            </div>
                            
                            <div class="col-md-4 border-start">
                                <div class="form-check form-switch mb-2 pt-1">
                                    <input class="form-check-input" type="checkbox" id="checkStock" name="manage_stock" checked onchange="toggleStock()">
                                    <label class="form-check-label fw-bold" for="checkStock">Controlar Stock</label>
                                </div>
                                <div id="stockInputGroup">
                                    <input type="number" name="stock" class="form-control" placeholder="Cantidad" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción Corta</label>
                            <textarea name="descripcion_corta" class="form-control" rows="2" placeholder="Breve resumen..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción Completa</label>
                            <textarea name="descripcion" class="form-control" rows="5" placeholder="Detalles técnicos..."></textarea>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary">Categoría</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($categorias_woo)): ?>
                            <select name="categoria" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach($categorias_woo as $cat): ?>
                                    <option value="<?php echo $cat->id; ?>"><?php echo $cat->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="number" name="categoria" class="form-control" placeholder="ID Manual" required>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary">Imágenes</h5>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Imagen Principal</label>
                            <input type="file" name="imagen_principal" class="form-control" accept="image/*" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Galería</label>
                            <input type="file" name="galeria[]" class="form-control" accept="image/*" multiple>
                            <small class="text-muted">Usa Ctrl para subir varias.</small>
                        </div>
                        
                        <hr>
                        <button type="submit" class="btn btn-primary w-100 btn-lg fw-bold shadow">
                            <i class="fas fa-save"></i> GUARDAR EN DISCO
                        </button>

                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
function toggleStock() {
    const check = document.getElementById('checkStock');
    document.getElementById('stockInputGroup').style.display = check.checked ? 'block' : 'none';
}

function generarCodigo() {
    let result = '';
    const characters = '0123456789';
    for (let i = 0; i < 12; i++) {
        result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    document.getElementById('codigoBarra').value = result;
}
</script>

<?php require_once 'includes/footer.php'; ?>