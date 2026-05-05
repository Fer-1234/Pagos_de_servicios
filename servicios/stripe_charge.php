<?php
require_once 'config.php';
verificarSesion();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Metodo no permitido';
    echo json_encode($response);
    exit();
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['stripeToken'] ?? '';
$producto_id = intval($input['producto_id'] ?? 0);
$amount = intval($input['amount'] ?? 0);

if (empty($token) || $producto_id === 0 || $amount === 0) {
    $response['message'] = 'Datos incompletos';
    echo json_encode($response);
    exit();
}

// Obtener info del producto
$sql = "SELECT * FROM productos WHERE id = $producto_id";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    $response['message'] = 'Producto no encontrado';
    echo json_encode($response);
    exit();
}
$producto = $result->fetch_assoc();

// Crear cargo en Stripe con TU secret key
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/charges');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'amount' => $amount,
    'currency' => STRIPE_CURRENCY,
    'source' => $token,
    'description' => 'Pago Tienda Digital: ' . $producto['nombre'],
    'receipt_email' => $_SESSION['usuario_email'] ?? ''
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . STRIPE_SECRET_KEY,
    'Content-Type: application/x-www-form-urlencoded'
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$stripeResponse = json_decode($result, true);

if ($httpCode === 200 && isset($stripeResponse['id'])) {
    // Guardar en base de datos
    $usuario_id = $_SESSION['usuario_id'];
    $monto = $amount / 100;
    $stripe_charge_id = $stripeResponse['id'];
    
    $stmt = $conn->prepare("INSERT INTO pagos (usuario_id, producto_id, monto, estado, paypal_order_id, metodo_pago) VALUES (?, ?, ?, 'completado', ?, 'tarjeta')");
    $stmt->bind_param("iids", $usuario_id, $producto_id, $monto, $stripe_charge_id);
    $stmt->execute();
    
    $response['success'] = true;
    $response['message'] = 'Pago exitoso';
    $response['charge_id'] = $stripe_charge_id;
} else {
    $response['message'] = $stripeResponse['error']['message'] ?? 'Error al procesar el pago con tarjeta';
}

echo json_encode($response);
?>