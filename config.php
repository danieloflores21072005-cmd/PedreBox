<?php
/**
 * PedroBox — Configuración Central del Backend
 * 
 * INSTRUCCIONES:
 * 1. Copia esta carpeta "pedrobox" dentro de C:\xampp\htdocs\
 * 2. Enciende XAMPP (Apache + MySQL)
 * 3. Crea la base de datos en phpMyAdmin ejecutando el archivo database/pedrobox.sql
 * 4. Ajusta las credenciales de abajo si las cambiaste
 */

// ─── CREDENCIALES DE BASE DE DATOS ─────────────────────────
define('DB_HOST',     'localhost');
define('DB_NAME',     'pedrobox');
define('DB_USER',     'root');           // usuario por defecto de XAMPP
define('DB_PASS',     '');               // en XAMPP por defecto está vacío
define('DB_CHARSET',  'utf8mb4');

// ─── CONEXIÓN PDO ──────────────────────────────────────────
function getDB() {
    static $pdo = null;
    if ($pdo) return $pdo;

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
        exit;
    }
}

// ─── HEADERS CORS (permite que la APK mobile hable con este servidor) ──
function setCorsHeaders() {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');                      // permite cualquier origen (APK, localhost, etc.)
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 3600');

    // Si la petición es OPTIONS (preflight de CORS), respondemos y terminamos
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// ─── FUNCIÓN PARA LEER JSON del cuerpo de la petición ──────
function getJSON(): array {
    $body = file_get_contents('php://input');
    return json_decode($body, true) ?? [];
}

// ─── RESPUESTA JSON estandarizada ───────────────────────────
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
?>
