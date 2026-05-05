<?php
require_once 'config.php';
verificarSesion();

// Obtener productos
$sql = "SELECT * FROM productos";
$result = $conn->query($sql);

// Obtener foto del usuario para el menú
$foto_user = null;
$tiene_foto_menu = false;
if (isset($_SESSION['usuario_id'])) {
    $id_usuario = intval($_SESSION['usuario_id']);
    $sql_foto = "SELECT foto FROM usuarios WHERE id = $id_usuario";
    $res_foto = $conn->query($sql_foto);
    if ($res_foto && $res_foto->num_rows > 0) {
        $foto_user = $res_foto->fetch_assoc();
        $tiene_foto_menu = !empty($foto_user['foto']) && file_exists('uploads/' . $foto_user['foto']);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catalogo - Tienda Digital</title>
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
        }
        .user-menu { position: relative; }
        .user-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
        }
        .user-btn:hover { background: rgba(255,255,255,0.3); }
        .user-avatar {
            width: 35px;
            height: 35px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            overflow: hidden;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .dropdown {
            position: absolute;
            top: 60px;
            right: 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            min-width: 220px;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s;
            z-index: 1000;
        }
        .dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .dropdown-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }
        .dropdown-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 10px;
            overflow: hidden;
        }
        .dropdown-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .dropdown-name { font-weight: 600; font-size: 16px; }
        .dropdown-email { font-size: 12px; opacity: 0.9; margin-top: 3px; }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
        }
        .dropdown-item:hover { background: #f5f5f5; }
        .dropdown-item.icon-red { color: #e74c3c; }
        .dropdown-item.icon-red:hover { background: #fee; }
        .dropdown-divider { height: 1px; background: #eee; margin: 5px 0; }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }
        .page-title h1 {
            color: #333;
            font-size: 36px;
            margin-bottom: 10px;
        }
        .page-title p {
            color: #666;
            font-size: 16px;
        }
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        .producto-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
        }
        .producto-card:hover { 
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .producto-img {
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 70px;
            position: relative;
            overflow: hidden;
        }
        .producto-img::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            animation: shimmer 3s infinite;
        }
        @keyframes shimmer {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .producto-info { padding: 25px; }
        .producto-nombre { 
            font-size: 20px; 
            color: #333; 
            margin-bottom: 8px;
            font-weight: 700;
        }
        .producto-desc { 
            color: #888; 
            margin-bottom: 15px; 
            line-height: 1.5;
            font-size: 14px;
        }
        .producto-precio {
            font-size: 32px;
            color: #667eea;
            font-weight: 800;
            margin-bottom: 20px;
        }
        .producto-precio span {
            font-size: 16px;
            color: #999;
            font-weight: 400;
        }
        .btn-comprar {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-comprar:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff6b6b;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-header">
            <span>🛒</span>
            <span>Tienda Digital</span>
        </div>
        <div class="user-menu">
            <div class="user-btn" onclick="toggleMenu()">
                <div class="user-avatar">
                    <?php if ($tiene_foto_menu): ?>
                        <img src="uploads/<?php echo htmlspecialchars($foto_user['foto']); ?>" alt="Foto">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                <span>▼</span>
            </div>
            <div class="dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <div class="dropdown-avatar">
                        <?php if ($tiene_foto_menu): ?>
                            <img src="uploads/<?php echo htmlspecialchars($foto_user['foto']); ?>" alt="Foto">
                        <?php else: ?>
                            👤
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></div>
                    <div class="dropdown-email"><?php echo htmlspecialchars($_SESSION['usuario_email']); ?></div>
                </div>
                <a href="perfil.php" class="dropdown-item">
                    <span>👤</span>
                    <span>Mi Perfil</span>
                </a>
                <a href="compras.php" class="dropdown-item">
                    <span>📋</span>
                    <span>Mis Compras</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item icon-red">
                    <span>🚪</span>
                    <span>Cerrar Sesion</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="page-title">
            <h1>🛍️ Nuestros Productos</h1>
            <p>Selecciona el servicio o producto que deseas adquirir</p>
        </div>
        <div class="productos-grid">
            <?php while ($producto = $result->fetch_assoc()): ?>
            <div class="producto-card">
                <?php if ($producto['precio'] <= 10): ?>
                    <div class="badge">OFERTA</div>
                <?php endif; ?>
                <div class="producto-img">📦</div>
                <div class="producto-info">
                    <h3 class="producto-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                    <p class="producto-desc"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                    <div class="producto-precio">
                        $<?php echo number_format($producto['precio'], 2); ?> 
                        <span>MXN</span>
                    </div>
                    <a href="pago.php?id=<?php echo $producto['id']; ?>" class="btn-comprar">
                        💳 Pagar
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('userDropdown').classList.toggle('active');
        }
        document.addEventListener('click', function(e) {
            const menu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            if (!menu.contains(e.target)) dropdown.classList.remove('active');
        });
    </script>
</body>
</html>