<?php // Ticket 80mm ?> 
<?php
require_once 'includes/config.php';
require_once 'includes/woo.php';
require_once 'includes/functions.php';

// Verificar sesión
session_start();
if(!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado");
}

// Validar ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No se especificó un ID de pedido.");
}

$order_id = $_GET['id'];

try {
    // Consultar datos del pedido
    $pedido = $woocommerce->get('orders/' . $order_id);
} catch (Exception $e) {
    die("Error al obtener el pedido: " . $e->getMessage());
}

// Preparar datos para impresión
$fecha = date('d/m/Y H:i', strtotime($pedido->date_created));
$cliente = $pedido->billing->first_name . ' ' . $pedido->billing->last_name;
$telefono = $pedido->billing->phone;
$email = $pedido->billing->email;
$metodo_pago = $pedido->payment_method_title;
$nota_cliente = $pedido->customer_note;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $order_id; ?></title>
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Estilos inline de refuerzo para la vista previa en pantalla */
        body {
            background-color: #555; /* Fondo oscuro para resaltar el ticket en pantalla */
            display: flex;
            justify-content: center;
            padding-top: 20px;
            margin: 0;
        }
        #area-impresion {
            background: white;
            width: 80mm; /* Simulación visual del papel */
            min-height: 150mm;
            padding: 5mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
        }
        .btn-cerrar {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: sans-serif;
            font-weight: bold;
        }
        
        /* AL IMPRIMIR: Reseteamos fondo y márgenes */
        @media print {
            body { background: none; display: block; padding: 0; }
            #area-impresion { box-shadow: none; width: 100%; min-height: auto; padding: 0; margin: 0; }
            .btn-cerrar { display: none; }
        }

        /* Utilidades de Ticket */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .borde-inf { border-bottom: 1px dashed #000; margin: 5px 0; }
        .borde-doble { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; margin: 5px 0; }
        .items-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .items-table th { text-align: left; border-bottom: 1px solid #000; }
        .items-table td { padding: 2px 0; vertical-align: top; }
    </style>
</head>
<body>

    <button onclick="window.close()" class="btn-cerrar">CERRAR VENTANA</button>

    <div id="area-impresion">
        
        <div class="ticket-header">
            <div class="text-center">
                <h3 style="margin: 0;"><?php echo defined('APP_NAME') ? APP_NAME : 'INSIS'; ?></h3>
                <p style="margin: 2px 0; font-size: 10px;">Servicio Técnico y Tecnología</p>
            </div>
            <div class="borde-inf"></div>
            <div>
                <strong>Pedido: #<?php echo $order_id; ?></strong><br>
                Fecha: <?php echo $fecha; ?><br>
            </div>
            <div class="borde-inf"></div>
            <div>
                <strong>Cliente:</strong><br>
                <?php echo $cliente; ?><br>
                Tel: <?php echo $telefono; ?><br>
            </div>
        </div>

        <div class="ticket-body">
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="10%">Cant</th>
                        <th width="60%">Producto</th>
                        <th width="30%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedido->line_items as $item): ?>
                    <tr>
                        <td><?php echo $item->quantity; ?></td>
                        <td>
                            <?php echo $item->name; ?>
                            <?php 
                            // Mostrar variaciones si existen (ej: Talla: L)
                            if (!empty($item->meta_data)) {
                                echo "<br><small style='color:#333'>(";
                                foreach($item->meta_data as $meta) {
                                    echo $meta->value . " ";
                                }
                                echo ")</small>";
                            }
                            ?>
                        </td>
                        <td class="text-right"><?php echo formato_dinero($item->total); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="ticket-total">
            <table width="100%">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right"><?php echo formato_dinero($pedido->total - $pedido->shipping_total); ?></td>
                </tr>
                <?php if($pedido->shipping_total > 0): ?>
                <tr>
                    <td>Envío:</td>
                    <td class="text-right"><?php echo formato_dinero($pedido->shipping_total); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="fw-bold" style="font-size:14px; padding-top:5px;">TOTAL:</td>
                    <td class="text-right fw-bold" style="font-size:14px; padding-top:5px;"><?php echo formato_dinero($pedido->total); ?></td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 10px;">
            <strong>Método de Pago:</strong><br>
            <?php echo $metodo_pago; ?>
        </div>

        <?php if(!empty($nota_cliente)): ?>
        <div style="margin-top: 10px; border: 1px solid #000; padding: 5px;">
            <strong>Nota del Cliente:</strong><br>
            <?php echo $nota_cliente; ?>
        </div>
        <?php endif; ?>

        <div class="ticket-footer">
            <br>
            *** GRACIAS POR SU COMPRA ***<br>
            www.insiscomputacion.cl
            <br><br>.
        </div>

    </div>

    <script>
        // Auto-imprimir al cargar
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>