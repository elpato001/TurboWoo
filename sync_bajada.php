<?php
// Aumentar tiempo máximo de ejecución (5 minutos)
set_time_limit(300); 

require_once 'includes/config.php';
require_once 'includes/db.php'; // Conexión Local
require_once 'includes/functions.php';
require_once 'includes/header.php'; 
require_once 'includes/woo.php'; // Cliente API

// 1. SEGURIDAD
verificar_permiso(['admin']);

$proceso_iniciado = isset($_POST['confirmar']) && $_POST['confirmar'] === 'si';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        
        <div class="card shadow-lg border-0 mt-4">
            <div class="card-header bg-dark text-white p-4">
                <h3 class="mb-0"><i class="fas fa-cloud-download-alt text-info"></i> Descarga Total (Reset)</h3>
                <p class="mb-0 opacity-75">Importar catálogo completo desde WooCommerce</p>
            </div>
            
            <div class="card-body p-4">
                
                <?php if (!$proceso_iniciado): ?>
                    <div class="text-center">
                        <div class="alert alert-warning text-start border-warning border-2">
                            <h4><i class="fas fa-exclamation-triangle"></i> Advertencia Importante</h4>
                            <p>Esta acción reiniciará tu base de datos local:</p>
                            <ul class="mb-0">
                                <li>Se <strong>borrarán</strong> todos los productos locales.</li>
                                <li>Se perderán cambios no subidos ("Sincronizados").</li>
                                <li>Se descargará la última versión de productos, <strong>categorías</strong> e imágenes desde la web.</li>
                            </ul>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="confirmar" value="si">
                            <a href="productos.php" class="btn btn-secondary btn-lg me-2">Cancelar</a>
                            <button type="submit" class="btn btn-danger btn-lg fw-bold">
                                <i class="fas fa-trash-alt"></i> Borrar y Descargar
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <div class="console-log bg-light p-3 border rounded font-monospace" style="max-height: 400px; overflow-y: auto;">
                        <?php
                        try {
                            echo "<p>⏳ Conectando con WooCommerce...</p>";
                            flush(); ob_flush();
                            
                            // 1. VACIAR TABLA LOCAL
                            $conn->query("TRUNCATE TABLE productos_local");
                            echo "<p class='text-success'>✔ Base de datos local limpia.</p>";

                            // 2. PREPARAR SQL (Ahora incluye categoria_id y descripciones)
                            // Nota: Usamos ON DUPLICATE KEY UPDATE por seguridad, aunque el Truncate ya limpió.
                            $sql = "INSERT INTO productos_local 
                                    (id_woo, nombre, precio_regular, precio_rebajado, stock, manage_stock, sku, descripcion, descripcion_corta, categoria_id, imagen_url, cambio_pendiente, es_nuevo) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";
                            
                            $stmt = $conn->prepare($sql);
                            if (!$stmt) throw new Exception("Error SQL: " . $conn->error);

                            // 3. DESCARGAR POR PÁGINAS
                            $pagina = 1;
                            $total_importados = 0;
                            $seguir = true;

                            echo "<p>⬇️ Iniciando descarga...</p>";

                            while ($seguir) {
                                $resultados = $woocommerce->get('products', [
                                    'page' => $pagina,
                                    'per_page' => 50, // Lotes de 50
                                    'status' => 'publish'
                                ]);

                                if (empty($resultados)) {
                                    $seguir = false;
                                    break;
                                }

                                foreach ($resultados as $p) {
                                    // Extracción de datos
                                    $id_woo = $p->id;
                                    $nombre = $p->name;
                                    $sku    = $p->sku;
                                    $desc   = $p->description;
                                    $desc_short = $p->short_description;
                                    
                                    // Precios
                                    $precio_reg  = empty($p->regular_price) ? 0 : (int)$p->regular_price;
                                    $precio_sale = empty($p->sale_price) ? 0 : (int)$p->sale_price;
                                    
                                    // Stock
                                    $manage_stock = $p->manage_stock ? 1 : 0;
                                    $stock_qty    = ($manage_stock && !is_null($p->stock_quantity)) ? (int)$p->stock_quantity : 0;
                                    
                                    // Imagen
                                    $img = !empty($p->images) ? $p->images[0]->src : '';

                                    // CATEGORÍA (Tomamos la primera del array)
                                    $cat_id = 0;
                                    if (!empty($p->categories)) {
                                        $cat_id = $p->categories[0]->id;
                                    }

                                    // Insertar
                                    $stmt->bind_param("isiiiisssis", 
                                        $id_woo, $nombre, $precio_reg, $precio_sale, $stock_qty, $manage_stock, 
                                        $sku, $desc, $desc_short, $cat_id, $img
                                    );
                                    $stmt->execute();
                                    
                                    $total_importados++;
                                }

                                echo "<div class='text-muted small'>... Página $pagina OK (" . count($resultados) . " productos)</div>";
                                $pagina++;
                                
                                echo "<script>document.querySelector('.console-log').scrollTop = document.querySelector('.console-log').scrollHeight;</script>";
                                flush(); ob_flush();
                            }

                            echo "<hr>";
                            echo "<h4 class='text-success fw-bold'>✨ PROCESO TERMINADO</h4>";
                            echo "<p>Se han importado <strong>$total_importados</strong> productos (con sus categorías).</p>";
                            echo "<a href='productos.php' class='btn btn-success w-100 btn-lg'>Ir al Editor Local</a>";

                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>ERROR: " . $e->getMessage() . "</div>";
                        }
                        ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>