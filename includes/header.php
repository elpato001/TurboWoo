<?php
// 1. INICIO DE SESIÓN Y SEGURIDAD
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay usuario logueado, expulsar al Login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Cargamos funciones auxiliares
require_once __DIR__ . '/functions.php';

// Obtener datos del usuario para la interfaz
$mi_nombre = $_SESSION['nombre'] ?? 'Usuario';
$mi_rol    = $_SESSION['rol'] ?? 'vendedor';
$titulo    = isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($titulo); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-4 no-print">
  <div class="container">
    
    <a class="navbar-brand" href="dashboard.php">
        <i class="fas fa-bolt text-warning me-2"></i>
        <?php echo defined('APP_NAME') ? APP_NAME : 'TurboWoo'; ?>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="menuPrincipal">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-home"></i> Inicio
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : ''; ?>" href="pedidos.php">
                <i class="fas fa-box"></i> Pedidos
            </a>
        </li>

        <?php if ($mi_rol === 'admin' || $mi_rol === 'editor'): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-tags"></i> Inventario
                </a>
                <ul class="dropdown-menu">
                    <li><h6 class="dropdown-header">Gestión Local (Offline)</h6></li>
                    
                    <li><a class="dropdown-item fw-bold text-primary" href="crear_local.php">⚡ Nuevo Producto (Rápido)</a></li>
                    
                    <li><a class="dropdown-item" href="productos.php">✏️ Editar Precios / Stock</a></li>
                    <li><a class="dropdown-item" href="categorias.php">📂 Categorías</a></li>
                    
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header">Nube (WooCommerce)</h6></li>
                    
                    <li><a class="dropdown-item" href="sync_subida.php">☁️ Publicar Cambios (Subir)</a></li>
                    <li><a class="dropdown-item text-danger" href="sync_bajada.php" onclick="return confirm('ATENCIÓN: Se borrarán todos los datos locales y se descargarán de nuevo de la web. ¿Seguro?')">⬇️ Resetear desde Web</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if ($mi_rol === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="install/index.php" target="_blank" title="Configuración">
                    <i class="fas fa-cog"></i>
                </a>
            </li>
        <?php endif; ?>

      </ul>

      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle"></i> <?php echo e($mi_nombre); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text text-muted small">Rol: <?php echo ucfirst($mi_rol); ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container" id="main-container">
    
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4 no-print" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo e($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4 no-print" role="alert">
            <i class="fas fa-exclamation-circle"></i> 
            <?php 
                if($_GET['error'] == 'acceso_denegado') echo "No tienes permisos para ver esa sección.";
                else echo e($_GET['error']); 
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>