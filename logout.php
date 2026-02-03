<?php
/**
 * PedroBox — Endpoint: Cerrar Sesión
 * 
 * Método:  POST
 * URL:     http://localhost/pedrobox/backend/logout.php
 * Headers: Authorization: Bearer <token>
 * 
 * Retorna: { "success": true, "message": "Sesión cerrada" }
 */

require_once __DIR__ . '/config.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// ─── Obtener token ──────────────────────────────────────────
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (empty($token)) {
    jsonResponse(['success' => true, 'message' => 'Sesión cerrada']);
}

// ─── Eliminar sesión de la base de datos ────────────────────
$db = getDB();

// Obtener usuario_id antes de eliminar (para historial)
$sessionCheck = $db->prepare("SELECT usuario_id FROM sesiones WHERE token = :token");
$sessionCheck->execute([':token' => $token]);
$session = $sessionCheck->fetch();

// Eliminar el token
$delete = $db->prepare("DELETE FROM sesiones WHERE token = :token");
$delete->execute([':token' => $token]);

// ─── Registrar en historial si encontramos el usuario ──────
if ($session) {
    $log = $db->prepare("
        INSERT INTO historial (usuario_id, tipo, detalle, fecha)
        VALUES (:uid, 'logout', 'Sesión cerrada por el usuario', NOW())
    ");
    $log->execute([':uid' => $session['usuario_id']]);
}

jsonResponse([
    'success' => true,
    'message' => 'Sesión cerrada exitosamente'
]);
?>
