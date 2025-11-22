<?php
/**
 * Helper para subir imágenes a la Librería de Medios de WordPress
 */
function subir_imagen_a_wordpress($file_array) {
    
    // Configuración de API (Usamos las mismas credenciales de Woo)
    $api_url = WOO_URL . '/wp-json/wp/v2/media';
    $consumer_key = WOO_CK;
    $consumer_secret = WOO_CS;

    // Preparar el archivo
    $file_name = basename($file_array['name']);
    $file_path = $file_array['tmp_name'];
    $file_type = $file_array['type'];
    
    // Leer contenido binario
    $file_data = file_get_contents($file_path);

    // Configurar cURL para subir binario
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Headers de Autenticación y Tipo de Archivo
    $headers = [
        'Content-Disposition: attachment; filename="' . $file_name . '"',
        'Content-Type: ' . $file_type,
        'Authorization: Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 201) { // 201 = Created
        $data = json_decode($response, true);
        return $data['id']; // Retornamos el ID de la imagen en WP
    } else {
        return false; // Falló la subida
    }
}
?>