<?php
/**
 * BACKEND AJAX: CONSULTA DE PEDIDOS
 * ---------------------------------
 * Este script es llamado por assets/js/app.js cada 30 segundos.
 * Devuelve HTML puro (filas de tabla) listo para insertar.
 */

// Cargar configuración y librería (sin headers HTML)
require_once 'includes/config.php';
require_once 'includes/woo.php';
require_once 'includes/functions.php';

// Verificar sesión (seguridad básica)
session_start();
if (!isset($_SESSION['usuario_id'])) {
    die("<tr><td colspan='6' class='text-center text-danger'>Sesión expirada. Recarga la página.</td></tr>");
}

try {
    // 1. Consultar API de WooCommerce
    // Filtramos por estado 'processing' (Procesando) que suele ser "Pagado y listo para enviar"
    // Si quieres ver todos, quita el parámetro 'status'.
    $parametros = [
        'status' => 'processing', 
        'per_page' => 20,         // Traer los últimos 20
        'orderby' => 'date',
        'order' => 'desc'
    ];

    $pedidos = $woocommerce->get('orders', $parametros);

    // 2. Si no hay pedidos
    if (empty($pedidos)) {
        echo "<tr>
                <td colspan='6' class='text-center py-5 text-muted'>
                    <i class='fas fa-mug-hot fa-3x mb-3 opacity-25'></i><br>
                    No hay pedidos pendientes por despachar.
                </td>
              </tr>";
        exit;
    }

    // 3. Generar Filas HTML
    foreach ($pedidos as $p) {
        
        // Datos básicos
        $id = $p->id;
        $nombre_cliente = $p->billing->first_name . ' ' . $p->billing->last_name;
        $total = formato_dinero($p->total);
        $fecha = date('d/m/Y H:i', strtotime($p->date_created));
        $estado = badge_estado($p->status);
        
        // Lógica para items (resumen corto)
        $cantidad_items = count($p->line_items);
        $resumen_items = $cantidad_items . ' producto(s)';
        if($cantidad_items == 1) {
            $resumen_items = $p->line_items[0]->name; // Si es solo uno, mostrar nombre
        }

        // Detectar si es pedido nuevo (menos de 1 hora) para resaltarlo
        $es_nuevo = (time() - strtotime($p->date_created)) < 3600;
        $clase_fila = $es_nuevo ? 'pedido-nuevo fila-pedido' : 'fila-pedido';

        // HTML DE LA FILA
        ?>
        <tr class="<?php echo $clase_fila; ?>">
            <td class="fw-bold">#<?php echo $id; ?></td>
            
            <td><small><?php echo $fecha; ?></small></td>
            
            <td>
                <div class="d-flex flex-column">
                    <span class="fw-bold"><?php echo $nombre_cliente; ?></span>
                    <small class="text-muted"><?php echo $resumen_items; ?></small>
                </div>
            </td>
            
            <td class="fw-bold text-success"><?php echo $total; ?></td>
            
            <td><?php echo $estado; ?></td>
            
            <td class="text-end">
                <div class="btn-group">
                    <button class="btn btn-dark btn-sm btn-imprimir" data-id="<?php echo $id; ?>" title="Imprimir Ticket Térmico">
                        <i class="fas fa-print"></i>
                    </button>

                    <a href="<?php echo WOO_URL; ?>/wp-admin/post.php?post=<?php echo $id; ?>&action=edit" target="_blank" class="btn btn-outline-secondary btn-sm" title="Ver en WordPress">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php
    }

} catch (Exception $e) {
    // Manejo de Errores de API
    echo "<tr>
            <td colspan='6' class='text-center text-danger py-3'>
                <i class='fas fa-exclamation-triangle'></i> Error conectando a WooCommerce:<br>
                <small>" . $e->getMessage() . "</small>
            </td>
          </tr>";
}
?>