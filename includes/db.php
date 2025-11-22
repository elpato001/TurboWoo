<?php
/**
 * CONECTOR BASE DE DATOS LOCAL (MySQL)
 * ------------------------------------
 * Este archivo utiliza las constantes definidas en config.php
 * para iniciar la conexión con la base de datos local.
 */

// 1. Validar que la configuración existe
if(!defined('DB_HOST')) {
    die('<b>Error Crítico:</b> No se han cargado las constantes de configuración. Asegúrate de que el sistema esté instalado y config.php exista.');
}

// 2. Intentar conectar (Usamos @ para suprimir errores nativos de PHP y manejarlos nosotros)
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 3. Verificar si hubo error
if ($conn->connect_error) {
    // Mostramos un mensaje de error estilizado para que no te asustes si olvidaste prender XAMPP
    die("
    <div style='font-family: sans-serif; padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 8px; margin: 20px; max-width: 600px;'>
        <h3 style='margin-top: 0;'>⛔ Error de Conexión a Base de Datos Local</h3>
        <p>No se pudo establecer conexión con MySQL.</p>
        <div style='background: white; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;'>
            " . $conn->connect_error . "
        </div>
        <br>
        <strong>Posibles soluciones:</strong>
        <ul>
            <li>Verifica que <strong>XAMPP / MySQL</strong> esté encendido (botón Start).</li>
            <li>Revisa si las credenciales en <code>includes/config.php</code> son correctas.</li>
        </ul>
    </div>
    ");
}

// 4. Configurar codificación de caracteres (Vital para tildes y ñ)
// Usamos utf8mb4 para soportar emojis también
if (!$conn->set_charset("utf8mb4")) {
    printf("Error cargando el conjunto de caracteres utf8mb4: %s\n", $conn->error);
    exit();
}

// La variable $conn queda lista para usarse en el resto del sistema
?>