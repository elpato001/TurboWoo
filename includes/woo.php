<?php
/**
 * CONECTOR WOOCOMMERCE API
 * ------------------------
 * Inicializa la librería oficial 'automattic/woocommerce' usando
 * las constantes definidas en config.php
 */

// 1. Cargar el Autoloader de Composer
// (Subimos un nivel desde 'includes' para encontrar la carpeta 'vendor')
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die("<h3>Error Crítico:</h3> No se encuentra la carpeta <code>vendor</code>.<br>Por favor ejecuta <code>composer install</code> en la raíz del proyecto.");
}

require __DIR__ . '/../vendor/autoload.php';

use Automattic\WooCommerce\Client;

// 2. Verificar que las constantes de configuración existan
if (!defined('WOO_URL') || !defined('WOO_CK') || !defined('WOO_CS')) {
    // Si este archivo se carga antes que config.php, detenemos todo.
    die('Acceso denegado: Configuración de WooCommerce no detectada.');
}

// 3. Instanciar el Cliente
try {
    $woocommerce = new Client(
        WOO_URL, // URL definida en config.php
        WOO_CK,  // Consumer Key
        WOO_CS,  // Consumer Secret
        [
            'version' => 'wc/v3',      // Versión de la API (v3 es la más estable actual)
            'timeout' => 60,           // Aumentamos timeout a 60s por si tu hosting es lento
            'verify_ssl' => false,     // CRÍTICO PARA XAMPP: Evita errores de certificado SSL local
            'wp_api' => true,          // Usar la integración WP REST API estándar
            'query_string_auth' => true // CRÍTICO PARA HOSTING COMPARTIDO: Pasa claves por URL si los Headers fallan
        ]
    );

} catch (Exception $e) {
    // Capturamos errores de inicialización de la librería (raro, pero posible)
    die('Error al iniciar cliente WooCommerce: ' . $e->getMessage());
}
?>