<?php // Dashboard ?> 
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Seguridad de Sesión (Por si se accede directo sin pasar por header)
session_start();
if(!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// --- LÓGICA DE DATOS (Solo consultas locales rápidas) ---

// 1. Contar productos totales en local
$query_total = $conn->query("SELECT COUNT(*) as total FROM productos_local");
$total_productos = $query_total->fetch_assoc()['total'];

// 2. Contar cambios pendientes de subir a la nube
$query_pendientes = $conn->query("SELECT COUNT(*) as total FROM productos_local WHERE cambio_pendiente = 1");
$cambios_pendientes = $query_pendientes->fetch_assoc()['total'];

// 3. Obtener rol para personalizar vista
$rol = $_SESSION['rol'];

// Título de la página (lo usará el header)
$page_title = "Inicio";
require_once 'includes/header.php'; 
?>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm bg-primary text-white overflow-hidden">
            <div class="card-body p-4 position-relative">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="fw-bold mb-1">Hola, <?php echo $_SESSION['nombre']; ?> 👋</h2>
                        <p class="mb-0 opacity-75">
                            Panel de Control | Rol: <span class="badge bg-white text-primary"><?php echo strtoupper($rol); ?></span>
                        </p>
                    </div>
                    <div class="col-md-4 text-end d-none d-md-block">
                        <i class="fas fa-bolt fa-4x opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm hover-card">
            <div class="card-body text-center p-4">
                <div class="icon-circle bg-success-light text-success mb-3">
                    <i class="fas fa-cash-register fa-2x"></i>
                </div>
                <h4 class="fw-bold">Pedidos</h4>
                <p class="text-muted small">Gestionar ventas y despachos</p>
                <a href="pedidos.php" class="btn btn-success w-100 rounded-pill fw-bold">
                    Ver Pedidos <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </div>

    <?php if($rol == 'admin' || $rol == 'editor'): ?>
    
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm hover-card">
            <div class="card-body text-center p-4">
                <div class="icon-circle bg-info-light text-info mb-3">
                    <i class="fas fa-tags fa-2x"></i>
                </div>
                <h4 class="fw-bold">Inventario Local</h4>
                <h2 class="display-6 fw-bold my-2"><?php echo number_format($total_productos); ?></h2>
                <p class="text-muted small">Productos descargados</p>
                
                <div class="d-grid gap-2">
                    <a href="productos.php" class="btn btn-outline-primary rounded-pill">
                        Editar Precios
                    </a>
                    <a href="sync_bajada.php" class="btn btn-link text-decoration-none text-muted btn-sm" onclick="return confirm('¿Estás seguro? Esto borrará los cambios locales no guardados y bajará todo de la web.')">
                        <i class="fas fa-sync"></i> Resetear desde Web
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <?php if($cambios_pendientes > 0): ?>
            <div class="card h-100 border-warning shadow-sm">
                <div class="card-body text-center p-4 bg-warning-subtle">
                    <div class="icon-circle bg-warning text-dark mb-3">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Cambios sin Subir</h4>
                    <h2 class="display-6 fw-bold my-2 text-dark"><?php echo $cambios_pendientes; ?></h2>
                    <p class="text-dark small">Productos modificados localmente</p>
                    <a href="sync_subida.php" class="btn btn-dark w-100 rounded-pill fw-bold">
                        <i class="fas fa-cloud-upload-alt"></i> Publicar Cambios
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="icon-circle bg-light text-secondary mb-3">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h4 class="fw-bold text-secondary">Todo Sincronizado</h4>
                    <p class="text-muted my-4">No hay cambios pendientes de subir a la tienda.</p>
                    <button class="btn btn-light w-100 rounded-pill" disabled>
                        Base de Datos al Día
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php endif; // Fin bloque admin/editor ?>

</div>

<style>
    .hover-card { transition: transform 0.2s; }
    .hover-card:hover { transform: translateY(-5px); }
    
    .icon-circle {
        width: 70px; height: 70px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
    }
    .bg-success-light { background-color: #d1e7dd; }
    .bg-info-light { background-color: #cff4fc; }
</style>

<?php require_once 'includes/footer.php'; ?>