# Plugin: Inactivity Notifier para Moodle

**Versión:** 1.0.2 | **Compatibilidad:** Moodle 4.1 a 5.2 | **Licencia:** GPL v3+

---

## 1. ¿Qué hace este plugin?

**Inactivity Notifier** es un plugin local para Moodle que detecta automáticamente estudiantes inactivos en los cursos y les envía una notificación (popup dentro de Moodle y/o correo electrónico) para motivarlos a retomar sus estudios.

### Funcionalidad principal

1. **Detección de inactividad**: Revisa diariamente (a las 8:00 AM mediante una tarea programada de cron) todos los estudiantes en todos los cursos.
2. **Configuración flexible**: Permite definir cuántos días de inactividad deben transcurrir antes de notificar (por defecto: 7 días).
3. **Notificaciones multicanal**: Las notificaciones pueden enviarse como:
   - Popup dentro de Moodle
   - Correo electrónico
   - Ambos canales simultáneamente
4. **Plantillas personalizables**: El asunto y cuerpo del mensaje pueden personalizarse con variables como `{{firstname}}`, `{{coursename}}`, `{{days}}`, `{{courseurl}}`.
5. **Control de repeticiones**: Una vez enviada una notificación, no se reenvía hasta que transcurra el período configurado de "frecuencia de recordatorio" (por defecto: 7 días).
6. **Exclusiones**: Permite excluir cursos específicos, categorías completas y cursos ocultos.
7. **Respeto por la finalización**: Omite a estudiantes que ya han completado el curso (si la finalización de curso está habilitada).
8. **GDPR**: Implementa un proveedor de privacidad completo que permite exportar y eliminar los datos de notificaciones enviadas.

---

## 2. Estructura del plugin

```
local/inactivitynotifier/
│
├── version.php                        # Metadatos del plugin
├── lib.php                            # Funciones públicas reutilizables
├── settings.php                       # Página de configuración en administración
├── README.md                          # Documentación básica
│
├── db/
│   ├── install.xml                    # Definición de tabla en BD (XMLDB)
│   ├── upgrade.php                    # Script de actualización
│   ├── messages.php                   # Registro del proveedor de mensajes
│   └── tasks.php                      # Definición de la tarea programada
│
├── classes/
│   ├── task/
│   │   └── send_notifications.php     # Tarea programada (lógica principal)
│   └── privacy/
│       └── provider.php               # Proveedor de privacidad GDPR
│
└── lang/
    ├── en/
    │   └── local_inactivitynotifier.php  # Traducciones al inglés
    └── es/
        └── local_inactivitynotifier.php  # Traducciones al español
```

### Descripción de archivos clave

#### `version.php`
Define la versión, dependencias de Moodle y madurez del plugin.

```php
$plugin->component = 'local_inactivitynotifier';
$plugin->version   = 2026060600;
$plugin->requires  = 2022112800;  // Moodle 4.1 mínimo
$plugin->supported = [401, 502]; // Moodle 4.1 a 5.2
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';
```

#### `db/install.xml`
Define la tabla `local_inactivitynotifier_sent` que registra cada notificación enviada:

| Columna   | Tipo    | Propósito                            |
|-----------|---------|--------------------------------------|
| `id`      | int PK  | Identificador único                  |
| `userid`  | int FK  | Estudiante notificado                |
| `courseid`| int FK  | Curso en el que estaba inactivo      |
| `timesent`| int     | Marca de tiempo del envío            |

#### `db/tasks.php`
Registra la tarea programada que ejecuta la detección de inactividad cada día a las 8:00 AM.

#### `local_inactivitynotifier_get_inactive_users($courseid, $days)`
Función pública que retorna los estudiantes inactivos de un curso específico. Útil para integraciones externas.

#### `local_inactivitynotifier_send_message($student, $course, $days)`
Función pública que envía la notificación a un estudiante para un curso determinado. Soporta los tres modos de notificación (email, popup o ambos).

#### `classes/task/send_notifications.php`
Núcleo del plugin. La tarea programada:
1. Lee la configuración del plugin.
2. Ejecuta una consulta SQL optimizada que encuentra estudiantes inactivos.
3. Para cada estudiante, verifica que no haya completado el curso.
4. Envía la notificación correspondiente.
5. Registra el envío en la tabla `local_inactivitynotifier_sent`.

---

## 3. Instalación

1. Copiar la carpeta `inactivitynotifier` en `moodle/local/inactivitynotifier/`.
2. Iniciar sesión como administrador en Moodle.
3. Ir a **Administración del sitio > Notificaciones**. El plugin se detectará automáticamente y creará la tabla en la base de datos.
4. Configurar el plugin en **Administración del sitio > Plugins > Plugins locales > Inactivity Notifier**.

### Configuración disponible

| Parámetro                | Tipo        | Defecto | Descripción                                    |
|--------------------------|-------------|---------|------------------------------------------------|
| Días de inactividad      | Entero      | 7       | Días sin acceso antes de notificar             |
| Activar plugin           | Checkbox    | Sí      | Activar/desactivar globalmente                 |
| Solo cursos visibles     | Checkbox    | Sí      | Ignorar cursos ocultos                         |
| Frecuencia de recuerdo   | Entero      | 7       | Días mínimos entre notificaciones              |
| Cursos excluidos         | Texto       | ""      | IDs de cursos separados por coma               |
| Categorías excluidas     | Texto       | ""      | IDs de categorías separados por coma           |
| Modo de notificación     | Select      | Ambos   | Email, Popup o Ambos                           |
| Asunto personalizado     | Texto       | ""      | Plantilla con `{{firstname}}`, etc.            |
| Cuerpo personalizado     | HTML        | ""      | Plantilla HTML con las mismas variables        |

### Ejecución manual (CLI)

```bash
php admin/cli/scheduled_task.php \
  --execute='\local_inactivitynotifier\task\send_notifications'
```

---

## 4. Cómo integrarlo en una página web

El plugin provee tres puntos de integración principales:

### 4.1 Funciones públicas en `lib.php`

Se pueden llamar desde scripts personalizados, otros plugins o páginas web externas que tengan acceso al entorno de Moodle.

**Ejemplo: Obtener estudiantes inactivos de un curso**

```php
require_once('/ruta/a/moodle/config.php');
require_once($CFG->dirroot . '/local/inactivitynotifier/lib.php');

$inactivos = local_inactivitynotifier_get_inactive_users(42, 7);

foreach ($inactivos as $usuario) {
    echo "{$usuario->firstname} {$usuario->lastname} - Último acceso: " .
         date('d/m/Y', $usuario->lastaccess) . "\n";
}
```

**Ejemplo: Enviar notificación manualmente**

```php
require_once('/ruta/a/moodle/config.php');
require_once($CFG->dirroot . '/local/inactivitynotifier/lib.php');

$student = $DB->get_record('user', ['id' => 5]);
$course  = $DB->get_record('course', ['id' => 42]);
$days    = 10;

$enviado = local_inactivitynotifier_send_message($student, $course, $days);
if ($enviado) {
    echo "Notificación enviada correctamente.\n";
}
```

### 4.2 Ejecución programática de la tarea

Desde cualquier script PHP con acceso a Moodle:

```php
$task = new \local_inactivitynotifier\task\send_notifications();
$task->execute();
```

### 4.3 Consultas directas a la base de datos

La tabla `local_inactivitynotifier_sent` puede consultarse para conocer el historial de notificaciones:

```sql
-- Verificar si un usuario fue notificado recientemente
SELECT MAX(timesent) as ultima_notificacion
FROM local_inactivitynotifier_sent
WHERE userid = 5 AND courseid = 42;

-- Contar notificaciones enviadas por curso
SELECT c.fullname, COUNT(s.id) as total_notificaciones
FROM local_inactivitynotifier_sent s
JOIN course c ON c.id = s.courseid
GROUP BY c.id, c.fullname
ORDER BY total_notificaciones DESC;
```

### 4.4 Integración con servicios web (API REST)

Dado que el plugin no expone servicios web nativos, se recomienda crear un plugin local adicional o un endpoint personalizado que consuma las funciones de `lib.php` y las exponga como API REST.

**Ejemplo mínimo de endpoint:**

```php
// webservice.php
require_once('/ruta/a/moodle/config.php');
require_once($CFG->dirroot . '/local/inactivitynotifier/lib.php');

header('Content-Type: application/json');

$courseid = required_param('courseid', PARAM_INT);
$days     = optional_param('days', 7, PARAM_INT);

$inactive = local_inactivitynotifier_get_inactive_users($courseid, $days);

echo json_encode([
    'success'   => true,
    'courseid'  => $courseid,
    'days'      => $days,
    'students'  => $inactive,
    'total'     => count($inactive),
]);
```

---

## 5. Resumen técnico

| Aspecto              | Detalle                                      |
|----------------------|----------------------------------------------|
| Tipo de plugin       | Local plugin (local_)                        |
| Lenguaje             | PHP 8.x                                      |
| Base de datos        | 1 tabla (`local_inactivitynotifier_sent`)    |
| Ejecución            | Cron programado (diario 8:00 AM)             |
| Canales de notificación | Email, popup Moodle, ambos                |
| Personalización      | Plantillas con `{{firstname}}`, `{{coursename}}`, `{{days}}`, `{{courseurl}}` |
| Internacionalización | Inglés y español                             |
| Privacidad           | Proveedor completo GDPR (exportar/eliminar)  |
| Interfaz web         | Solo página de configuración administrativa  |
| JavaScript           | No utiliza                                   |
| Dependencias externas| Ninguna                                      |
