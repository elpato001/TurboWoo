<?php // Functions ?> 
<?php
/**
 * FUNCIONES AUXILIARES DE TURBOWOO
 * --------------------------------
 * Aquí colocamos lógica reutilizable para no repetir código en las vistas.
 */

/* ========================================================
   1. FORMATO Y MONEDA (Chile / CLP)
   ======================================================== */

/**
 * Formatea un número a formato de moneda Peso Chileno (sin decimales)
 * Ej: 1500 -> $1.500
 */
function formato_dinero($valor) {
    if (!is_numeric($valor)) return '$0';
    // number_format(numero, decimales, separador_dec, separador_miles)
    return '$' . number_format($valor, 0, ',', '.');
}

/**
 * Limpia cadenas para evitar XSS (Seguridad básica al imprimir en HTML)
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/* ========================================================
   2. INTERFAZ Y ESTADOS DE WOOCOMMERCE
   ======================================================== */

/**
 * Devuelve un BADGE (etiqueta) de Bootstrap según el estado del pedido.
 * Traduce los estados de inglés (API) a español visual.
 */
function badge_estado($estado_woo) {
    $html = '';
    
    switch ($estado_woo) {
        case 'pending':
        case 'on-hold':
            $clase = 'bg-warning text-dark';
            $texto = 'Pendiente';
            $icono = 'fa-clock';
            break;
            
        case 'processing':
            $clase = 'bg-primary';
            $texto = 'Procesando'; // Listo para despachar
            $icono = 'fa-box-open';
            break;
            
        case 'completed':
            $clase = 'bg-success';
            $texto = 'Completado';
            $icono = 'fa-check';
            break;
            
        case 'cancelled':
        case 'failed':
            $clase = 'bg-danger';
            $texto = 'Cancelado';
            $icono = 'fa-times';
            break;
            
        case 'refunded':
            $clase = 'bg-secondary';
            $texto = 'Reembolsado';
            $icono = 'fa-undo';
            break;
            
        default:
            $clase = 'bg-light text-dark border';
            $texto = ucfirst($estado_woo);
            $icono = 'fa-question';
            break;
    }

    return "<span class='badge $clase'><i class='fas $icono me-1'></i> $texto</span>";
}

/* ========================================================
   3. SEGURIDAD Y ROLES
   ======================================================== */

/**
 * Verifica si el usuario actual tiene permiso para ver la página.
 * Si no tiene permiso, redirige al dashboard o muestra error.
 * * Uso: verificar_permiso(['admin', 'editor']);
 */
function verificar_permiso($roles_permitidos = []) {
    // 1. Asegurar que hay sesión iniciada
    if (!isset($_SESSION['rol'])) {
        header('Location: index.php');
        exit;
    }

    $mi_rol = $_SESSION['rol'];

    // 2. Si el array está vacío, cualquiera logueado entra
    if (empty($roles_permitidos)) {
        return true;
    }

    // 3. Verificar si mi rol está en la lista permitida
    if (!in_array($mi_rol, $roles_permitidos)) {
        // Si es una petición AJAX, matamos el script diferente
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            die("Error: No tienes permiso para realizar esta acción.");
        }
        
        // Si es navegación normal, redirigimos
        header('Location: dashboard.php?error=acceso_denegado');
        exit;
    }
}

/**
 * Helper para verificar si soy Admin (útil para ifs rápidos en el HTML)
 */
function es_admin() {
    return (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');
}

/**
 * Depuración bonita (para cuando estés programando y algo falle)
 */
function dd($data) {
    echo '<pre style="background: #333; color: #0f0; padding: 10px; z-index:9999; position:relative;">';
    print_r($data);
    echo '</pre>';
    die();
}
?>