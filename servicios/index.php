<?php
require_once 'config.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: catalogo.php');
    exit();
}

$error = '';
$registro_exitoso = '';

// Registro de nuevo usuario
if (isset($_GET['registro']) && $_GET['registro'] == '1') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = $conn->real_escape_string($_POST['nombre']);
        $email = $conn->real_escape_string($_POST['email']);
        $usuario = $conn->real_escape_string($_POST['usuario']);
        $password = md5($_POST['password']);
        
        $check = $conn->query("SELECT id FROM usuarios WHERE usuario = '$usuario'");
        if ($check && $check->num_rows > 0) {
            $error = 'El usuario ya existe. Intenta con otro.';
        } else {
            $sql = "INSERT INTO usuarios (nombre, email, usuario, password) 
                    VALUES ('$nombre', '$email', '$usuario', '$password')";
            if ($conn->query($sql)) {
                $registro_exitoso = 'Registro exitoso. Ahora puedes iniciar sesion.';
            } else {
                $error = 'Error al registrar. Intenta de nuevo.';
            }
        }
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $usuario = $conn->real_escape_string($_POST['usuario']);
        $password = md5($_POST['password']);
        
        $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND password = '$password'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_email'] = $user['email'];
            header('Location: catalogo.php');
            exit();
        } else {
            $error = 'Usuario o contrasena incorrectos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tienda Digital</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 420px;
            backdrop-filter: blur(10px);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-icon {
            font-size: 60px;
            margin-bottom: 10px;
        }
        h2 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group { margin-bottom: 25px; }
        label { 
            display: block; 
            margin-bottom: 8px; 
            color: #555; 
            font-weight: 600;
            font-size: 14px;
        }
        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: #fafafa;
        }
        input:focus {
            border-color: #667eea;
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .error {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        .exito {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        .tabs {
            display: flex;
            margin-bottom: 25px;
            background: #f0f0f0;
            border-radius: 12px;
            padding: 5px;
        }
        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            color: #666;
            font-weight: 500;
            transition: all 0.3s;
        }
        .tab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🛒</div>
            <h2>Tienda Digital</h2>
            <div class="subtitle">
                <?php echo isset($_GET['registro']) ? 'Crea tu cuenta gratis' : 'Inicia sesion para continuar'; ?>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($registro_exitoso): ?>
            <div class="exito"><?php echo $registro_exitoso; ?></div>
        <?php endif; ?>

        <div class="tabs">
            <a href="index.php" class="tab <?php echo !isset($_GET['registro']) ? 'active' : ''; ?>">Iniciar Sesion</a>
            <a href="?registro=1" class="tab <?php echo isset($_GET['registro']) ? 'active' : ''; ?>">Registrarse</a>
        </div>

        <?php if (isset($_GET['registro']) && $_GET['registro'] == '1'): ?>
            <!-- FORMULARIO DE REGISTRO - SIN AUTOCOMPLETADO -->
            <form method="POST" action="?registro=1" autocomplete="off">
                <div class="form-group">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" placeholder="Tu nombre completo" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Correo electronico</label>
                    <input type="email" name="email" placeholder="tu@email.com" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="usuario" placeholder="Elige un usuario" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Contrasena</label>
                    <input type="password" name="password" placeholder="Crea una contrasena" required autocomplete="new-password">
                </div>
                <button type="submit">Crear Cuenta</button>
            </form>
        <?php else: ?>
            <!-- FORMULARIO DE LOGIN -->
            <form method="POST" action="" autocomplete="off">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="usuario" placeholder="Ingresa tu usuario" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Contrasena</label>
                    <input type="password" name="password" placeholder="Ingresa tu contrasena" required autocomplete="off">
                </div>
                <button type="submit">Ingresar</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>