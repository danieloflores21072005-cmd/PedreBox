<?php
/**
 * PedroBox — Endpoint: Registro de Usuario
 * 
 * Método:  POST
 * URL:     http://localhost/pedrobox/backend/register.php
 * Body:    { "name": "Juan Pérez", "email": "juan@email.com", "phone": "+51900000000", "pass": "123456" }
 * 
 * Retorna: { "success": true,  "message": "...", "user": { ... } }
 *    o:    { "success": false, "message": "error descriptivo" }
 */

require_once __DIR__ . '/config.php';
setCorsHeaders();

// Solo aceptamos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// ─── Leer datos del cuerpo ──────────────────────────────────
$data  = getJSON();
$name  = trim($data['name']  ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$pass  = trim($data['pass']  ?? '');

// ─── Validaciones del servidor (segunda línea de defensa) ──
if (empty($name)) {
    jsonResponse(['success' => false, 'message' => 'El nombre es obligatorio']);
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Correo electrónico no válido']);
}
if (empty($phone)) {
    jsonResponse(['success' => false, 'message' => 'El número de WhatsApp es obligatorio']);
}
if (empty($pass) || strlen($pass) < 6) {
    jsonResponse(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
}

// ─── Verificar si el email ya existe ────────────────────────
$db = getDB();
$exists = $db->prepare("SELECT id FROM usuarios WHERE email = :email");
$exists->execute([':email' => $email]);
if ($exists->fetch()) {
    jsonResponse(['success' => false, 'message' => 'Este correo ya está registrado']);
}

// ─── Hashear la contraseña (NUNCA guardar en texto plano) ───
$passHash = password_hash($pass, PASSWORD_BCRYPT);

// ─── Insertar usuario en la base de datos ──────────────────
try {
    $stmt = $db->prepare("
        INSERT INTO usuarios (nombre, email, telefono, contrasena, campo, fecha_registro)
        VALUES (:name, :email, :phone, :pass, 'estudiantil', NOW())
    ");
    $stmt->execute([
        ':name'  => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':pass'  => $passHash,
    ]);

    $userId = $db->lastInsertId();

    // ─── Guardar en historial de actividad ──────────────────
    $log = $db->prepare("
        INSERT INTO historial (usuario_id, tipo, detalle, fecha)
        VALUES (:uid, 'registro', 'Cuenta creada exitosamente', NOW())
    ");
    $log->execute([':uid' => $userId]);

    // ─── Retornar respuesta exitosa ─────────────────────────
    jsonResponse([
        'success' => true,
        'message' => '¡Cuenta creada exitosamente!',
        'user'    => [
            'id'     => $userId,
            'name'   => $name,
            'email'  => $email,
            'phone'  => $phone,
            'campo'  => 'estudiantil',
        ]
    ]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Error al crear la cuenta. Intenta de nuevo.'], 500);
}
?>
