<?php
require_once 'config.php';
verificarSesion();

// Recibir parametros de Mercado Pago
$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$external_reference = isset($_GET['external_reference']) ? $_GET['external_reference'] : '';
$metodo = isset($_GET['metodo']) ? $_GET['metodo'] : 'mercadopago';

$producto_id = $_SESSION['producto_id'] ?? 0;
$precio = $_SESSION['producto_precio'] ?? 0;
$orderID = $payment_id ?: 'N/A';

// Guardar el pago en la base de datos
if ($producto_id > 0) {
    $usuario_id = $_SESSION['usuario_id'];
    $metodo_db = 'mercadopago';
    
    $stmt = $conn->prepare("INSERT INTO pagos (usuario_id, producto_id, monto, estado, paypal_order_id, metodo_pago) VALUES (?, ?, ?, 'completado', ?, ?)");
    $stmt->bind_param("iidss", $usuario_id, $producto_id, $precio, $orderID, $metodo_db);
    $stmt->execute();
    
    unset($_SESSION['producto_id']);
    unset($_SESSION['producto_precio']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago Exitoso</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #11998e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .exito-container {
            background: white;
            padding: 60px;
            border-radius: 30px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
        }
        .checkmark {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #11998e, #38ef7d);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.6s ease;
            box-shadow: 0 10px 30px rgba(17, 153, 142, 0.4);
        }
        .checkmark::after {
            content: '✓';
            color: white;
            font-size: 60px;
            font-weight: bold;
        }
        @keyframes scaleIn {
            0% { transform: scale(0) rotate(-180deg); }
            60% { transform: scale(1.2) rotate(10deg); }
            100% { transform: scale(1) rotate(0deg); }
        }
        h1 { 
            color: #333; 
            margin-bottom: 15px;
            font-size: 32px;
        }
        .mensaje {
            color: #666;
            margin-bottom: 10px;
            line-height: 1.6;
            font-size: 16px;
        }
        .metodo-pago {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            margin: 15px 0;
            font-size: 14px;
        }
        .detalle {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 15px;
            margin: 25px 0;
            text-align: left;
        }
        .detalle-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detalle-item:last-child {
            border-bottom: none;
            font-weight: 700;
            color: #667eea;
            font-size: 20px;
        }
        .orden-id {
            background: #333;
            color: #00ff88;
            padding: 15px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            margin: 20px 0;
            word-break: break-all;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            margin-top: 25px;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:hover { 
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
    </style>
</head>
<body>
    <div class="exito-container">
        <div class="checkmark"></div>
        <h1>¡Pago Completado!</h1>
        <p class="mensaje">Tu transaccion ha sido procesada exitosamente.</p>
        
        <div class="metodo-pago">
            💳 Mercado Pago (Prueba)
        </div>
        
        <div class="detalle">
            <div class="detalle-item">
                <span>Estado</span>
                <span>✅ Completado</span>
            </div>
            <div class="detalle-item">
                <span>Monto pagado</span>
                <span>$<?php echo number_format($precio, 2); ?> MXN</span>
            </div>
            <div class="detalle-item">
                <span>Total</span>
                <span>$<?php echo number_format($precio, 2); ?> MXN</span>
            </div>
        </div>
        
        <div class="orden-id">
            ID Pago: <?php echo htmlspecialchars($orderID); ?>
        </div>
        
        <p class="mensaje" style="font-size: 14px; color: #999;">
            Este fue un pago de prueba. No se realizó ningún cargo real.
        </p>
        
        <a href="catalogo.php" class="btn">🛍️ Volver al Catalogo</a>
    </div>
</body>
</html>