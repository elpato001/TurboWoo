¡<?php
// 1. Iniciar la sesión (necesario para poder destruirla)
session_start();

// 2. Vaciar el array de sesión
$_SESSION = array();

// 3. Borrar la cookie de sesión del navegador
// Esto es una medida de seguridad adicional para invalidar el ID de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión en el servidor
session_destroy();

// 5. Redirigir al Login
header("Location: index.php");
exit;
?>