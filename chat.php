<?php
/**
 * PedroBox — Endpoint: Motor de Chat
 * 
 * Método:  POST
 * URL:     http://localhost/pedrobox/backend/chat.php
 * Headers: Authorization: Bearer <token>
 * Body:    { "msg": "texto del usuario", "campo": "estudiantil" }
 * 
 * Retorna: { "success": true, "response": "<html respuesta del bot>" }
 */

require_once __DIR__ . '/config.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// ─── Verificar token de sesión ──────────────────────────────
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

$db = getDB();

if (!empty($token)) {
    $sessionCheck = $db->prepare("
        SELECT usuario_id FROM sesiones 
        WHERE token = :token AND fecha_fin > NOW()
    ");
    $sessionCheck->execute([':token' => $token]);
    $session = $sessionCheck->fetch();
    $userId = $session['usuario_id'] ?? null;
} else {
    $userId = null; // modo sin autenticación (para pruebas)
}

// ─── Leer datos ─────────────────────────────────────────────
$data  = getJSON();
$msg   = trim($data['msg']   ?? '');
$campo = trim($data['campo'] ?? 'estudiantil');

if (empty($msg)) {
    jsonResponse(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
}

// ─── Campos válidos ─────────────────────────────────────────
$validCampos = ['estudiantil', 'negocio', 'empleo', 'salud', 'transporte', 'entretenimiento'];
if (!in_array($campo, $validCampos)) {
    $campo = 'estudiantil'; // fallback
}

// ─── Motor de respuestas (mismo lógica que el JS, pero en servidor) ──
$response = getMotorResponse($msg, $campo);

// ─── Guardar conversación en historial ──────────────────────
if ($userId) {
    // Guardar mensaje del usuario
    $saveUser = $db->prepare("
        INSERT INTO conversaciones (usuario_id, tipo, mensaje, campo, fecha)
        VALUES (:uid, 'usuario', :msg, :campo, NOW())
    ");
    $saveUser->execute([':uid' => $userId, ':msg' => $msg, ':campo' => $campo]);

    // Guardar respuesta del bot
    $saveBot = $db->prepare("
        INSERT INTO conversaciones (usuario_id, tipo, mensaje, campo, fecha)
        VALUES (:uid, 'bot', :resp, :campo, NOW())
    ");
    $saveBot->execute([':uid' => $userId, ':resp' => $response, ':campo' => $campo]);
}

// ─── Retornar respuesta ─────────────────────────────────────
jsonResponse([
    'success'  => true,
    'response' => $response
]);


// ============================================================
// FUNCIÓN: Motor de Respuestas
// ============================================================
function getMotorResponse(string $msg, string $campo): string {
    $t = strtolower($msg);
    // Eliminar caracteres especiales para comparación
    $t = preg_replace('/[^a-záéíóúñü\s]/u', '', $t);
    $t = trim($t);

    // ─── Saludos ────────────────────────────────────────────
    if (preg_match('/^(hola|hi|hey|buenos|buenas|saludos)/', $t)) {
        return getWelcome($campo);
    }

    // ─── Recordatorio ───────────────────────────────────────
    if (str_contains($t, 'recordatorio') || str_contains($t, 'recordar') || str_contains($t, 'cita')) {
        return '⏰ <b>Crear recordatorio</b><br><br>Dime:<br>• ¿Qué necesitas recordar?<br>• ¿Cuándo? (fecha y hora)<br><br>Ejemplo: <i>"Examen de matemáticas el viernes a las 10 AM"</i><br><br>Te lo guardo y te aviso a tiempo 😊';
    }

    // ─── Más información ────────────────────────────────────
    if (str_contains($t, 'mas info') || str_contains($t, 'info')) {
        return getInfo($campo);
    }

    // ─── Otro tema ──────────────────────────────────────────
    if (str_contains($t, 'otro tema') || str_contains($t, 'cambiar')) {
        return '🔄 ¡Claro! Abre el menú ☰ para cambiar de campo. O dime directamente el tema.';
    }

    // ─── Ubicación ──────────────────────────────────────────
    if (str_contains($t, 'donde estoy') || str_contains($t, 'ubicacion') || str_contains($t, 'cerca')) {
        return '📍 <b>Estás en Majes, Arequipa</b><br><br>PedroBox está configurado para esta zona.<br><br>¿Qué necesitas buscar?';
    }

    // ─── Recursos académicos ────────────────────────────────
    if (str_contains($t, 'recursos') || str_contains($t, 'estudio')) {
        return '📚 <b>Top recursos:</b><br>• Khan Academy (gratis)<br>• Coursera<br>• YouTube Education<br>• Quizlet<br><br>¿Algo específico?';
    }

    // ─── Instituciones ──────────────────────────────────────
    if (str_contains($t, 'instituciones') || str_contains($t, 'escuela') || str_contains($t, 'universidad')) {
        return '🏫 <b>Instituciones en Majes:</b><br>• CETpad — Formación técnica<br>• Filial UNA<br>• Instituto Educativo Majes<br><br>¿Info de alguna?';
    }

    // ─── Exámenes ───────────────────────────────────────────
    if (str_contains($t, 'examen')) {
        return '📝 <b>¿Cuándo es tu examen?</b><br>Dime la fecha y te ayudo con un plan de estudio.';
    }

    // ─── Precios (negocio) ──────────────────────────────────
    if (str_contains($t, 'precio') || str_contains($t, 'cotizacion') || str_contains($t, 'costo')) {
        return '💰 <b>Generador de precios</b><br>Ingresa producto, cantidad y precio base.<br>Calculo el precio final con IGV.';
    }

    // ─── Clientes ───────────────────────────────────────────
    if (str_contains($t, 'cliente') || str_contains($t, 'atencion')) {
        return '🤝 <b>Respuestas automáticas</b><br>Configura respuestas para cuando un cliente escribe.<br>¿Quieres configurar una?';
    }

    // ─── Ofertas de trabajo ─────────────────────────────────
    if (str_contains($t, 'oferta') || str_contains($t, 'trabajo') || str_contains($t, 'empleo')) {
        return '💼 <b>Ofertas en Majes:</b><br>• Vendedor — Tienda Almacén<br>• Asistente contable<br>• Repartidor express<br><br>¿Te interesa alguna?';
    }

    // ─── CV ─────────────────────────────────────────────────
    if (str_contains($t, 'cv') || str_contains($t, 'curriculum')) {
        return '📄 <b>Creador de CV</b><br>Te guío paso a paso para crear tu CV profesional.<br>¿Empezamos?';
    }

    // ─── Emergencia ─────────────────────────────────────────
    if (str_contains($t, 'emergencia') || str_contains($t, 'urgencia')) {
        return '🚑 <b>EMERGENCIA</b><br><br>• <b>Policía:</b> 105<br>• <b>Bomberos:</b> 102<br>• <b>Ambulancia:</b> 106<br><br>¡Llama inmediatamente!';
    }

    // ─── Música ─────────────────────────────────────────────
    if (str_contains($t, 'musica') || str_contains($t, 'canciones')) {
        return '🎵 <b>Géneros populares:</b><br>• Cumbia, Reggaeton, Salsa, Electrocumbia<br><br>¿Recomendaciones de un género?';
    }

    // ─── Gracias ────────────────────────────────────────────
    if (str_contains($t, 'gracias') || str_contains($t, 'gracia')) {
        return '😊 ¡De nada! Estoy aquí siempre. ¿Algo más?';
    }

    // ─── Eventos ────────────────────────────────────────────
    if (str_contains($t, 'evento') || str_contains($t, 'fiesta')) {
        return '🎉 <b>Eventos próximos:</b><br>• Feria Cultural — sábado<br>• Caminata grupal — domingo 8am<br>• Concierto local — viernes<br><br>¿Te interesa alguno?';
    }

    // ─── Noticias ───────────────────────────────────────────
    if (str_contains($t, 'noticia')) {
        return '📰 <b>Noticias Majes:</b><br>• Nueva plaza en el centro<br>• Feria de Majes próxima<br><br>¿Más detalles?';
    }

    // ─── Horarios ───────────────────────────────────────────
    if (str_contains($t, 'horario')) {
        return '⏰ <b>Horarios en Majes:</b><br>• Educativos: 8am–6pm<br>• Salud: 7am–8pm<br>• Transporte: 5am–11pm';
    }

    // ─── DEFAULT ────────────────────────────────────────────
    $labels = [
        'estudiantil'     => 'Estudiantil',
        'negocio'         => 'Negocio',
        'empleo'          => 'Empleo',
        'salud'           => 'Salud',
        'transporte'      => 'Transporte',
        'entretenimiento' => 'Entretenimiento',
    ];
    return '🤖 No entendí del todo. Estoy en modo <b>' . ($labels[$campo] ?? 'General') . '</b>.<br>Prueba: <i>Más info, Recordatorio, o un tema específico</i> 😊';
}

// ─── Mensajes de bienvenida por campo ───────────────────────
function getWelcome(string $campo): string {
    $welcomes = [
        'estudiantil'     => '👋 ¡Hola! Estoy en modo <b>Estudiantil</b>.<br>Puedo ayudarte con:<br>• Recursos académicos<br>• Recordatorios de exámenes<br>• Horarios e instituciones en Majes',
        'negocio'         => '👋 ¡Hola! Estoy en modo <b>Negocio</b>.<br>Te ayudo con:<br>• Precios y cotizaciones<br>• Gestión de inventario<br>• Respuestas automáticas',
        'empleo'          => '👋 ¡Hola! Estoy en modo <b>Empleo</b>.<br>Te ayudo con:<br>• Ofertas de trabajo<br>• Preparación de CVs<br>• Consejos para entrevistas',
        'salud'           => '👋 ¡Hola! Estoy en modo <b>Salud</b>.<br>Te ayudo con:<br>• Recordatorios de citas<br>• Centros de salud cercanos<br>• Información médica',
        'transporte'      => '👋 ¡Hola! Estoy en modo <b>Transporte</b>.<br>Te ayudo con:<br>• Rutas de buses en Majes<br>• Horarios actualizados<br>• Costos de pasajes',
        'entretenimiento' => '👋 ¡Hola! Estoy en modo <b>Entretenimiento</b>.<br>Te ayudo con:<br>• Eventos locales<br>• Actividades en Majes<br>• Noticias de la comunidad',
    ];
    return $welcomes[$campo] ?? $welcomes['estudiantil'];
}

// ─── Info detallada por campo ───────────────────────────────
function getInfo(string $campo): string {
    $infos = [
        'estudiantil'     => '📚 <b>Recursos recomendados:</b><br>• Khan Academy (gratis)<br>• Biblioteca Virtual UNA<br>• Apps de estudio en Play Store<br><br>¿Necesitas algo específico?',
        'negocio'         => '💼 <b>Para tu negocio:</b><br>• Facturación electrónica<br>• Marketing en redes sociales<br>• Plantillas de precios<br><br>¿Cuál aspecto?',
        'empleo'          => '🏗️ <b>Recursos de empleo:</b><br>• Plataformas de trabajo locales<br>• Plantillas de CVs<br>• Guía de entrevistas<br><br>¿Orientación?',
        'salud'           => '🏥 <b>Centros en Majes:</b><br>• Centro de Salud Majes<br>• Consultorio 24h<br>• Farmacia Comunal<br><br>¿Necesitas cita?',
        'transporte'      => '🚌 <b>Rutas principales:</b><br>• Ruta 1: Majes ↔ Arequipa<br>• Ruta 2: Majes ↔ Camanari<br>• Ruta 3: Centro local<br><br>¿A dónde vas?',
        'entretenimiento' => '🎮 <b>Actividades en Majes:</b><br>• Plaza Central — sábados<br>• Parque de Majes<br>• Eventos culturales<br><br>¿Cuál te interesa?',
    ];
    return $infos[$campo] ?? $infos['estudiantil'];
}
?>
