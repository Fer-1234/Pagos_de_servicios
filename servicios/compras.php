<?php
require_once 'config.php';
verificarSesion();

$usuario_id = $_SESSION['usuario_id'];

// Obtener compras del usuario
$sql = "SELECT p.*, pr.nombre as producto_nombre, pr.descripcion as producto_desc 
        FROM pagos p 
        LEFT JOIN productos pr ON p.producto_id = pr.id 
        WHERE p.usuario_id = $usuario_id 
        ORDER BY p.fecha DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Compras - Tienda Digital</title>
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
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }
        .page-title h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .page-title p {
            color: #666;
        }
        
        /* COMPRA CARD */
        .compra-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 25px;
            transition: all 0.3s;
        }
        .compra-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        .compra-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            flex-shrink: 0;
        }
        .compra-info {
            flex: 1;
        }
        .compra-producto {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        .compra-fecha {
            color: #888;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .compra-id {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #999;
            background: #f5f5f5;
            padding: 4px 10px;
            border-radius: 5px;
            display: inline-block;
        }
        .compra-monto {
            font-size: 24px;
            font-weight: 800;
            color: #667eea;
        }
        .compra-estado {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #d4edda;
            color: #155724;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        /* SIN COMPRAS */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        .empty-icon {
            font-size: 100px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        .empty-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        .empty-text {
            color: #888;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn-comprar {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-comprar:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
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
        <div class="page-title">
            <h1>📋 Mis Compras</h1>
            <p>Historial de tus compras realizadas</p>
        </div>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($compra = $result->fetch_assoc()): ?>
            <div class="compra-card">
                <div class="compra-icon">📦</div>
                <div class="compra-info">
                    <div class="compra-producto"><?php echo htmlspecialchars($compra['producto_nombre'] ?? 'Producto'); ?></div>
                    <div class="compra-fecha">
                        📅 <?php echo date('d/m/Y H:i', strtotime($compra['fecha'])); ?>
                    </div>
                    <div class="compra-id">ID: <?php echo htmlspecialchars($compra['paypal_order_id'] ?? 'N/A'); ?></div>
                    <div class="compra-estado">
                        <span>✅</span>
                        <span><?php echo ucfirst($compra['estado']); ?></span>
                    </div>
                </div>
                <div class="compra-monto">
                    $<?php echo number_format($compra['monto'], 2); ?> MXN
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- SIN COMPRAS -->
            <div class="empty-state">
                <div class="empty-icon">🛒</div>
                <h2 class="empty-title">Aun no has realizado compras</h2>
                <p class="empty-text">
                    Parece que aun no has adquirido ningun producto.<br>
                    Explora nuestro catalogo y encuentra algo que te interese.
                </p>
                <a href="catalogo.php" class="btn-comprar">🛍️ Ir al Catalogo</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>