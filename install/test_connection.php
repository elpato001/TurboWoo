<?php
header('Content-Type: application/json');
error_reporting(0); 

function test_request($url, $user, $pass, $type) {
    // 1. Limpieza de contraseña (quitar espacios que a veces copiamos sin querer)
    $pass_clean = str_replace(' ', '', $pass);
    $user_clean = trim($user);

    $ch = curl_init();
    
    // Determinar Endpoint
    if ($type === 'woo') {
        $endpoint = $url . '/wp-json/wc/v3/system_status';
    } else {
        $endpoint = $url . '/wp-json/wp/v2/users/me';
    }

    // CONFIGURACIÓN ROBUSTA DE CURL
    $options = [
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false, // Ignorar SSL (Vital para local)
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', // Disfrazarse de Navegador
        CURLOPT_FOLLOWLOCATION => true, // Seguir redirecciones
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $user_clean . ":" . $pass_clean
    ];
    
    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    // --- INTENTO 2: FALLBACK POR URL (Si falla el método normal) ---
    // Algunos hostings borran el header Authorization. Enviamos claves por URL.
    if ($http_code === 401 || $http_code === 403) {
        if ($type === 'woo') {
            // Reintentar poniendo claves en la URL
            $url_fallback = $endpoint . '?consumer_key=' . $user_clean . '&consumer_secret=' . $pass_clean;
            curl_setopt($ch, CURLOPT_URL, $url_fallback);
            // Quitamos el header auth para no confundir
            curl_setopt($ch, CURLOPT_USERPWD, null); 
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
    }

    curl_close($ch);

    // ANÁLISIS DE RESPUESTA
    if ($http_code === 200 || $http_code === 201) {
        return ['status' => 'ok', 'msg' => 'Conexión Exitosa'];
    } 
    elseif ($http_code === 401) {
        return ['status' => 'error', 'msg' => "Error 401: El servidor rechazó las claves. (Revisa si tienes plugins de seguridad como Wordfence bloqueando la API)."];
    }
    elseif ($http_code === 404) {
        return ['status' => 'error', 'msg' => "Error 404: No se encuentra la API. Revisa que la URL sea exactamente la raíz del sitio."];
    }
    elseif ($http_code === 0) {
        return ['status' => 'error', 'msg' => "Error de Red: $curl_error. (XAMPP no puede salir a internet)."];
    }
    else {
        // Devolver un trozo de la respuesta para depurar
        $preview = substr(strip_tags($response), 0, 100);
        return ['status' => 'error', 'msg' => "Error $http_code. Respuesta servidor: $preview"];
    }
}

$response = [];

// 1. SQL
$conn = @new mysqli($_POST['db_host'], $_POST['db_user'], $_POST['db_pass']);
if ($conn->connect_error) {
    $response['sql'] = ['status' => 'error', 'msg' => $conn->connect_error];
} else {
    $response['sql'] = ['status' => 'ok'];
    $conn->close();
}

// 2. WOO
$response['woo'] = test_request(rtrim($_POST['woo_url'], '/'), $_POST['woo_ck'], $_POST['woo_cs'], 'woo');

// 3. WP
$response['wp'] = test_request(rtrim($_POST['woo_url'], '/'), $_POST['wp_user'], $_POST['wp_pass'], 'wp');

echo json_encode($response);
?>