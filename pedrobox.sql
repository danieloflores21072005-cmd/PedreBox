-- ============================================================
-- PedroBox — Base de Datos MySQL
-- ============================================================
-- INSTRUCCIONES:
-- 1. Abre phpMyAdmin en tu navegador: http://localhost/phpmyadmin
-- 2. Ve a la pestaña "SQL" (arriba)
-- 3. Copia TODO este código y pégalo en el editor
-- 4. Clic en "Ejecutar"
-- 5. ¡Listo! La base de datos "pedrobox" estará lista
-- ============================================================


-- ─── Crear la base de datos (si no existe) ─────────────────
CREATE DATABASE IF NOT EXISTS pedrobox
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos
USE pedrobox;


-- ============================================================
-- TABLA: usuarios
-- Almacena todos los usuarios registrados
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)  NOT NULL                        COMMENT 'Nombre completo del usuario',
    email           VARCHAR(150)  NOT NULL UNIQUE                 COMMENT 'Correo electrónico (único)',
    telefono        VARCHAR(20)   NOT NULL                        COMMENT 'Número de WhatsApp',
    contrasena      VARCHAR(255)  NOT NULL                        COMMENT 'Contraseña hasheada con bcrypt',
    campo           ENUM('estudiantil','negocio','empleo','salud','transporte','entretenimiento')
                                  NOT NULL DEFAULT 'estudiantil' COMMENT 'Campo configurado por el usuario',
    activo          TINYINT(1)    NOT NULL DEFAULT 1              COMMENT '1 = activo, 0 = suspendido',
    fecha_registro  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del registro',
    fecha_actualizacion DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Usuarios registrados de PedroBox';


-- ============================================================
-- TABLA: sesiones
-- Almacena tokens de sesión activas
-- ============================================================
CREATE TABLE IF NOT EXISTS sesiones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED  NOT NULL,
    token           VARCHAR(64)   NOT NULL UNIQUE                COMMENT 'Token aleatorio de 64 caracteres',
    fecha_inicio    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin       DATETIME      NOT NULL                       COMMENT 'Expiración de la sesión (7 días por defecto)',
    INDEX idx_usuario (usuario_id),
    INDEX idx_token   (token),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokens de sesión activas';


-- ============================================================
-- TABLA: conversaciones
-- Guarda todo el historial de chat entre usuario y bot
-- ============================================================
CREATE TABLE IF NOT EXISTS conversaciones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED  NOT NULL,
    tipo            ENUM('usuario','bot') NOT NULL               COMMENT 'Quien envió el mensaje',
    mensaje         TEXT          NOT NULL                       COMMENT 'Contenido del mensaje',
    campo           ENUM('estudiantil','negocio','empleo','salud','transporte','entretenimiento')
                                  NOT NULL DEFAULT 'estudiantil' COMMENT 'Campo activo al momento del mensaje',
    fecha           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_fecha (usuario_id, fecha),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de conversaciones';


-- ============================================================
-- TABLA: historial
-- Log de actividades del sistema (login, logout, cambios, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS historial (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED  NOT NULL,
    tipo            VARCHAR(30)   NOT NULL                       COMMENT 'Tipo: registro, login, logout, campo_cambio, etc.',
    detalle         TEXT                                         COMMENT 'Descripción detallada',
    fecha           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_fecha (usuario_id, fecha),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de actividades del sistema';


-- ============================================================
-- TABLA: recordatorios
-- Almacena los recordatorios creados por cada usuario
-- ============================================================
CREATE TABLE IF NOT EXISTS recordatorios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED  NOT NULL,
    titulo          VARCHAR(200)  NOT NULL                       COMMENT 'Título del recordatorio',
    descripcion     TEXT                                         COMMENT 'Descripción opcional',
    fecha_recordar  DATETIME      NOT NULL                       COMMENT 'Cuándo activar el recordatorio',
    completado      TINYINT(1)    NOT NULL DEFAULT 0             COMMENT '0 = pendiente, 1 = completado',
    campo           ENUM('estudiantil','negocio','empleo','salud','transporte','entretenimiento')
                                  NOT NULL DEFAULT 'estudiantil',
    fecha_creacion  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha_recordar (fecha_recordar),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Recordatorios de los usuarios';


-- ============================================================
-- TABLA: campos_config
-- Configuración de respuestas para cada campo
-- (permite editar respuestas sin tocar código)
-- ============================================================
CREATE TABLE IF NOT EXISTS campos_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campo           ENUM('estudiantil','negocio','empleo','salud','transporte','entretenimiento')
                                  NOT NULL,
    keyword         VARCHAR(50)   NOT NULL                       COMMENT 'Palabra clave que activa la respuesta',
    respuesta       TEXT          NOT NULL                       COMMENT 'Respuesta HTML del bot',
    activo          TINYINT(1)    NOT NULL DEFAULT 1,
    UNIQUE KEY uk_campo_keyword (campo, keyword)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuración de respuestas por campo y keyword';


-- ============================================================
-- DATOS DE EJEMPLO (usuario demo para pruebas)
-- Contraseña del demo: 123456
-- Email: demo@pedrobox.com
-- ============================================================

-- Usuario demo (contraseña "123456" hasheada con bcrypt)
INSERT INTO usuarios (nombre, email, telefono, contrasena, campo)
VALUES (
    'Usuario Demo',
    'demo@pedrobox.com',
    '+51 900 000 000',
    '$2y$10$YourHashHere',  -- NOTA: esta línea se actualiza abajo
    'estudiantil'
);

-- Actualizar el hash correctamente
-- (Si no funciona el login demo, re-genera el hash con: password_hash('123456', PASSWORD_BCRYPT) )
UPDATE usuarios 
SET contrasena = '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ12'
WHERE email = 'demo@pedrobox.com';


-- ============================================================
-- DATOS DE EJEMPLO: campos_config (respuestas configuradas)
-- ============================================================
INSERT INTO campos_config (campo, keyword, respuesta) VALUES
('estudiantil', 'recursos',      '📚 <b>Recursos:</b><br>• Khan Academy<br>• Biblioteca Virtual UNA<br>• Quizlet'),
('estudiantil', 'instituciones', '🏫 <b>Instituciones:</b><br>• CETpad<br>• Filial UNA<br>• Instituto Educativo Majes'),
('negocio',     'precios',       '💰 <b>Precios:</b><br>Ingresa producto y cantidad para calcular.'),
('negocio',     'inventario',    '📦 <b>Inventario:</b><br>Registra tus productos aquí.'),
('empleo',      'ofertas',       '💼 <b>Ofertas:</b><br>• Vendedor<br>• Asistente contable<br>• Repartidor'),
('salud',       'centros',       '🏥 <b>Centros:</b><br>• Centro Salud Majes<br>• Consultorio 24h'),
('transporte',  'rutas',         '🚌 <b>Rutas:</b><br>• Ruta 1: Majes-Arequipa<br>• Ruta 2: Majes-Camanari'),
('entretenimiento', 'eventos',   '🎉 <b>Eventos:</b><br>• Feria Cultural<br>• Caminata grupal');


-- ============================================================
-- VERIFICACIÓN: ver las tablas creadas
-- ============================================================
SHOW TABLES;
?>
