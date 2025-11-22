<?php
session_start();

// 1. VERIFICACIÓN DE INSTALACIÓN
// Si no existe el archivo de configuración maestro, asumimos que no está instalado
if (!file_exists('includes/config.php')) {
    header('Location: install/index.php');
    exit;
}

// 2. CARGAR SISTEMA
require_once 'includes/config.php';
require_once 'includes/db.php'; 

// 3. SI YA HAY SESIÓN, IR AL DASHBOARD
if(isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// 4. PROCESAR LOGIN
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_input = trim($_POST['usuario']);
    $pass_input = trim($_POST['password']);
    
    if(empty($user_input) || empty($pass_input)) {
        $error = "Por favor completa todos los campos.";
    } else {
        // Consulta segura preparada
        $stmt = $conn->prepare("SELECT id, nombre, usuario, password, rol FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $user_input);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if($fila = $resultado->fetch_assoc()) {
            // Verificamos el hash de la contraseña
            if(password_verify($pass_input, $fila['password'])) {
                // ¡ÉXITO! Guardamos datos en sesión
                $_SESSION['usuario_id'] = $fila['id'];
                $_SESSION['nombre']     = $fila['nombre'];
                $_SESSION['usuario']    = $fila['usuario'];
                $_SESSION['rol']        = $fila['rol']; // 'admin', 'vendedor', 'editor'
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "El usuario no existe.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo defined('APP_NAME') ? APP_NAME : 'TurboWoo'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
        }
        .brand-title {
            font-weight: 800;
            color: #1e3c72;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .brand-subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        .btn-turbo {
            background-color: #1e3c72;
            color: white;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-turbo:hover { 
            background-color: #162d55; 
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3);
        }
        .form-control:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 0.25rem rgba(30, 60, 114, 0.25);
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-3">
            <i class="fas fa-bolt fa-3x text-primary"></i>
        </div>
        <h2 class="brand-title"><?php echo defined('APP_NAME') ? APP_NAME : 'TurboWoo'; ?></h2>
        <p class="brand-subtitle">Panel de Gestión Sincronizada</p>
        
        <?php if($error): ?>
            <div class="alert alert-danger text-center p-2 fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-bold text-muted small">USUARIO</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="usuario" class="form-control" required autofocus placeholder="Ej: admin">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label fw-bold text-muted small">CONTRASEÑA</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
            </div>
            
            <button type="submit" class="btn btn-turbo w-100 btn-lg py-2">
                INGRESAR <i class="fas fa-sign-in-alt ms-2"></i>
            </button>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">v1.0 &copy; <?php echo date('Y'); ?></small>
        </div>
    </div>
</body>
</html>