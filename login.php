<?php
/**
 * PedroBox — Endpoint: Inicio de Sesión
 * 
 * Método:  POST
 * URL:     http://localhost/pedrobox/backend/login.php
 * Body:    { "email": "juan@email.com", "pass": "123456" }
 * 
 * Retorna: { "success": true,  "token": "...", "user": { ... } }
 *    o:    { "success": false, "message": "error descriptivo" }
 */

require_once __DIR__ . '/config.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// ─── Leer datos ─────────────────────────────────────────────
$data  = getJSON();
$email = trim($data['email'] ?? '');
$pass  = trim($data['pass']  ?? '');

// ─── Validaciones básicas ───────────────────────────────────
if (empty($email) || empty($pass)) {
    jsonResponse(['success' => false, 'message' => 'Correo y contraseña son obligatorios']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Formato de correo no válido']);
}

// ─── Buscar usuario por email ───────────────────────────────
$db   = getDB();
$stmt = $db->prepare("SELECT id, nombre, email, telefono, contrasena, campo FROM usuarios WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    // Respondemos genérico para no revelar si el email existe
    jsonResponse(['success' => false, 'message' => 'Correo o contraseña incorrectos']);
}

// ─── Verificar contraseña (comparación hash segura) ────────
if (!password_verify($pass, $user['contrasena'])) {
    jsonResponse(['success' => false, 'message' => 'Correo o contraseña incorrectos']);
}

// ─── Generar token de sesión simple ─────────────────────────
// En producción usa JWT. Para el prototipo generamos un token básico
// y lo guardamos en la tabla sesiones.
$token = bin2hex(random_bytes(32)); // 64 caracteres aleatorios

$tokenStmt = $db->prepare("
    INSERT INTO sesiones (usuario_id, token, fecha_inicio, fecha_fin)
    VALUES (:uid, :token, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))
");
$tokenStmt->execute([':uid' => $user['id'], ':token' => $token]);

// ─── Registrar actividad ────────────────────────────────────
$log = $db->prepare("
    INSERT INTO historial (usuario_id, tipo, detalle, fecha)
    VALUES (:uid, 'login', 'Sesión iniciada exitosamente', NOW())
");
$log->execute([':uid' => $user['id']]);

// ─── Retornar respuesta ─────────────────────────────────────
jsonResponse([
    'success' => true,
    'message' => '¡Sesión iniciada!',
    'token'   => $token,
    'user'    => [
        'id'    => $user['id'],
        'name'  => $user['nombre'],
        'email' => $user['email'],
        'phone' => $user['telefono'],
        'campo' => $user['campo'],
    ]
]);
?>
