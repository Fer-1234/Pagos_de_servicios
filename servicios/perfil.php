<?php
require_once 'config.php';
verificarSesion();

$mensaje = '';
$error = '';

// Crear carpeta uploads si no existe
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

// Actualizar perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $email = $conn->real_escape_string($_POST['email']);
    $usuario = $conn->real_escape_string($_POST['usuario']);
    
    $id = $_SESSION['usuario_id'];
    $foto_sql = '';
    
    // Subir foto si se seleccionó
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $permitidos = ['image/jpeg', 'image/png', 'image/gif'];
        $tipo = $_FILES['foto']['type'];
        
        if (in_array($tipo, $permitidos)) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nombre_foto = 'perfil_' . $id . '_' . time() . '.' . $ext;
            $ruta_destino = 'uploads/' . $nombre_foto;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
                // Borrar foto anterior si existe
                $sql_foto_actual = "SELECT foto FROM usuarios WHERE id = $id";
                $result_foto = $conn->query($sql_foto_actual);
                if ($result_foto && $row = $result_foto->fetch_assoc()) {
                    if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])) {
                        unlink('uploads/' . $row['foto']);
                    }
                }
                $foto_sql = ", foto = '$nombre_foto'";
            } else {
                $error = '❌ Error al subir la foto';
            }
        } else {
            $error = '❌ Solo se permiten imágenes JPG, PNG o GIF';
        }
    }
    
    // Cambiar contraseña si se proporcionó
    $password_sql = '';
    if (!empty($_POST['password_nueva'])) {
        $password_nueva = md5($_POST['password_nueva']);
        $password_sql = ", password = '$password_nueva'";
    }
    
    if (empty($error)) {
        $sql = "UPDATE usuarios SET nombre = '$nombre', email = '$email', usuario = '$usuario' $foto_sql $password_sql WHERE id = $id";
        
        if ($conn->query($sql)) {
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_email'] = $email;
            $mensaje = '✅ Perfil actualizado correctamente';
        } else {
            $error = '❌ Error al actualizar el perfil: ' . $conn->error;
        }
    }
}

// Obtener datos actuales
$id = $_SESSION['usuario_id'];
$sql = "SELECT * FROM usuarios WHERE id = $id";
$result = $conn->query($sql);
$usuario = $result->fetch_assoc();

// Generar iniciales para avatar por defecto
$nombre_partes = explode(' ', $usuario['nombre']);
$iniciales = '';
if (count($nombre_partes) >= 2) {
    $iniciales = strtoupper(substr($nombre_partes[0], 0, 1) . substr($nombre_partes[count($nombre_partes)-1], 0, 1));
} else {
    $iniciales = strtoupper(substr($usuario['nombre'], 0, 2));
}

// Verificar si tiene foto
$tiene_foto = !empty($usuario['foto']) && file_exists('uploads/' . $usuario['foto']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - Tienda Digital</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .logo-header {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
            font-weight: 700;
            text-decoration: none;
            color: white;
        }
        .back-btn {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            opacity: 0.9;
            transition: opacity 0.3s;
        }
        .back-btn:hover { opacity: 1; }
        
        .container {
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .profile-card {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
            position: relative;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 5px solid rgba(255,255,255,0.5);
            font-size: 48px;
            font-weight: 800;
            color: #667eea;
            overflow: hidden;
            position: relative;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .foto-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 8px;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }
        .profile-avatar:hover .foto-overlay {
            opacity: 1;
        }
        .profile-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .profile-email {
            font-size: 14px;
            opacity: 0.9;
        }
        .profile-body {
            padding: 40px;
        }
        .section-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: #fafafa;
        }
        .form-group input:focus {
            border-color: #667eea;
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        .file-input-label {
            display: block;
            padding: 15px;
            background: #f0f0f0;
            border: 2px dashed #ccc;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            color: #666;
        }
        .file-input-label:hover {
            border-color: #667eea;
            background: #f5f5ff;
        }
        .file-input-label.has-file {
            border-color: #11998e;
            background: #d4edda;
            color: #155724;
        }
        .password-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }
        .password-note {
            font-size: 13px;
            color: #888;
            margin-bottom: 15px;
        }
        .btn-save {
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
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .mensaje {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }
        .mensaje.exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .foto-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px auto;
            display: block;
            border: 3px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="catalogo.php" class="back-btn">← Volver al Catalogo</a>
        <a href="catalogo.php" class="logo-header">
            <span>🛒</span>
            <span>Tienda Digital</span>
        </a>
        <div></div>
    </div>
    
    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar" id="avatarContainer">
                    <?php if ($tiene_foto): ?>
                        <img src="uploads/<?php echo htmlspecialchars($usuario['foto']); ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <?php echo $iniciales; ?>
                    <?php endif; ?>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                <div class="profile-email"><?php echo htmlspecialchars($usuario['email']); ?></div>
            </div>
            
            <div class="profile-body">
                <h2 class="section-title">✏️ Editar Informacion Personal</h2>
                
                <?php if ($mensaje): ?>
                    <div class="mensaje exito"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="mensaje error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- FOTO DE PERFIL -->
                    <div class="form-group">
                        <label>📷 Foto de perfil</label>
                        <?php if ($tiene_foto): ?>
                            <img src="uploads/<?php echo htmlspecialchars($usuario['foto']); ?>" class="foto-preview" id="preview">
                        <?php endif; ?>
                        <div class="file-input-wrapper">
                            <input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/gif" onchange="mostrarNombre(this)">
                            <label for="foto" class="file-input-label" id="fileLabel">
                                📁 Seleccionar foto (JPG, PNG, GIF)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre completo</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Correo electronico</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nombre de usuario</label>
                        <input type="text" name="usuario" value="<?php echo htmlspecialchars($usuario['usuario']); ?>" required>
                    </div>
                    
                    <div class="password-section">
                        <h3 class="section-title">🔒 Cambiar Contrasena</h3>
                        <p class="password-note">Deja este campo vacio si no deseas cambiar tu contrasena actual.</p>
                        <div class="form-group">
                            <label>Nueva contrasena</label>
                            <input type="password" name="password_nueva" placeholder="Nueva contrasena (opcional)">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-save">💾 Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function mostrarNombre(input) {
            const label = document.getElementById('fileLabel');
            if (input.files && input.files[0]) {
                label.textContent = '✅ ' + input.files[0].name;
                label.classList.add('has-file');
                
                // Previsualizar imagen
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = document.getElementById('avatarContainer');
                    container.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>