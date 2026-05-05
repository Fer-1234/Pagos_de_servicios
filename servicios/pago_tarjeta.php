<?php
require_once 'config.php';
verificarSesion();

if (!isset($_GET['id'])) {
    header('Location: catalogo.php');
    exit();
}

$producto_id = intval($_GET['id']);
$sql = "SELECT * FROM productos WHERE id = $producto_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: catalogo.php');
    exit();
}

$producto = $result->fetch_assoc();
$_SESSION['producto_id'] = $producto_id;
$_SESSION['producto_precio'] = $producto['precio'];

// Crear preferencia de pago en Mercado Pago
$preference_data = [
    'items' => [[
        'id' => $producto_id,
        'title' => $producto['nombre'],
        'description' => $producto['descripcion'],
        'quantity' => 1,
        'currency_id' => 'MXN',
        'unit_price' => floatval($producto['precio'])
    ]],
    'payer' => [
        'name' => $_SESSION['usuario_nombre'],
        'email' => $_SESSION['usuario_email']
    ],
    'back_urls' => [
        'success' => 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/exito.php?metodo=mercadopago',
        'failure' => 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/cancelado.php',
        'pending' => 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/exito.php?metodo=mercadopago&pending=1'
    ],
    'auto_return' => 'approved',
    'external_reference' => $producto_id . '_' . $_SESSION['usuario_id'] . '_' . time()
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/checkout/preferences');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . MP_ACCESS_TOKEN,
    'Content-Type: application/json'
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$mpResponse = json_decode($result, true);
$checkout_url = $mpResponse['init_point'] ?? '';
$sandbox_url = $mpResponse['sandbox_init_point'] ?? '';

// Obtener foto del usuario
$sql_foto = "SELECT foto FROM usuarios WHERE id = " . $_SESSION['usuario_id'];
$res_foto = $conn->query($sql_foto);
$foto_user = $res_foto->fetch_assoc();
$tiene_foto_menu = !empty($foto_user['foto']) && file_exists('uploads/' . $foto_user['foto']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago con Tarjeta - <?php echo htmlspecialchars($producto['nombre']); ?></title>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            width: 35px; height: 35px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            overflow: hidden;
        }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .dropdown {
            position: absolute;
            top: 60px; right: 0;
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
            width: 60px; height: 60px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 10px;
            overflow: hidden;
        }
        .dropdown-avatar img { width: 100%; height: 100%; object-fit: cover; }
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
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .pago-card {
            background: white;
            border-radius: 25px;
            padding: 50px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        .resumen {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 35px;
            border: 2px solid #e9ecef;
        }
        .resumen h3 { 
            color: #333; 
            margin-bottom: 20px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .resumen-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
            font-size: 16px;
        }
        .resumen-item:last-child { border-bottom: none; }
        .resumen-item span:first-child { color: #666; }
        .resumen-item span:last-child { color: #333; font-weight: 600; }
        .total {
            font-size: 36px;
            color: #667eea;
            font-weight: 800;
            margin-top: 20px;
            text-align: right;
            padding-top: 20px;
            border-top: 3px solid #667eea;
        }
        .total span { font-size: 18px; color: #999; font-weight: 400; }
        
        .metodos-pago {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        .metodo-btn {
            flex: 1;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        .metodo-btn:hover { border-color: #667eea; transform: translateY(-2px); }
        .metodo-btn.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .metodo-btn .icono { font-size: 40px; margin-bottom: 10px; }
        .metodo-btn .titulo { font-weight: 600; font-size: 16px; }
        
        .mp-info {
            background: linear-gradient(135deg, #00b1ea 0%, #009ee3 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: center;
        }
        .mp-info h3 {
            font-size: 22px;
            margin-bottom: 15px;
        }
        .mp-info p {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        .btn-mp {
            display: inline-block;
            padding: 18px 50px;
            background: white;
            color: #009ee3;
            text-decoration: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .btn-mp:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        .medios-pago {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .medio-item {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 13px;
        }
        .seguridad {
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 13px;
        }
        .volver {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .volver:hover { color: #764ba2; transform: translateX(-5px); }
    </style>
</head>
<body>
    <div class="header">
        <a href="catalogo.php" class="logo-header">
            <span>🛒</span>
            <span>Tienda Digital</span>
        </a>
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
                <a href="perfil.php" class="dropdown-item"><span>👤</span><span>Mi Perfil</span></a>
                <a href="compras.php" class="dropdown-item"><span>📋</span><span>Mis Compras</span></a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item icon-red"><span>🚪</span><span>Cerrar Sesion</span></a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="pago-card">
            <div class="resumen">
                <h3>📋 Resumen de Compra</h3>
                <div class="resumen-item">
                    <span>Producto</span>
                    <span><?php echo htmlspecialchars($producto['nombre']); ?></span>
                </div>
                <div class="resumen-item">
                    <span>Descripcion</span>
                    <span><?php echo htmlspecialchars($producto['descripcion']); ?></span>
                </div>
                <div class="resumen-item">
                    <span>Precio unitario</span>
                    <span>$<?php echo number_format($producto['precio'], 2); ?> MXN</span>
                </div>
                <div class="total">
                    Total: $<?php echo number_format($producto['precio'], 2); ?> <span>MXN</span>
                </div>
            </div>
            
            <h3 style="margin-bottom: 20px; color: #333;">💳 Selecciona metodo de pago</h3>
            
            <div class="metodos-pago">
                <a href="pago.php?id=<?php echo $producto_id; ?>" class="metodo-btn">
                    <div class="icono">💰</div>
                    <div class="titulo">PayPal</div>
                </a>
                <div class="metodo-btn active">
                    <div class="icono">💳</div>
                    <div class="titulo">Tarjeta / Mercado Pago</div>
                </div>
            </div>
            
            <div class="mp-info">
                <h3>🛒 Pagar con Mercado Pago</h3>
                <p>Paga de forma segura con tarjeta de credito, debito, Oxxo, SPEI y mas.</p>
                <p style="font-size: 18px; font-weight: 700; margin-bottom: 20px;">
                    Total: $<?php echo number_format($producto['precio'], 2); ?> MXN
                </p>
                
                <?php if (!empty($checkout_url)): ?>
                    <a href="<?php echo $checkout_url; ?>" class="btn-mp">
                        💳 Ir a Pagar
                    </a>
                <?php else: ?>
                    <p style="color: #ffcccc;">Error al crear el pago. Intenta de nuevo.</p>
                <?php endif; ?>
                
                <div class="medios-pago">
                    <div class="medio-item">💳 Tarjetas</div>
                    <div class="medio-item">🏪 Oxxo</div>
                    <div class="medio-item">🏦 SPEI</div>
                    <div class="medio-item">📱 Mercado Pago</div>
                </div>
            </div>
            
            <div class="seguridad">
                🔒 Pago 100% seguro • Procesado por Mercado Pago • Sin datos sensibles en este sitio
            </div>
            
            <a href="catalogo.php" class="volver">← Volver al catalogo</a>
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