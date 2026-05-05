<?php
session_start();

// ============================================
// BASE DE DATOS - Parsear DATABASE_URL
// ============================================
$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    // Parsear la URL de TiDB Cloud
    // Formato: mysql://usuario:password@host:port/base_datos
    $parsed = parse_url($dbUrl);
    
    define('DB_HOST', $parsed['host']);
    define('DB_PORT', $parsed['port'] ?? '4000');
    define('DB_USER', $parsed['user']);
    define('DB_PASS', $parsed['pass']);
    define('DB_NAME', ltrim($parsed['path'], '/'));
} else {
    // Fallback para desarrollo local
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'escuela_pagos');
}

// ============================================
// PAYPAL - SANDBOX
// ============================================
define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: 'AX64EiVARf5nggG84_PrlGp1G2zigH66POX4zZJOloZk1n_A4RENh-0a9cR1zU-aDb19GFfekAaNkG6j');
define('PAYPAL_SECRET', getenv('PAYPAL_SECRET') ?: 'TU_SECRET_SANDBOX');
define('PAYPAL_API_URL', 'https://api-m.sandbox.paypal.com');
define('PAYPAL_CURRENCY', 'MXN');

// ============================================
// MERCADO PAGO - CREDENCIALES DE PRUEBA
// ============================================
define('MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN') ?: 'APP_USR-7528501431612012-050516-4eb8b8f1f418240b42750e4f0e17b42d-3379785657');
define('MP_PUBLIC_KEY', getenv('MP_PUBLIC_KEY') ?: 'APP_USR-fa8f4171-1540-469d-9f12-41dec73e4986');

// ============================================
// STRIPE - CREDENCIALES
// ============================================
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: 'sk_test_...');
define('STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_...');
define('STRIPE_CURRENCY', 'mxn');

// ============================================
// CONEXIÓN A BASE DE DATOS
// ============================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ============================================
// FUNCIONES AUXILIARES
// ============================================
function verificarSesion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: index.php');
        exit();
    }
}
?>