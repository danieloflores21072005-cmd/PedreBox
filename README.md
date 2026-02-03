# 🤖 PedroBox — Proyecto Completo

**Asistente Virtual para los Jóvenes de Majes**

---

## 📁 Estructura del Proyecto

```
pedrobox_proyecto/
│
├── frontend/
│   └── index.html              ← App completa (Login, Registro, Chat, etc.)
│
├── backend/
│   ├── config.php              ← Configuración BD + funciones comunes
│   ├── register.php            ← Endpoint: Crear cuenta
│   ├── login.php               ← Endpoint: Iniciar sesión
│   ├── chat.php                ← Endpoint: Motor de chat
│   ├── update_campo.php        ← Endpoint: Cambiar campo
│   └── logout.php              ← Endpoint: Cerrar sesión
│
├── database/
│   └── pedrobox.sql            ← SQL completo para phpMyAdmin
│
└── README.md                   ← Este archivo (instrucciones)
```

---

## ⚡ PASO 1: Instalar y encender XAMPP

1. Descarga XAMPP desde: **https://www.apachefriends.org/**
2. Instala normalmente (marca Apache + MySQL durante la instalación)
3. Abre **XAMPP Control Panel**
4. Enciende **Apache** → clic en `Start`
5. Enciende **MySQL** → clic en `Start`

```
✅ Apache  [Start]   → Running
✅ MySQL    [Start]   → Running
```

---

## 🗄️ PASO 2: Crear la Base de Datos en phpMyAdmin

1. Abre tu navegador y ve a: **http://localhost/phpmyadmin**
2. En la barra izquierda verás las bases de datos existentes
3. Ve a la pestaña **SQL** (arriba en la barra horizontal)
4. Abre el archivo `database/pedrobox.sql` con un editor de texto (Notepad, VS Code)
5. **Copia todo** el contenido
6. **Pégalo** en el editor SQL de phpMyAdmin
7. Clic en el botón **Ejecutar** (esquina inferior derecha)
8. Verás la tabla `pedrobox` creada con todas las tablas

```
Tablas creadas:
  ├── usuarios          → Datos de los usuarios
  ├── sesiones          → Tokens de sesión
  ├── conversaciones    → Historial del chat
  ├── historial         → Log de actividades
  ├── recordatorios     → Recordatorios de usuarios
  └── campos_config     → Configuración de respuestas
```

**⚠️ NOTA sobre el usuario Demo:**
El SQL incluye un usuario de prueba pero el hash de contraseña necesita generarse tú mismo.
Para hacerlo:
1. En phpMyAdmin ve a la tabla `usuarios`
2. Clic en `Modificar` la fila del usuario demo
3. En el campo `contrasena` pega un hash generado así en PHP:

```php
<?php echo password_hash('123456', PASSWORD_BCRYPT); ?>
```

Ejecuta esa línea en cualquier archivo PHP temporal y copia el resultado.

---

## 🖥️ PASO 3: Instalar el Backend en XAMPP

1. Busca la carpeta de XAMPP. Dentro hay una carpeta llamada **htdocs**:
   - **Windows:** `C:\xampp\htdocs\`
   - **Mac:**    `/Applications/XAMPP/xamppfiles/htdocs/`
   - **Linux:**  `/var/www/html/`

2. Dentro de `htdocs`, crea una carpeta llamada **pedrobox**

3. Copia los archivos de la carpeta `backend/` ahí:
```
htdocs/
  └── pedrobox/
        ├── config.php
        ├── register.php
        ├── login.php
        ├── chat.php
        ├── update_campo.php
        └── logout.php
```

4. Verifica que funciona abriendo en el navegador:
   - **http://localhost/pedrobox/login.php**
   - Debería mostrar `{"success":false,"message":"Método no permitido"}`
   - Eso es CORRECTO (porque es un endpoint POST, no GET)

---

## 📱 PASO 4: Conectar el Frontend al Backend

En el archivo `frontend/index.html`, busca esta línea cerca del inicio del `<script>`:

```javascript
const API_BASE = null; // null = modo simulado (prototipo)
```

Cámbiala a:

```javascript
const API_BASE = 'http://localhost/pedrobox/';
```

Si lo pruebas desde otro dispositivo de la misma red WiFi, reemplaza `localhost` por la IP de tu computadora:

```javascript
const API_BASE = 'http://192.168.1.XXX/pedrobox/';
// Reemplaza XXX con la IP real de tu PC
// La puedes ver en: cmd → ipconfig → "Dirección IPv4"
```

---

## 📲 PASO 5: Convertir a APK Android

Tienes **3 métodos** para crear la APK. Te recomiendo el **Método A** (el más profesional) o el **Método C** (el más rápido sin instalar nada).

---

### 🏆 MÉTODO A: Capacitor (Recomendado — gratuito, profesional)

Capacitor convierte tu HTML/CSS/JS en una app nativa de Android.

**Requisitos previos:**
- Node.js instalado (descarga en https://nodejs.org → versión LTS)
- Android Studio instalado (descarga en https://developer.android.com/studio)

**Pasos:**

```bash
# 1. Crea una carpeta nueva y entra en ella
mkdir pedrobox-apk
cd pedrobox-apk

# 2. Inicializa el proyecto
npm init -y

# 3. Instala Capacitor
npm install @capacitor/core @capacitor/cli @capacitor/android

# 4. Inicializa Capacitor con el nombre de tu app
npx cap init
# Cuando te pida nombre:        PedroBox
# Cuando te pida package name:  com.pedrobox.app

# 5. Agrega la plataforma Android
npx cap add android

# 6. Copia tu index.html a la carpeta "www"
#    (Si no existe la carpeta "www", créala)
mkdir www
# Copia frontend/index.html → www/index.html

# 7. Sincroniza los archivos
npx cap sync

# 8. Abre en Android Studio
npx cap open android
```

En Android Studio:
- Espera que termine de cargar (puede tardar 2-3 minutos)
- Clic en el botón **▶ Run** (la flecha verde arriba)
- Si tienes un teléfono conectado por USB con **Depuración USB** activada, se instala directo
- Si no tienes teléfono, usa el **Emulador** que trae Android Studio

Para **exportar la APK**:
- Ve a `Build` → `Build Bundle(s)/APK(s)` → `Build APK(s)`
- La APK se genera en: `android/app/build/outputs/apk/debug/app-debug.apk`

---

### ⚡ MÉTODO B: Android WebView (Manual — sin dependencias extras)

Si ya conoces un poco de Android:

1. Abre **Android Studio** → `New Project` → `Empty Activity`
2. En `app/src/main/res/layout/activity_main.xml` reemplaza todo con:

```xml
<?xml version="1.0" encoding="utf-8"?>
<androidx.constraintlayout.widget.ConstraintLayout
    xmlns:android="http://schemas.android.com/apk/res/android"
    xmlns:tools="http://schemas.android.com/tools"
    android:layout_width="match_parent"
    android:layout_height="match_parent"
    tools:context=".MainActivity">

    <android.webkit.WebView
        android:id="@+id/webView"
        android:layout_width="match_parent"
        android:layout_height="match_parent"
        android:layout_constraintStart_toStartOf="parent"
        android:layout_constraintTop_toTopOf="parent" />

</androidx.constraintlayout.widget.ConstraintLayout>
```

3. En `app/src/main/java/.../MainActivity.kt` escribe:

```kotlin
import androidx.appcompat.app.AppCompatActivity
import android.webkit.WebView
import android.webkit.WebViewClient
import android.os.Bundle

class MainActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        val webView: WebView = findViewById(R.id.webView)
        webView.webViewClient = WebViewClient()
        webView.settings.javaScriptEnabled = true

        // Opción A: Cargar desde XAMPP (red local)
        webView.loadUrl("http://192.168.1.XXX/pedrobox/frontend/index.html")

        // Opción B: Cargar el archivo HTML local (sin internet)
        // webView.loadUrl("file:///android_asset/index.html")
    }
}
```

4. Si usas **Opción B** (archivo local):
   - Crea la carpeta: `app/src/main/assets/`
   - Copia `index.html` ahí

5. En `AndroidManifest.xml` agrega permisos de internet:
```xml
<uses-permission android:name="android.permission.INTERNET" />
```

6. Clic **▶ Run** → se genera la APK

---

### 🚀 MÉTODO C: APK Online (Sin instalar nada — más rápido)

Si no quieres instalar Android Studio ni Node.js:

1. Ve a **https://www.appgyver.com/** (gratuito)
   - O también puedes usar **https://php.telusplc.com/apk/** como alternativa

2. Crea una cuenta gratuita

3. Crea un nuevo proyecto → selecciona **"Web App"**

4. En la sección de URL o código fuente, sube tu `index.html`

5. Configura:
   - Nombre: **PedroBox**
   - Icono: puedes usar cualquier icono verde con 🤖
   - Orientación: Retrato (Portrait)

6. Clic en **Export** → **Android APK**

7. Descarga la APK generada

**⚠️ NOTA:** Los métodos online gratuitos pueden agregar publicidad. Para una presentación profesional, usa el Método A (Capacitor).

---

## 🧪 PASO 6: Probar la Aplicación

### Prueba rápida (sin backend):
- Abre `frontend/index.html` directamente en tu navegador
- El modo simulado funciona sin XAMPP
- Login de demo: **demo@pedrobox.com** / **123456**

### Prueba completa (con backend):
1. XAMPP encendido (Apache + MySQL)
2. Base de datos importada
3. `API_BASE` configurado en el index.html
4. Registra un usuario nuevo o usa el demo

---

## 🔧 Resumen de Endpoints del Backend

| Método | URL                              | Descripción              |
|--------|----------------------------------|--------------------------|
| POST   | /pedrobox/register.php           | Crear cuenta nueva       |
| POST   | /pedrobox/login.php              | Iniciar sesión           |
| POST   | /pedrobox/chat.php               | Enviar mensaje al bot    |
| POST   | /pedrobox/update_campo.php       | Cambiar campo del usuario|
| POST   | /pedrobox/logout.php             | Cerrar sesión            |

---

## 💡 Consejos para la Exposición

- **Modo sin internet:** Si vas a presentar en un lugar sin WiFi, usa el modo simulado (`API_BASE = null`) que funciona 100% offline
- **Pantalla completa:** En el navegador presiona **F11** para pantalla completa
- **En el teléfono:** Si prefieres mostrar en un celular real, instala la APK (Método A, B o C) y conecta al XAMPP de tu computadora por WiFi local
- **Demo rápido:** El login simulado acepta cualquier email/contraseña cuando `API_BASE = null`. Si conectas al backend real, usa el usuario demo

---

## 📞 Tecnologías Usadas

| Tecnología | Uso |
|------------|-----|
| HTML5 / CSS3 | Frontend de la app |
| JavaScript | Lógica del cliente y motor de chat simulado |
| PHP 8.x | Backend (APIs REST) |
| MySQL / MariaDB | Base de datos |
| phpMyAdmin | Administrador visual de BD |
| XAMPP | Servidor local (Apache + MySQL) |
| Kotlin | desarrollo nativo Android (si usas Método B) |
| Capacitor | Empaquetador HTML→APK (Método A) |

---

*PedroBox v1.0 · Prototipo · Desarrollado para la comunidad de Majes, Arequipa*
