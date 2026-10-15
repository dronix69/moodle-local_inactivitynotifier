# Informe de revisión — `local_inactivitynotifier`

**Fecha:** 2026-10-15  
**Alcance:** revisión estática del código, configuración, cron, base de datos, privacidad y utilidad para administración/profesorado. No se modificó el plugin.

## Veredicto

El plugin tiene una base funcional clara: detecta estudiantes inactivos, excluye cursos/categorías, respeta finalización, permite plantillas y registra recordatorios. Sin embargo, antes de usarlo ampliamente conviene corregir dos riesgos funcionales y añadir una interfaz de seguimiento para que el administrador y el profesor puedan actuar sobre los casos detectados.

## Hallazgos prioritarios

### 1. Posibles notificaciones duplicadas — **Alta**

Archivo: `classes/task/send_notifications.php` (consulta principal).

La consulta no usa `DISTINCT` ni agrupa por usuario/curso. Un estudiante puede tener varias asignaciones de rol o más de una matrícula activa y aparecer varias veces. En una misma ejecución podrían enviarse varios mensajes y registrarse varios logs antes de que el recordatorio vuelva a filtrar el caso.

**Recomendación:** seleccionar `DISTINCT` por usuario/curso o construir la consulta con `EXISTS`; añadir una restricción/estrategia de unicidad adecuada y una prueba con múltiples matrículas/asignaciones.

### 2. El modo “Solo popup” no queda garantizado — **Alta**

Archivo: `lib.php`, función `local_inactivitynotifier_send_message()`.

`email_only` usa `email_to_user()`, pero `popup_only` y `both` pasan por `message_send()`. La entrega real queda condicionada a las preferencias del usuario y del proveedor; por tanto “Solo popup” no asegura técnicamente que nunca se envíe correo, y “Ambos” tampoco garantiza ambos canales.

**Recomendación:** definir explícitamente el comportamiento soportado. Para control estricto, separar el envío de email y el mensaje Moodle, evitando depender de preferencias cuando el administrador haya seleccionado un modo exclusivo.

### 3. Falta de panel operativo para administración — **Alta**

Actualmente el administrador solo configura parámetros. No puede ver rápidamente cuántos estudiantes están inactivos, qué cursos concentran el problema, cuándo se envió el último aviso, cuántos fallaron ni ejecutar una prueba.

**Mejora propuesta:** página de reporte con filtros por curso/categoría/fecha/estado, último acceso, último aviso, contador de recordatorios y exportación CSV. Añadir botón de “enviar prueba” para un usuario autorizado, sin alterar el registro normal.

### 4. Falta de vista y control para profesores — **Alta**

El plugin notifica al estudiante, pero no informa al profesor. El docente no puede identificar alumnos en riesgo ni enviar un recordatorio manual desde su curso.

**Mejora propuesta:** enlace dentro de cada curso visible para usuarios con capacidad específica, por ejemplo `local/inactivitynotifier:viewreport`; mostrar solo estudiantes matriculados en ese curso y permitir recordatorio manual con capacidad separada y registro de auditoría.

## Hallazgos de prioridad media

- **Rendimiento:** la tarea consulta potencialmente todos los cursos y alumnos en cada ejecución y después llama a `completion_info` por registro. En instalaciones grandes conviene paginar/procesar por curso, limitar lotes y revisar índices de `userid, courseid, timesent`.
- **Exclusión de categorías:** se compara `c.category` directamente. Si se espera excluir una categoría completa, deben incluirse sus subcategorías o documentar que solo se excluye la categoría exacta.
- **Validación de configuración:** `inactivedays` y `remind_frequency` aceptan valores cero o negativos; deben tener mínimo 1 y validación visible para evitar comportamientos inesperados.
- **Privacidad:** el historial de envíos es correcto como finalidad, pero se debería documentar una política de retención; conservar indefinidamente los registros no es necesario para controlar recordatorios.
- **Trazabilidad:** no hay resumen persistente de errores ni contador por tarea. `mtrace()` ayuda al cron, pero el administrador no tiene un historial consultable desde Moodle.
- **Documentación/versionado:** `README.md` indica 1.0.0/1.0.2 en algunos apartados mientras `version.php` declara 1.0.4. También hay referencias a `thirdpartylibs.xml`, pero ese archivo no está presente. Hay que sincronizar la documentación antes de distribuirlo.
- **Compatibilidad:** `version.php` declara soporte hasta Moodle 5.2; debe probarse realmente en cada versión declarada, especialmente APIs de mensajería, privacidad y tareas programadas.

## Lo que está bien resuelto

- SQL parametrizado y filtros de matrícula activa.
- Ignora usuarios eliminados/suspendidos y cursos excluidos.
- Respeta finalización de curso.
- Plantillas con variables y configuración multicanal.
- Registro para evitar recordatorios excesivos.
- Proveedor de privacidad con exportación y borrado por contexto.
- Tabla con claves foráneas e índice básico.
- Las comprobaciones de sintaxis PHP realizadas no presentan errores.

## Plan recomendado

1. **Corrección inmediata:** eliminar duplicados, definir correctamente los modos de envío y validar mínimos de configuración.
2. **Versión orientada al administrador:** reporte global, métricas de cron, exportación CSV y prueba de notificación.
3. **Versión orientada al profesor:** reporte por curso, capacidades Moodle y recordatorio manual auditado.
4. **Escalabilidad y mantenimiento:** paginación, índices revisados, retención configurable, pruebas automatizadas y documentación/versionado coherentes.

## Conclusión

El plugin es aprovechable como motor automático de avisos, pero hoy beneficia principalmente al estudiante y al administrador solo como configurador. Las mejoras con mayor retorno son corregir duplicados y canales de entrega, y añadir reportes accionables para administrador y profesor. No recomiendo declararlo listo para producción masiva hasta resolver los dos hallazgos de prioridad alta.
