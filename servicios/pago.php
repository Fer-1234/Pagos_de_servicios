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

// ============================================
// MERCADO PAGO - SIN back_urls NI auto_return
// ============================================

$preference_data = [
    'items' => [[
        'id' => (string)$producto_id,
        'title' => substr($producto['nombre'], 0, 256),
        'description' => substr($producto['descripcion'], 0, 256),
        'quantity' => 1,
        'currency_id' => 'MXN',
        'unit_price' => floatval($producto['precio'])
    ]],
    'payer' => [
        'name' => $_SESSION['usuario_nombre'],
        'email' => $_SESSION['usuario_email']
    ],
    'external_reference' => 'PROD_' . $producto_id . '_USER_' . $_SESSION['usuario_id'] . '_' . time()
    // NO incluimos back_urls ni auto_return
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
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$mpResponse = json_decode($result, true);

if ($httpCode == 200 || $httpCode == 201) {
    $checkout_url = $mpResponse['init_point'] ?? '';
    
    if (!empty($checkout_url)) {
        // Redirigir directamente a Mercado Pago
        header('Location: ' . $checkout_url);
        exit();
    }
}

// Si llegamos aquí, hubo error
$error_detail = $mpResponse['message'] ?? $mpResponse['error'] ?? 'Error desconocido';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error - Mercado Pago</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .error-box {
            background: white;
            padding: 50px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
        }
        .error-box h1 { color: #e74c3c; margin-bottom: 20px; }
        .error-box p { color: #666; margin-bottom: 20px; }
        .error-box code {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 10px;
            display: block;
            text-align: left;
            font-size: 12px;
            color: #c62828;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>❌ Error al crear el pago</h1>
        <p><?php echo htmlspecialchars($error_detail); ?></p>
        <code>HTTP <?php echo $httpCode; ?><br><?php echo htmlspecialchars(json_encode($mpResponse)); ?></code>
        <br>
        <a href="catalogo.php" class="btn">Volver al catálogo</a>
    </div>
</body>
</html>