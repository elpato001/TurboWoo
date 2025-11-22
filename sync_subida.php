<?php
set_time_limit(600); 
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/woo.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

if(!defined('WP_ADMIN_USER') || !defined('WP_APP_PASS')) die("<div class='alert alert-danger'>Faltan credenciales WP.</div>");

// Función Subida
function subir_foto_desde_disco($ruta_local) {
    if (!file_exists($ruta_local)) return null;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $ruta_local);
    finfo_close($finfo);
    $file_name = basename($ruta_local);
    $file_data = file_get_contents($ruta_local);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, WOO_URL . '/wp-json/wp/v2/media');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $auth = base64_encode(WP_ADMIN_USER . ':' . WP_APP_PASS);
    $headers = ['Content-Disposition: attachment; filename="' . $file_name . '"', 'Content-Type: ' . $mime_type, 'Authorization: Basic ' . $auth];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 201) ? json_decode($res, true)['id'] : null;
}

// --- LÓGICA DE SYNC ---
$pendientes = $conn->query("SELECT * FROM productos_local WHERE cambio_pendiente = 1");
$total = $pendientes->num_rows;
$iniciar = isset($_POST['go']);
?>

<div class="container mt-4">
    <h3 class="mb-3">☁️ Sincronización Masiva (Final)</h3>
    
    <?php if (!$iniciar): ?>
        <div class="alert alert-info"><strong><?php echo $total; ?></strong> cambios pendientes.</div>
        <form method="POST"><input type="hidden" name="go" value="1"><button class="btn btn-success w-100 btn-lg">INICIAR</button></form>
    <?php else: ?>
        <div class="console-log bg-dark text-white p-3 font-monospace rounded" style="height:400px; overflow-y:scroll;">
            <?php
            while ($p = $pendientes->fetch_assoc()) {
                echo "<hr>Producto: <strong>" . $p['nombre'] . "</strong>... "; flush(); ob_flush();

                try {
                    // ELIMINAR
                    if ($p['eliminar_pendiente'] == 1) {
                        $woocommerce->delete('products/' . $p['id_woo'], ['force' => true]);
                        $conn->query("DELETE FROM productos_local WHERE id = " . $p['id']);
                        echo "<span class='text-danger'>ELIMINADO.</span>";
                        continue;
                    }

                    // PREPARAR IMÁGENES (Principal + Galería)
                    $all_images = [];
                    
                    // 1. Imagen Principal
                    if (!empty($p['ruta_imagen_local'])) {
                        echo "Subiendo Principal... "; flush(); ob_flush();
                        $main_id = subir_foto_desde_disco($p['ruta_imagen_local']);
                        if ($main_id) $all_images[] = ['id' => $main_id];
                    }

                    // 2. Galería
                    if (!empty($p['galeria_local'])) {
                        $rutas = json_decode($p['galeria_local'], true);
                        if (is_array($rutas)) {
                            foreach($rutas as $ruta) {
                                echo "Subiendo Galería... "; flush(); ob_flush();
                                $g_id = subir_foto_desde_disco($ruta);
                                if ($g_id) $all_images[] = ['id' => $g_id];
                            }
                        }
                    }

                    // DATOS COMUNES
                    $data = [
                        'name' => $p['nombre'],
                        'sku' => $p['sku'],
                        'regular_price' => (string)$p['precio_regular'],
                        'sale_price' => empty($p['precio_rebajado']) ? '' : (string)$p['precio_rebajado'],
                        'description' => $p['descripcion'],
                        'short_description' => $p['descripcion_corta'],
                        'manage_stock' => (bool)$p['manage_stock'],
                        'stock_quantity' => (bool)$p['manage_stock'] ? (int)$p['stock'] : null
                    ];

                    if ($p['categoria_id'] > 0) $data['categories'] = [['id' => $p['categoria_id']]];
                    
                    // Solo enviamos imágenes si hay nuevas locales (para no borrar las que ya tenía Woo si no las tocamos)
                    // En este script simplificado, si es NUEVO, enviamos todo. Si es update, solo si hay cambios.
                    if ($p['es_nuevo'] == 1 || !empty($all_images)) {
                         if (!empty($all_images)) $data['images'] = $all_images;
                    }

                    // ENVIAR
                    if ($p['es_nuevo'] == 1) {
                        $res = $woocommerce->post('products', $data);
                        $conn->query("UPDATE productos_local SET id_woo={$res->id}, es_nuevo=0, cambio_pendiente=0 WHERE id={$p['id']}");
                        echo "<span class='text-success'>CREADO (ID {$res->id}).</span>";
                    } else {
                        $woocommerce->put('products/' . $p['id_woo'], $data);
                        $conn->query("UPDATE productos_local SET cambio_pendiente=0 WHERE id={$p['id']}");
                        echo "<span class='text-info'>ACTUALIZADO.</span>";
                    }

                } catch (Exception $e) { echo "<span class='text-danger'>ERROR: " . $e->getMessage() . "</span>"; }
                
                echo "<script>document.querySelector('.console-log').scrollTop = document.querySelector('.console-log').scrollHeight;</script>";
                flush(); ob_flush();
            }
            ?>
            <br>✅ TERMINADO
        </div>
        <a href="productos.php" class="btn btn-primary mt-3">Volver</a>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>