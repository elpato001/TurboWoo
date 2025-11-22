<?php // Pedidos ?> 
<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Verificación de sesión básica (El header también lo hace, pero es doble seguridad)
session_start();
if(!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$page_title = "Pedidos en Vivo";
require_once 'includes/header.php'; 
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="fw-bold">
            <i class="fas fa-cash-register text-success me-2"></i> Gestión de Pedidos
        </h2>
        <p class="text-muted mb-0">
            Monitor en tiempo real | 
            <span id="loading-indicator" class="badge bg-light text-dark border spinner-border-sm" style="display:none;">
                <i class="fas fa-circle-notch fa-spin text-primary"></i> Actualizando...
            </span>
            <span class="text-success small"><i class="fas fa-wifi"></i> Conectado a WooCommerce</span>
        </p>
    </div>
    <div class="col-md-6 text-end">
        <button onclick="cargarPedidos()" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-sync-alt"></i> Refrescar Ahora
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 15%;">Fecha</th>
                        <th style="width: 30%;">Cliente / Items</th>
                        <th style="width: 15%;">Total</th>
                        <th style="width: 15%;">Estado</th>
                        <th style="width: 15%; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                
                <tbody id="contenedor-pedidos-live">
                    
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <h5 class="text-muted">Conectando con la tienda...</h5>
                            <small class="text-muted">Esto puede tomar unos segundos dependiendo de tu internet.</small>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3 d-flex gap-3 justify-content-center text-muted small">
    <div class="d-flex align-items-center">
        <div style="width: 15px; height: 15px; background: #f0fff4; border-left: 3px solid #198754; margin-right: 5px;"></div>
        Pedido Nuevo (< 1h)
    </div>
    <div class="d-flex align-items-center">
        <div style="width: 15px; height: 15px; background: #fff; border-left: 3px solid #2a5298; margin-right: 5px;"></div>
        Pedido Normal
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>