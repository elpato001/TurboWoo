<?php
// SETUP FINAL (Solo se ejecuta si el AJAX dio luz verde)
error_reporting(E_ALL);

// Recolectar
$db_host = $_POST['db_host'];
$db_name = $_POST['db_name'];
$db_user = $_POST['db_user'];
$db_pass = $_POST['db_pass'];
$woo_url = rtrim($_POST['woo_url'], '/');
$woo_ck  = $_POST['woo_ck'];
$woo_cs  = $_POST['woo_cs'];
$wp_user = $_POST['wp_user'];
$wp_pass = $_POST['wp_pass'];
$app_name = $_POST['app_name'];
$admin_user = $_POST['admin_user'];
$admin_pass = $_POST['admin_pass'];

try {
    // 1. Crear BD
    $conn = new mysqli($db_host, $db_user, $db_pass);
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($db_name);

    // 2. Tablas
    $conn->query("CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100),
        usuario VARCHAR(50) UNIQUE,
        password VARCHAR(255),
        rol ENUM('admin', 'vendedor', 'editor') DEFAULT 'vendedor',
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS productos_local (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_woo INT NOT NULL DEFAULT 0,
        nombre VARCHAR(255),
        precio_regular INT DEFAULT 0,
        precio_rebajado INT DEFAULT 0,
        stock INT DEFAULT 0,
        manage_stock TINYINT DEFAULT 1,
        sku VARCHAR(100),
        descripcion TEXT,
        descripcion_corta TEXT,
        categoria_id INT DEFAULT 0,
        imagen_url TEXT,
        ruta_imagen_local VARCHAR(255),
        galeria_local TEXT,
        es_nuevo TINYINT DEFAULT 0,
        cambio_pendiente TINYINT DEFAULT 0,
        eliminar_pendiente TINYINT DEFAULT 0,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // 3. Admin
    $pass_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
    $conn->query("TRUNCATE TABLE usuarios");
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (?, ?, ?, 'admin')");
    $stmt->bind_param("sss", $app_name, $admin_user, $pass_hash);
    $stmt->execute();

    // 4. Config.php
    $config_content = "<?php
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('WOO_URL', '$woo_url');
define('WOO_CK', '$woo_ck');
define('WOO_CS', '$woo_cs');
define('WP_ADMIN_USER', '$wp_user');
define('WP_APP_PASS', '$wp_pass');
define('APP_NAME', '$app_name');
date_default_timezone_set('America/Santiago'); 
?>";
    file_put_contents('../includes/config.php', $config_content);

    // 5. Carpetas
    if (!file_exists('../assets/fotos_temp')) mkdir('../assets/fotos_temp', 0777, true);

    // ÉXITO
    echo "
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <div class='d-flex align-items-center justify-content-center vh-100 bg-light'>
        <div class='text-center'>
            <h1 class='text-success'>¡Instalación Exitosa! 🚀</h1>
            <p>TurboWoo está listo.</p>
            <a href='../index.php' class='btn btn-primary btn-lg'>Ir al Login</a>
        </div>
    </div>";

} catch (Exception $e) {
    die("Error Fatal: " . $e->getMessage());
}
?>