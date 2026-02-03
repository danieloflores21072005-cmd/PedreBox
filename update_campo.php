<?php
/**
 * PedroBox — Endpoint: Actualizar Campo del Usuario
 * 
 * Método:  POST
 * URL:     http://localhost/pedrobox/backend/update_campo.php
 * Headers: Authorization: Bearer <token>
 * Body:    { "campo": "negocio" }
 * 
 * Retorna: { "success": true, "message": "Campo actualizado" }
 */

require_once __DIR__ . '/config.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// ─── Verificar token ───────────────────────────────────────
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$db = getDB();

$sessionCheck = $db->prepare("
    SELECT usuario_id FROM sesiones 
    WHERE token = :token AND fecha_fin > NOW()
");
$sessionCheck->execute([':token' => $token]);
$session = $sessionCheck->fetch();

if (!$session) {
    jsonResponse(['success' => false, 'message' => 'Sesión no válida. Por favor inicia sesión de nuevo.'], 401);
}

$userId = $session['usuario_id'];

// ─── Leer nuevo campo ───────────────────────────────────────
$data  = getJSON();
$campo = trim($data['campo'] ?? '');

$validCampos = ['estudiantil', 'negocio', 'empleo', 'salud', 'transporte', 'entretenimiento'];
if (!in_array($campo, $validCampos)) {
    jsonResponse(['success' => false, 'message' => 'Campo no válido']);
}

// ─── Actualizar en la base de datos ─────────────────────────
$update = $db->prepare("UPDATE usuarios SET campo = :campo WHERE id = :uid");
$update->execute([':campo' => $campo, ':uid' => $userId]);

// ─── Registrar en historial ─────────────────────────────────
$log = $db->prepare("
    INSERT INTO historial (usuario_id, tipo, detalle, fecha)
    VALUES (:uid, 'campo_cambio', 'Campo cambiado a: " . $campo . "', NOW())
");
$log->execute([':uid' => $userId]);

jsonResponse([
    'success' => true,
    'message' => 'Campo actualizado a: ' . ucfirst($campo)
]);
?>
